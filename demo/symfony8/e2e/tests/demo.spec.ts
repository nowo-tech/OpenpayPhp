import { test, expect } from '@playwright/test';

/**
 * REQ-DEMO-013 — happy-path e2e against the OpenpayPhp demo: the UiKit shell, the FormKit customer form, the mocked
 * Openpay session (no keys configured) and the validation error path.
 */
test.describe('OpenpayPhp demo', () => {
  test('home renders the UiKit shell, the mock notice and the customer form', async ({ page }) => {
    const response = await page.goto('/');
    expect(response?.ok()).toBeTruthy();
    await expect(page.getByTestId('demo-platform')).toBeVisible();
    await expect(page.locator('.nowo-ui-flashes')).toContainText('mocked');
    const form = page.locator('[data-testid="demo-customer-form"]');
    await expect(form.getByLabel('Name')).toHaveValue('Demo User');
    await expect(form.getByLabel('Email')).toHaveValue('demo@example.test');
  });

  test('creating a customer shows the mocked Openpay payload', async ({ page }) => {
    await page.goto('/');
    const form = page.locator('[data-testid="demo-customer-form"]');
    await form.getByLabel('Name').fill('Ada Lovelace');
    await form.getByLabel('Email').fill('ada@example.test');
    await page.getByRole('button', { name: 'Create customer' }).click();
    await expect(page.getByRole('heading', { level: 1 })).toHaveText('Customer created');
    const payload = JSON.parse((await page.locator('[data-testid="demo-result-payload"]').textContent()) ?? '{}');
    expect(payload.mock).toBe(true);
    expect(payload.id).toMatch(/^cus_demo_/);
    await page.getByRole('link', { name: 'Back' }).click();
    await expect(page).toHaveURL(/\/$/);
  });

  test('an invalid email is refused with a 422 and the form stays on the page', async ({ page }) => {
    await page.goto('/');
    const form = page.locator('[data-testid="demo-customer-form"]');
    // Skip the browser's own email check so the server-side validation answers.
    await form.locator('form').evaluate((el) => { (el as HTMLFormElement).noValidate = true; });
    await form.getByLabel('Email').fill('not-an-email');
    const [response] = await Promise.all([
      page.waitForResponse((r) => r.url().endsWith('/customers') && r.request().method() === 'POST'),
      page.getByRole('button', { name: 'Create customer' }).click(),
    ]);
    expect(response.status()).toBe(422);
    await expect(page.locator('[data-testid="demo-customer-form"]')).toBeVisible();
  });
});
