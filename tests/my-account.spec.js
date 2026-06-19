import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { expect, test } from '@playwright/test';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const fixturesPath = path.resolve(__dirname, '../wp-content/themes/papetarie-storefront/tools/my-account-fixtures.json');
const fixtures = JSON.parse(fs.readFileSync(fixturesPath, 'utf8'));
const myAccountUrl = '/?page_id=10';
const myAccountOrdersUrl = '/?page_id=10&orders';

function formatMoney(amount) {
  const value = Number(amount || 0);
  const fixed = value.toFixed(2);
  return fixed.endsWith('.00') ? fixed.slice(0, -3) : fixed;
}

function findOrderByNumber(number) {
  for (const user of Object.values(fixtures.users)) {
    if (!Array.isArray(user.orders)) {
      continue;
    }

    const match = user.orders.find((order) => order.number === number);
    if (match) {
      return match;
    }
  }

  return null;
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

async function openAuthModal(page) {
  await page.goto('/');
  await page.locator('[data-pap-auth-account]').first().click();
  await expect(page.locator('#pap-auth-modal')).toBeVisible();
}

async function loginAs(page, userKey) {
  const user = fixtures.users[userKey];
  await page.context().clearCookies();
  await openAuthModal(page);

  const modal = page.locator('#pap-auth-modal');
  const loginForm = modal.locator('form[data-auth-form="login"]').first();
  await loginForm.locator('input[name="username"]').first().fill(user.email);
  await loginForm.locator('input[name="password"]').first().fill(user.password);

  await Promise.all([
    page.waitForResponse((response) => {
      return response.url().includes('admin-ajax.php')
        && response.request().method() === 'POST'
        && (response.request().postData() || '').includes('action=pap_auth_login')
        && response.ok();
    }),
    loginForm.locator('button[type="submit"]').first().click(),
  ]);

  await expect(page.locator('[data-pap-auth-account]')).toContainText('Contul meu');
  await page.goto('/');
  await expect(page.locator('[data-pap-auth-account]')).toContainText('Contul meu');
}

async function goToDashboard(page, userKey) {
  await loginAs(page, userKey);
  await page.goto(myAccountUrl);
  await expect(page.locator('.pap-account-shell')).toBeVisible();
}

async function goToOrders(page, userKey) {
  await loginAs(page, userKey);
  await page.goto(myAccountOrdersUrl);
  await expect(page.locator('.pap-account-orders-table')).toBeVisible();
}

async function openFirstOrder(page) {
  const firstRow = page.locator('.pap-account-order-row').first();
  const orderNumber = (await firstRow.locator('.pap-account-order-row__cell--order strong').textContent() || '').trim();
  await firstRow.locator('a.pap-account-row-action').click();
  await expect(page.locator('.pap-account-view-order-head')).toBeVisible();
  return orderNumber;
}

async function openOrderByNumber(page, orderNumber) {
  const row = page.locator('.pap-account-order-row').filter({ hasText: orderNumber }).first();
  await row.locator('a.pap-account-row-action').click();
  await expect(page.locator('.pap-account-view-order-head')).toBeVisible();
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

    await openFirstOrder(page);
    await expect(page.locator('.pap-account-view-order-head')).toBeVisible();

    const viewOverflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
    expect(viewOverflow).toBe(false);
  }
});
