// Delay until all elements are loaded, otherwise the gateways won't be available yet.
jQuery(window).on('load', function () {
    if (typeof nexiCheckoutAdminParams == 'undefined') { 
        return;
    }

    const gateways = nexiCheckoutAdminParams.gateways;
    for (const gatewayId in gateways) { 
        const $gateway = jQuery(`#${gatewayId}`);
        if ($gateway.length !== 1 ) {
            continue;
        }

        $gateway.find('.woocommerce-list__item-title')
            .contents()
            // Preserve any HTML formatting by only replacing the text node.
            .filter((_, node) => node.nodeType === 3 && node.textContent === 'Nexi Checkout')
            .replaceWith(gateways[gatewayId].label);

        $gateway.find('.woocommerce-list__item-image').attr('src', gateways[gatewayId].logo);
    }
 });
