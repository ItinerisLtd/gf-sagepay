#!/usr/bin/env bash
#
# Runs the Playwright payment tests against Opayo's sandbox.
# Builds a wp-env site, opens a Cloudflare quick tunnel so SagePay can reach it, then runs the tests.
# The site is public while the tunnel is open, so wp-env only mounts throwaway copies from build/ and the admin
# password is randomised first.
#
# Environment:
#   E2E_KEEP_ENV  leave the wp-env containers running afterwards when set

set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")"

readonly WP_ENV_PORT=8888

fail() {
	echo "e2e: $*" >&2
	exit 1
}

command -v cloudflared >/dev/null || fail 'cloudflared is required on PATH (https://github.com/cloudflare/cloudflared/releases)'
command -v docker >/dev/null || fail 'docker is required for wp-env'
command -v rsync >/dev/null || fail 'rsync is required'
command -v composer >/dev/null || fail 'composer is required'
[[ -f ../gravityforms/vendor/gravity/gravityforms/gravityforms.php ]] ||
	fail 'Gravity Forms is missing; run: composer install --working-dir=tests/gravityforms'

tunnel_log="$(mktemp)"

cleanup() {
	if [[ -n "${tunnel_pid:-}" ]]; then
		kill "${tunnel_pid}" 2>/dev/null || true
	fi

	if [[ -z "${E2E_KEEP_ENV:-}" ]]; then
		npx wp-env stop >/dev/null 2>&1 || true
	fi

	rm -f "${tunnel_log}"
}
trap cleanup EXIT

# Synced in place rather than recreated, so containers kept from an earlier run keep valid mounts.
# The plugin copy gets its own production-only vendor/, so no dev packages are served through the tunnel.
mkdir -p build/gf-sagepay build/gravityforms build/mu-plugins build/site
rsync -a --delete --include='/src/***' --include='/gf-sagepay.php' --include='/composer.json' \
	--include='/composer.lock' --exclude='*' ../../ build/gf-sagepay/
composer install --working-dir=build/gf-sagepay --no-dev --no-progress --prefer-dist --optimize-autoloader
rsync -a --delete ../gravityforms/vendor/gravity/gravityforms/ build/gravityforms/
rsync -a --delete mu-plugins/ build/mu-plugins/
rsync -a --delete site/ build/site/

# wp-env starts slowly, so the tunnel only opens once the site is up.
npx wp-env start
if [[ -z "${CI:-}" ]]; then
	npx wp-env reset development
fi
# Generated inside the container, so the password never appears in a command line or the (public) CI log.
# shellcheck disable=SC2016 # PHP variables for wp eval, not shell ones.
npx wp-env run cli wp eval '$admin = get_user_by("login", "admin"); if (! $admin instanceof WP_User) { exit(1); } wp_set_password(wp_generate_password(32), $admin->ID);'

# Starts a quick tunnel and waits for its connection; succeeds only once the tunnel is registered.
# HTTP/2 over TCP, as QUIC (UDP 7844) is often blocked.
open_tunnel() {
	cloudflared tunnel --no-autoupdate --protocol http2 --url "http://localhost:${WP_ENV_PORT}" >"${tunnel_log}" 2>&1 &
	tunnel_pid=$!

	for _ in $(seq 1 60); do
		grep -q 'Registered tunnel connection' "${tunnel_log}" && return 0
		kill -0 "${tunnel_pid}" 2>/dev/null || return 1
		sleep 1
	done

	return 1
}

# Quick tunnel provisioning occasionally fails on Cloudflare's side, so it gets three attempts.
for attempt in 1 2 3; do
	open_tunnel && break
	kill "${tunnel_pid}" 2>/dev/null || true
	cat "${tunnel_log}" >&2
	[[ "${attempt}" -lt 3 ]] || fail 'cloudflared did not open a tunnel'
	sleep $((attempt * 10))
done
tunnel_url="$(grep -oE 'https://[a-z0-9-]+\.trycloudflare\.com' "${tunnel_log}" | head -n 1 || true)"
if [[ -z "${tunnel_url}" ]]; then
	cat "${tunnel_log}" >&2
	fail 'cloudflared did not report a tunnel URL'
fi

# SagePay's notifications reach a quick tunnel over plain HTTP only, so the site (and its notification URL) uses http://.
site_url="http://${tunnel_url#https://}"
# wp-env adds its port to these constants, which the tunnel URL must not have.
npx wp-env run cli wp config set WP_HOME "${site_url}"
npx wp-env run cli wp config set WP_SITEURL "${site_url}"

fixture="$(npx wp-env run cli wp eval 'require WP_CONTENT_DIR . "/gf-sagepay-e2e/fixture.php";')"

home="$(npx wp-env run cli wp option get home)"
[[ "${home}" == "${site_url}" ]] || fail "the site's home URL must be ${site_url} for SagePay to reach it, got ${home}"

reachable=''
for _ in $(seq 1 60); do
	if curl -fsS -o /dev/null "${site_url}/"; then
		reachable=1
		break
	fi
	sleep 2
done
if [[ -z "${reachable}" ]]; then
	cat "${tunnel_log}" >&2
	fail "the site is not reachable through the tunnel at ${site_url}"
fi

E2E_BASE_URL="${site_url}" E2E_FIXTURE="${fixture}" npx playwright test
