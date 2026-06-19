import { expect, test } from '@playwright/test';

test('cart recommendations add-to-cart keeps mini-cart and cart page synchronized', async ({ page }) => {
  const errors = [];
  page.on('pageerror', (error) => {
    errors.push(`pageerror: ${error.message}`);
  });
  page.on('console', (message) => {
    if (message.type() === 'error') {
      errors.push(`console:error: ${message.text()}`);
    }
  });

  await page.goto('/');

  const homepageAddButton = page.locator('.pap-home-add-to-cart').first();
  await expect(homepageAddButton).toBeVisible();

  await Promise.all([
    page.waitForResponse((response) => {
      return response.url().includes('admin-ajax.php')
        && response.request().method() === 'POST'
        && (response.request().postData() || '').includes('action=pap_home_add_to_cart')
        && response.ok();
    }),
    homepageAddButton.click(),
  ]);

  await expect(page.locator('[data-pap-cart-count]').first()).toContainText('1 produs');

  await page.goto('/?page_id=8');
  await expect(page.getByText('S-ar putea să-ți placă și')).toBeVisible();

  const recommendationCard = page.locator('.pap-featured .pap-product-card').first();
  await expect(recommendationCard).toBeVisible();

  const recommendationName = (await recommendationCard.locator('[data-product-name], .pap-product-copy h3').first().textContent())?.trim() || '';
  expect(recommendationName).not.toBe('');

  const recommendationButton = recommendationCard.locator('.pap-home-add-to-cart').first();
  await expect(recommendationButton).toBeVisible();

  await Promise.all([
    page.waitForResponse((response) => {
      return response.url().includes('admin-ajax.php')
        && response.request().method() === 'POST'
        && (response.request().postData() || '').includes('action=pap_home_add_to_cart')
        && response.ok();
    }),
    recommendationButton.click(),
  ]);

  await expect(page.locator('[data-pap-cart-count]').first()).toContainText('2 produse');
  const successModalClose = page.locator('[data-cart-modal-close]').first();
  if (await successModalClose.isVisible().catch(() => false)) {
    await successModalClose.click();
  }

  const drawerTrigger = page.locator('[data-cart-drawer-trigger]').first();
  await expect(drawerTrigger).toBeVisible();
  await drawerTrigger.click();

  const drawer = page.locator('[data-cart-drawer]');
  await expect(drawer).toBeVisible();

  await expect(drawer.locator('.pap-cart-drawer-item').first()).toBeVisible();

  const drawerItem = drawer.locator('.pap-cart-drawer-item').filter({ hasText: recommendationName }).first();
  await expect(drawerItem).toBeVisible();
  await expect(drawerItem.locator('.pap-cart-drawer-name')).toHaveText(recommendationName);
  await expect(drawerItem.locator('.pap-cart-drawer-line-total')).not.toHaveText('');
  await expect(drawerItem.locator('.pap-cart-drawer-line-total')).toContainText('lei');

  await page.screenshot({ path: 'cart-drawer-after-add.png', fullPage: true });

  await page.context().clearCookies();
  await page.goto('/?page_id=8');
  await page.locator('.pap-cart-empty-hero').waitFor({ state: 'visible' });
  await page.screenshot({ path: 'cart-empty-layout.png', fullPage: true });

  expect(errors).toEqual([]);
});

test('empty cart page updates immediately after adding a recommendation', async ({ page }) => {
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
  await page.goto('/?page_id=8');

  await expect(page.locator('.pap-cart-empty-hero')).toBeVisible();

  const recommendationCard = page.locator('.pap-featured .pap-product-card').first();
  await expect(recommendationCard).toBeVisible();

  const recommendationName = (await recommendationCard.locator('[data-product-name], .pap-product-copy h3').first().textContent())?.trim() || '';
  expect(recommendationName).not.toBe('');

  const recommendationButton = recommendationCard.locator('.pap-home-add-to-cart').first();
  await expect(recommendationButton).toBeVisible();

  await Promise.all([
    page.waitForResponse((response) => {
      return response.url().includes('admin-ajax.php')
        && response.request().method() === 'POST'
        && (response.request().postData() || '').includes('action=pap_home_add_to_cart')
        && response.ok();
    }),
    recommendationButton.click(),
  ]);

  await expect(page.locator('[data-pap-cart-count]').first()).toContainText('1 produs');
  await expect(page.locator('.pap-cart-empty-hero')).toHaveCount(0);
  await expect(page.locator('.pap-cart-items .pap-cart-item')).toHaveCount(1);
  await expect(page.locator('.pap-cart-summary')).toContainText('lei');

  expect(errors).toEqual([]);
});

test('removing the last drawer item switches to the empty cart layout immediately', async ({ page }) => {
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
  await page.goto('/?page_id=8');

  const recommendationCard = page.locator('.pap-featured .pap-product-card').first();
  await expect(recommendationCard).toBeVisible();

  const recommendationName = (await recommendationCard.locator('[data-product-name], .pap-product-copy h3').first().textContent())?.trim() || '';
  expect(recommendationName).not.toBe('');

  const recommendationButton = recommendationCard.locator('.pap-home-add-to-cart').first();
  await Promise.all([
    page.waitForResponse((response) => {
      return response.url().includes('admin-ajax.php')
        && response.request().method() === 'POST'
        && (response.request().postData() || '').includes('action=pap_home_add_to_cart')
        && response.ok();
    }),
    recommendationButton.click(),
  ]);

  const drawerTrigger = page.locator('[data-cart-drawer-trigger]').first();
  await expect(drawerTrigger).toBeVisible();
  await drawerTrigger.click();

  const drawer = page.locator('[data-cart-drawer]');
  await expect(drawer).toBeVisible();

  const removeButton = drawer.locator('[data-cart-remove-item]').first();
  await expect(removeButton).toBeVisible();
  await removeButton.click();

  const confirmButton = page.locator('[data-cart-delete-modal-confirm]').first();
  await expect(confirmButton).toBeVisible();

  await Promise.all([
    page.waitForResponse((response) => {
      return response.url().includes('admin-ajax.php')
        && response.request().method() === 'POST'
        && (response.request().postData() || '').includes('action=pap_cart_drawer_sync')
        && (response.request().postData() || '').includes('mode=remove')
        && response.ok();
    }),
    confirmButton.click(),
  ]);

  await expect(page.locator('.pap-cart-page--empty')).toBeVisible();
  await expect(page.locator('.pap-cart-empty-hero')).toBeVisible();
  await expect(page.locator('.pap-cart-empty-stack .pap-featured-slider-shell')).toBeVisible();
  await expect(page.locator('.pap-cart-form')).toHaveCount(0);
  await expect(page.locator('[data-pap-cart-count]').first()).toContainText('0 produse');

  expect(errors).toEqual([]);
});
