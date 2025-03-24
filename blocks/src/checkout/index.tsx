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

// Loop each key in settings and register the payment method with the key as the name
Object.keys(settings).forEach((key) => {

  const { enabled, title, description, icon, buttonLabel, features } = settings[key];

  const options = {
    name: key,
    label: <Label title={title} icon={icon} />,
    content: <NetsEasyCheckout description={description} />,
    edit: <NetsEasyCheckout description={description} />,
    placeOrderButtonLabel: buttonLabel,
    canMakePayment: () => enabled,
    ariaLabel: title,
    supports: {
      features
    }
  };

  registerPaymentMethod(options);
});
