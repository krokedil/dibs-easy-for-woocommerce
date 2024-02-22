/**
 * External dependencies
 */
import * as React from "react";

/**
 * Wordpress/WooCommerce dependencies
 */
import { decodeEntities } from "@wordpress/html-entities";
// @ts-ignore - Cant avoid this issue, but its loaded in by Webpack
import { registerPaymentMethod } from "@woocommerce/blocks-registry";
// @ts-ignore - Cant avoid this issue, but its loaded in by Webpack
import { getSetting } from "@woocommerce/settings";
import { Label, NetsEasyCheckout } from "../shared/nets-easy-checkout";

const settings: any = getSetting("nets_easy_data", {});
const title: string = decodeEntities(settings.title || "Nets Easy");

// Loop each key in settings and register the payment method with the key as the name
Object.keys(settings).forEach((key) => {
  const setting = settings[key];

  const options = {
    name: key,
    label: <Label title={setting.title} />,
    content: <NetsEasyCheckout description={setting.description} />,
    edit: <NetsEasyCheckout description={setting.description} />,
    placeOrderButtonLabel: `Pay with ${setting.title}`,
    canMakePayment: () => setting.enabled,
    ariaLabel: title,
  };

  registerPaymentMethod(options);
});
