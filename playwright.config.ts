import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1:8000';

export default defineConfig({
    testDir: './e2e',
    globalSetup: './e2e/global-setup.ts',
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    workers: 1,
    reporter: process.env.CI ? 'github' : 'list',
    timeout: 60_000,
    expect: { timeout: 15_000 },
    use: {
        baseURL,
        testIdAttribute: 'data-test',
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
        // Avoid requiring Playwright's ffmpeg download on unsupported hosts.
        video: 'off',
    },
    projects: [
        {
            name: 'chromium',
            use: {
                ...devices['Desktop Chrome'],
                // Local hosts (e.g. Ubuntu 26) may lack Playwright's bundled chromium.
                // CI installs it via `npx playwright install --with-deps chromium`.
                ...(process.env.CI ? {} : { channel: 'chrome' as const }),
            },
        },
    ],
    webServer: {
        command: 'bash e2e/start-server.sh',
        url: baseURL,
        reuseExistingServer: !process.env.CI,
        timeout: 120_000,
        stdout: 'ignore',
        stderr: 'pipe',
    },
});
