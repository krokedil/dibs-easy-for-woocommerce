import { test, expect, APIRequestContext } from '@playwright/test';
import { NetseasyIframe } from '../locators/NetseasyIframe';
import { GetWcApiClient, WcPages } from '@krokedil/wc-test-helper';
import { SetNetseasySettings } from '../utils/utils';

const {
    BASE_URL,
    CONSUMER_KEY,
    CONSUMER_SECRET,
} = process.env;

test.describe('Guest Checkout @shortcode', () => {
    test.use({ storageState: process.env.GUESTSTATE });

    let wcApiClient: APIRequestContext;

    const paymentMethodId = 'nets_easy';

    let orderId: string;

    test.beforeAll(async () => {
        wcApiClient = await GetWcApiClient(BASE_URL ?? 'http://localhost:8080', CONSUMER_KEY ?? 'admin', CONSUMER_SECRET ?? 'password');
    });

    test.afterEach(async () => {
        // Delete the order from WooCommerce.
        await wcApiClient.delete(`orders/${orderId}`);
    });

    test('Can buy 6x 99.99 products with 25% tax.', async ({ page }) => {
        const cartPage = new WcPages.Cart(page, wcApiClient);
        const orderRecievedPage = new WcPages.OrderReceived(page, wcApiClient);
        const checkoutPage = new WcPages.Checkout(page);
        const iframe = new NetseasyIframe(page);

        // Add products to the cart.
        await cartPage.addtoCart(['simple-25', 'simple-25', 'simple-25', 'simple-25', 'simple-25', 'simple-25']);

        // Go to the checkout page.
        await checkoutPage.goto();

        // Process the Qliro iFrame
        await iframe.fillAndSubmit();

        // Verify that the order was placed.
        await expect(page).toHaveURL(/order-received/);

        orderId = await orderRecievedPage.getOrderId();

        // Verify the order details.
        //await VerifyOrderRecieved(orderRecievedPage);
    });
});
