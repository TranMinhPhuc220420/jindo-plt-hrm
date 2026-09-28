import { expect,  test } from '@playwright/test';
import type {Page} from '@playwright/test';

async function loginAs(
    page: Page,
    email: string,
    password = 'password',
): Promise<void> {
    await page.goto('/login');
    await expect(page.getByTestId('login-button')).toBeVisible();
    await page.locator('#email').fill(email);
    await page.locator('#password').fill(password);
    await Promise.all([
        page.waitForURL(/\/dashboard/, { timeout: 30_000 }),
        page.getByTestId('login-button').click(),
    ]);
}

async function logout(page: Page): Promise<void> {
    await page.getByTestId('sidebar-menu-button').click();
    await page.getByTestId('logout-button').click();
    await expect(page).toHaveURL(/\/login/, { timeout: 30_000 });
}

test.describe('HRM smoke', () => {
    test('admin can login and reach the dashboard', async ({ page }) => {
        await loginAs(page, 'admin@example.test');
        await expect(page.locator('body')).toBeVisible();
    });

    test('admin can open employees leave attendance and payroll pages', async ({
        page,
    }) => {
        await loginAs(page, 'admin@example.test');

        await page.goto('/employees');
        await expect(page).toHaveURL(/\/employees/);
        await expect(page.getByRole('button', { name: /create|tạo/i })).toBeVisible();

        await page.goto('/leave');
        await expect(page).toHaveURL(/\/leave/);
        await expect(page.locator('body')).toContainText(/leave|nghỉ|phép/i);

        await page.goto('/attendance');
        await expect(page).toHaveURL(/\/attendance/);
        await expect(page.locator('body')).toBeVisible();

        await page.goto('/payroll');
        await expect(page).toHaveURL(/\/payroll/);
        await expect(page.locator('body')).toBeVisible();
    });

    test('viewer without employee permission sees permission denied on employees', async ({
        page,
    }) => {
        await loginAs(page, 'viewer@example.test');
        await page.goto('/employees');
        await expect(page.locator('body')).toContainText(
            /permission denied|không có quyền|denied/i,
        );
        await expect(
            page.getByRole('button', { name: /create|tạo/i }),
        ).toHaveCount(0);
    });

    test('admin can logout', async ({ page }) => {
        await loginAs(page, 'admin@example.test');
        await logout(page);
        await expect(page.getByTestId('login-button')).toBeVisible();
    });

    test('unauthenticated visit to dashboard redirects to login', async ({
        page,
    }) => {
        await page.goto('/dashboard');
        await expect(page).toHaveURL(/\/login/);
    });
});
