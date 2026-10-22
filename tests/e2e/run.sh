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
[[ -f ../gravityforms/vendor/gravity/gravityforms/gravityforms.php ]] ||
	fail 'Gravity Forms is missing; run: composer install --working-dir=tests/gravityforms'
[[ -f ../../vendor/autoload.php ]] || fail 'the plugin has no vendor directory; run: composer install'

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

rm -rf build
mkdir -p build/gf-sagepay
cp -R ../../src ../../vendor ../../gf-sagepay.php ../../composer.json build/gf-sagepay/
cp -R ../gravityforms/vendor/gravity/gravityforms build/gravityforms
cp -R mu-plugins site build/

# wp-env starts slowly, so the tunnel only opens once the site is up.
npx wp-env start
if [[ -z "${CI:-}" ]]; then
	npx wp-env reset development
fi
npx wp-env run cli wp user update admin --user_pass="$(openssl rand -hex 24)" --skip-email

# HTTP/2 over TCP, as QUIC (UDP 7844) is often blocked.
cloudflared tunnel --no-autoupdate --protocol http2 --url "http://localhost:${WP_ENV_PORT}" >"${tunnel_log}" 2>&1 &
tunnel_pid=$!

for _ in $(seq 1 60); do
	grep -q 'Registered tunnel connection' "${tunnel_log}" && break
	kill -0 "${tunnel_pid}" 2>/dev/null || break
	sleep 1
done
tunnel_url="$(grep -oE 'https://[a-z0-9-]+\.trycloudflare\.com' "${tunnel_log}" | head -n 1 || true)"
if [[ -z "${tunnel_url}" ]] || ! grep -q 'Registered tunnel connection' "${tunnel_log}"; then
	cat "${tunnel_log}" >&2
	fail 'cloudflared did not open a tunnel'
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
