import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
	testDir: '.',
	testMatch: '*.spec.ts',
	workers: 1,
	retries: 0,
	timeout: 180_000,
	expect: { timeout: 30_000 },
	use: {
		baseURL: process.env.E2E_BASE_URL,
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
		navigationTimeout: 60_000,
	},
	projects: [
		{
			name: 'chromium',
			use: {
				...devices['Desktop Chrome'],
				// The site is served over plain http://; Chrome would otherwise upgrade it to the tunnel's https.
				launchOptions: { args: [ '--disable-features=HttpsUpgrades' ] },
			},
		},
	],
});
