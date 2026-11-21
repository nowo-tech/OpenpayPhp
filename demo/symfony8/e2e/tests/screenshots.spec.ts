import { test, expect } from '@playwright/test';
import { mkdirSync } from 'node:fs';
import { resolve } from 'node:path';
import { frameShot } from './frame';

/**
 * REQ-DEMO-013 — full demo frame (demo platform shell + the Openpay customer flow, no WebProfiler toolbar), captured with a
 * tall viewport (see frame.ts).
 */
const outDir = process.env.SCREENSHOT_DIR
  ? resolve(process.env.SCREENSHOT_DIR)
  : resolve(__dirname, '../../../../docs/images/demo');

test.use({ viewport: { width: 1360, height: 900 } });

test.beforeAll(() => {
  mkdirSync(outDir, { recursive: true });
});

test('overview — customer form on the mocked session', async ({ page }) => {
  await page.goto('/');
  await expect(page.locator('[data-testid="demo-customer-form"]')).toBeVisible();
  await frameShot(page, resolve(outDir, 'overview.png'));
});

test('result — mocked Openpay customer', async ({ page }) => {
  await page.goto('/');
  await page.getByRole('button', { name: 'Create customer' }).click();
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Customer created');
  await frameShot(page, resolve(outDir, 'customer-created.png'));
});
