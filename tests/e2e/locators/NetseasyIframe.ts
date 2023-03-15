import { expect, FrameLocator, Locator, Page } from '@playwright/test';

export class NetseasyIframe {
    readonly page: Page;

    readonly iframe: FrameLocator;

    readonly emailInput: Locator;
    readonly postalCodeInput: Locator;
    readonly phonenumberInput: Locator;

    readonly placeOrderButton: Locator;
    readonly approvePaymentButton: Locator;

    constructor(page: Page) {
        this.page = page;

        this.iframe = page.frameLocator('#nets-checkout-iframe');

        this.emailInput = this.iframe.locator('input#registrationManualEmail');
        this.postalCodeInput = this.iframe.locator('input#registrationManualPostalCode');
        this.phonenumberInput = this.iframe.locator('input#swishPhoneNumber');

        this.placeOrderButton = this.iframe.locator('button#btnPay');
        this.approvePaymentButton = this.iframe.locator('button#AuthenticationSuccessButton');
    }

    async fillEmail(email: string = 'test@krokedil.se') {
        await this.emailInput.fill(email);
    }
    async fillPostalCode(postalcode: string = '12222') {
        await this.postalCodeInput.fill(postalcode);
    }
    async fillPhonenumber(phonenumber: string = '812345678') {
        await this.phonenumberInput.fill(phonenumber);
    }
    async placeOrder() {
        await this.placeOrderButton.click();
    }
    async approvePayment() {
        await this.approvePaymentButton.click();
    }

    async fillAndSubmit() {
        await this.fillEmail();
        await this.fillPostalCode();
        await this.fillPhonenumber();

        await this.placeOrder();
        //await this.approvePayment();
    }
}