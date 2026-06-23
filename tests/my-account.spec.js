import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { expect, test } from '@playwright/test';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const fixturesPath = path.resolve(__dirname, '../wp-content/themes/papetarie-storefront/tools/my-account-fixtures.json');
const fixtures = JSON.parse(fs.readFileSync(fixturesPath, 'utf8'));
const myAccountUrl = '/?page_id=10';
const myAccountOrdersUrl = '/?page_id=10&orders';
const myAccountAddressesUrl = '/?page_id=10&edit-address';
const myAccountEditAccountUrl = '/?page_id=10&edit-account';
const myAccountFavoritesUrl = '/?page_id=10&favorite';
const qaAddressBookUser = {
  email: 'qa.empty.account@example.com',
  password: 'Test1234!qa',
};

function formatMoney(amount) {
  const value = Number(amount || 0);
  const fixed = value.toFixed(2);
  return fixed.endsWith('.00') ? fixed.slice(0, -3) : fixed;
}

function statusLabelFor(status) {
  switch (status) {
    case 'completed':
      return 'Livrată';
    case 'processing':
      return 'Procesare';
    case 'pending':
      return 'În așteptare';
    case 'cancelled':
      return 'Anulată';
    default:
      return status;
  }
}

async function loginAs(page, userKey) {
  const user = fixtures.users[userKey];
  await page.context().clearCookies();
  await page.goto('/wp-login.php');
  await page.locator('#user_login').fill(user.email);
  await page.locator('#user_pass').fill(user.password);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded' }).catch(() => {}),
    page.locator('#wp-submit').click(),
  ]);
  await page.waitForLoadState('networkidle');
  await page.goto(myAccountUrl);
  await expect(page.locator('.pap-account-shell')).toBeVisible({ timeout: 30_000 });
}

async function goToDashboard(page, userKey) {
  await loginAs(page, userKey);
}

async function goToOrders(page, userKey) {
  await loginAs(page, userKey);
  await page.goto(myAccountOrdersUrl);
  await expect(page.locator('.pap-account-orders-table')).toBeVisible();
}

async function loginForAddressBook(page) {
  await page.context().clearCookies();
  await page.goto('/wp-login.php');
  await page.locator('#user_login').fill(qaAddressBookUser.email);
  await page.locator('#user_pass').fill(qaAddressBookUser.password);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded' }).catch(() => {}),
    page.locator('#wp-submit').click(),
  ]);
  await page.waitForLoadState('networkidle');
  await page.goto('/my-account/edit-address/?pap_address_action=add');
  await expect(page.locator('#pap-account-content h1').first()).toHaveText('Adrese', { timeout: 30_000 });
}

test('dashboard shows empty state and zero favorite count for a user without orders', async ({ page }) => {
  await goToDashboard(page, 'empty');

  await expect(page.getByRole('heading', { name: /Bun venit, QA Empty!/ })).toBeVisible();
  await expect(page.getByText('Nu ai comenzi încă.')).toBeVisible();
  await expect(page.locator('.pap-account-help-card')).toHaveCount(0);
  await expect(page.locator('.pap-account-stat-card').filter({ hasText: 'Favorite' })).toContainText('0');
  await expect(page.locator('.pap-account-stat-card').filter({ hasText: 'Comenzi' })).toContainText('0');
});

test('dashboard renders the latest orders for a user with history', async ({ page }) => {
  const user = fixtures.users.five;
  await goToDashboard(page, 'five');

  await expect(page.getByRole('heading', { name: /Bun venit, QA Five!/ })).toBeVisible();
  await expect(page.locator('.pap-account-order-row')).toHaveCount(4);
  await expect(page.locator('.pap-account-order-row').first().locator('.pap-account-order-row__cell--order strong')).toHaveText(/^#SH-\d+$/);
  expect(await page.locator('.pap-account-order-row').filter({ hasText: 'Livrată' }).count()).toBeGreaterThan(0);
  expect(await page.locator('.pap-account-order-row').filter({ hasText: 'Procesare' }).count()).toBeGreaterThan(0);
  expect(await page.locator('.pap-account-order-row').filter({ hasText: 'În așteptare' }).count()).toBeGreaterThan(0);
  expect(await page.locator('.pap-account-order-row').filter({ hasText: 'Anulată' }).count()).toBeGreaterThan(0);
  await expect(page.locator('.pap-account-stat-card').filter({ hasText: 'Comenzi' })).toContainText('5');
});

test('orders list supports 1, 5 and 20 order scenarios with status badges and pagination', async ({ page }) => {
  await goToOrders(page, 'one');
  await expect(page.locator('.pap-account-order-row')).toHaveCount(1);
  await expect(page.locator('.pap-account-pagination')).toHaveCount(0);
  await expect(page.locator('.pap-account-order-row').first().locator('.pap-account-order-row__cell--order strong')).toHaveText(/^#SH-\d+$/);
  await expect(page.locator('.pap-account-order-row').first()).toContainText('Livrată');

  await goToOrders(page, 'five');
  await expect(page.locator('.pap-account-order-row')).toHaveCount(5);
  expect(await page.locator('.pap-account-order-row').filter({ hasText: 'Livrată' }).count()).toBeGreaterThan(0);
  expect(await page.locator('.pap-account-order-row').filter({ hasText: 'Procesare' }).count()).toBeGreaterThan(0);
  expect(await page.locator('.pap-account-order-row').filter({ hasText: 'În așteptare' }).count()).toBeGreaterThan(0);
  expect(await page.locator('.pap-account-order-row').filter({ hasText: 'Anulată' }).count()).toBeGreaterThan(0);
  await expect(page.locator('.pap-account-pagination')).toHaveCount(0);

  await goToOrders(page, 'twenty');
  await expect(page.locator('.pap-account-order-row')).toHaveCount(5);
  expect(await page.locator('.pap-account-pagination__button').count()).toBeGreaterThanOrEqual(4);
  await expect(page.locator('.pap-account-order-row').first().locator('.pap-account-order-row__cell--order strong')).toHaveText(/^#SH-\d+$/);
  expect(await page.locator('.pap-account-order-row').filter({ hasText: 'Livrată' }).count()).toBeGreaterThan(0);
  expect(await page.locator('.pap-account-order-row').filter({ hasText: 'Procesare' }).count()).toBeGreaterThan(0);
  expect(await page.locator('.pap-account-order-row').filter({ hasText: 'În așteptare' }).count()).toBeGreaterThan(0);
  expect(await page.locator('.pap-account-order-row').filter({ hasText: 'Anulată' }).count()).toBeGreaterThan(0);

  const pageOneFirstOrder = await page.locator('.pap-account-order-row').first().locator('.pap-account-order-row__cell--order strong').textContent();
  await page.locator('.pap-account-pagination__button', { hasText: '2' }).click();
  const pageTwoFirstOrder = await page.locator('.pap-account-order-row').first().locator('.pap-account-order-row__cell--order strong').textContent();
  expect(pageTwoFirstOrder).not.toBe(pageOneFirstOrder);
});

test('favorite, address and edit account states stay consistent', async ({ page }) => {
  await loginAs(page, 'empty');

  await page.goto(myAccountFavoritesUrl);
  await expect(page.locator('.pap-account-empty-state')).toBeVisible();
  await expect(page.locator('.pap-account-empty-state')).toContainText('Nu ai produse salvate momentan.');

  await page.goto(myAccountAddressesUrl);
  await expect(page.locator('.pap-account-page-head h1')).toHaveText('Adrese');
  await expect(page.locator('.pap-account-panel')).toBeVisible();

  await page.goto(myAccountEditAccountUrl);
  await expect(page.locator('form.pap-account-form')).toBeVisible();
  await expect(page.locator('input#account_first_name')).toBeVisible();
  await expect(page.locator('input#account_email')).toBeVisible();
  await expect(page.locator('input[name="password_1"]')).toBeVisible();
});

test('favorite and address pages render seeded data when available', async ({ page }) => {
  await loginAs(page, 'twenty');

  await page.goto(myAccountFavoritesUrl);
  const favoriteCards = page.locator('.pap-product-card');
  await expect(favoriteCards.first()).toBeVisible();
  expect(await favoriteCards.count()).toBeGreaterThan(0);

  await page.goto(myAccountAddressesUrl);
  const addressCards = page.locator('.pap-account-address-card');
  await expect(addressCards.first()).toBeVisible();
  await expect(page.locator('.pap-account-address-card')).toHaveCount(2);
  await expect(page.locator('.pap-account-address-card').first()).toContainText('Adresă de facturare');
});

test('view order matches the seeded WooCommerce totals and payment metadata', async ({ page }) => {
  await goToOrders(page, 'twenty');
  const order = fixtures.users.twenty.orders.find((item) => item.shipping_total > 0);
  expect(order).not.toBeNull();
  const subtotal = order.total - order.shipping_total - order.tax_total;

  await page.goto(`/?page_id=10&view-order=${order.id}`);

  await expect(page.getByRole('heading', { name: new RegExp(order.number.replace('#', '\\#')) })).toBeVisible();
  await expect(page.getByText('Plasată pe')).toBeVisible();
  await expect(page.getByText(statusLabelFor(order.status))).toBeVisible();
  await expect(page.getByText('Curier rapid')).toBeVisible();
  await expect(page.getByText('Fan Courier')).toBeVisible();
  await expect(page.locator('.pap-account-order-meta-card').nth(1)).toContainText('Online cu cardul');
  await expect(page.locator('.pap-account-order-meta-card').nth(1)).toContainText('4242');
  await expect(page.locator('.pap-account-order-item')).toHaveCount(3);
  await expect(page.locator('.pap-account-order-items-table__head')).toContainText('Cantitate');
  await expect(page.locator('.pap-account-totals')).toContainText(`${formatMoney(subtotal)} lei`);
  await expect(page.locator('.pap-account-totals')).toContainText(`${formatMoney(order.shipping_total)} lei`);
  await expect(page.locator('.pap-account-totals')).toContainText(`${formatMoney(order.tax_total)} lei`);
  await expect(page.locator('.pap-account-totals')).toContainText(`${formatMoney(order.total)} lei`);
});

test('my account layout stays intact across the requested breakpoints', async ({ page }) => {
  await loginAs(page, 'twenty');

  const widths = [390, 768, 1024, 1440, 1920];

  for (const width of widths) {
    await page.setViewportSize({ width, height: 1200 });

    await page.goto(myAccountUrl);
    await expect(page.locator('.pap-account-shell')).toBeVisible();
    await expect(page.locator('.pap-account-nav')).toBeVisible();
    await expect(page.locator('.pap-account-page-head h1')).toBeVisible();

    const overflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
    expect(overflow).toBe(false);

    await page.goto(myAccountOrdersUrl);
    await expect(page.locator('.pap-account-orders-table')).toBeVisible();

    const ordersOverflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
    expect(ordersOverflow).toBe(false);
  }
});

test('logout link clears the account state', async ({ page }) => {
  await loginAs(page, 'one');
  await page.goto(myAccountUrl);
  await expect(page.locator('.pap-account-shell')).toBeVisible();

  const logoutLink = page.locator('a[href*="customer-logout"]').first();
  await expect(logoutLink).toBeVisible();
  await logoutLink.click();
  await page.waitForLoadState('networkidle');

  await expect(page.locator('body')).not.toContainText('Contul meu');
});

test('address form enables city only after county selection and loads county localities', async ({ page }) => {
  await loginForAddressBook(page);

  const stateField = page.locator('[data-address-book-state]');
  const cityField = page.locator('[data-address-book-city]');

  await expect(stateField).toBeVisible();
  await expect(cityField).toBeVisible();
  await expect(cityField).toBeDisabled();
  await expect(cityField).toContainText('Alege județul întâi');

  await stateField.selectOption('CJ');
  await expect(cityField).toBeEnabled();
  await expect(cityField).toContainText('Alege localitatea');
  await expect(cityField).toContainText('Cluj-Napoca');
  await expect(cityField).toContainText('Turda');
});
