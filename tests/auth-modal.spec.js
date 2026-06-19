import { expect, test } from '@playwright/test';

const AUTH_USERNAME = 'd.crysty23@gmail.com';
const AUTH_PASSWORD = 'Steauab23.';

async function openAuthModal(page, redirectUrl) {
  if (typeof redirectUrl !== 'undefined') {
    await page.evaluate((targetUrl) => {
      const trigger = document.createElement('button');
      trigger.type = 'button';
      trigger.id = 'pap-test-auth-trigger';
      trigger.setAttribute('data-auth-modal-open', '');
      if (targetUrl) {
        trigger.setAttribute('data-auth-redirect', targetUrl);
      }
      trigger.textContent = 'Test auth trigger';
      document.body.appendChild(trigger);
    }, redirectUrl);

    await page.locator('#pap-test-auth-trigger').click();
    return;
  }

  await page.locator('[data-pap-auth-account]').first().click();
}

async function submitAuthModalLogin(page) {
  const modal = page.locator('#pap-auth-modal');
  await expect(modal).toBeVisible();

  const loginForm = modal.locator('form[data-auth-form="login"]').first();
  const usernameField = loginForm.locator('input[name="username"]').first();
  const passwordField = loginForm.locator('input[name="password"]').first();
  const submitButton = loginForm.locator('button[type="submit"]').first();

  await usernameField.fill(AUTH_USERNAME);
  await passwordField.fill(AUTH_PASSWORD);

  await Promise.all([
    page.waitForResponse((response) => {
      return response.url().includes('admin-ajax.php')
        && response.request().method() === 'POST'
        && (response.request().postData() || '').includes('action=pap_auth_login')
        && response.ok();
    }),
    submitButton.click(),
  ]);
}

test('login from modal updates auth state and keeps the user on the current page', async ({ page }) => {
  const errors = [];
  page.on('pageerror', (error) => {
    errors.push(`pageerror: ${error.message}`);
  });
  page.on('console', (message) => {
    if (message.type() === 'error') {
      errors.push(`console:error: ${message.text()}`);
    }
  });

  await page.context().clearCookies();
  await page.goto('/');

  const accountTool = page.locator('[data-pap-auth-account]').first();

  await expect(accountTool).toContainText('Autentificare');

  const modal = page.locator('#pap-auth-modal');
  await openAuthModal(page, '');
  await submitAuthModalLogin(page);

  await expect(modal.locator('[data-auth-view="login"] .pap-auth-notice--success')).toContainText('Te-ai autentificat cu succes.');
  await page.waitForFunction(() => Boolean(window.papAuthState && window.papAuthState.is_logged_in));
  await page.waitForFunction(() => Boolean(window.papAccountUi && window.papAccountUi.authState && window.papAccountUi.authState.is_logged_in));

  await expect(page.locator('#pap-auth-modal')).toBeHidden();
  await expect(accountTool).toContainText('Contul meu');
  await expect(accountTool).toContainText('Bun venit');
  await expect(page).toHaveURL(/\/$/);

  expect(errors).toEqual([]);
});
