jQuery( function ( $ ) {
    if ( typeof nexiCheckoutAdminParams === "undefined" ) {
        return false
    }

    /**
     * The main object.
     *
     * @type {Object} nexiCheckoutAdmin
     */
    const nexiCheckoutAdmin = {
        bodyEl: $( "body" ),

        /**
         * Initialize the admin gateway logic
         */
        init() {
            $( window ).on( "load", function () {
                nexiCheckoutAdmin.waitForElement(
                    ".woocommerce-list__item-enter-done",
                    nexiCheckoutAdmin.updateNexiGateways,
                )
            } )
        },

        /**
         * Update Nexi gateways labels and logos
         */
        updateNexiGateways() {
            const gateways = nexiCheckoutAdminParams.gateways
            for ( const gatewayId in gateways ) {
                const $gateway = $( `#${ gatewayId }` )
                if ( $gateway.length !== 1 ) {
                    continue
                }
                // Update the label
                $gateway
                    .find( ".woocommerce-list__item-title" )
                    .contents()
                    .filter( ( _, node ) => node.nodeType === 3 && node.textContent === "Nexi Checkout" )
                    .replaceWith( gateways[ gatewayId ].label )
                // Update the logo
                $gateway
                    .find( ".woocommerce-list__item-image" )
                    .attr( "src", gateways[ gatewayId ]?.logo )
                    .attr( "onerror", `this.onerror=null;this.src='${ gateways.dibs_easy.logo }';` )
            }
        },

        /**
         * Wait for an element to appear in the DOM, then run callback
         */
        waitForElement( selector, callback ) {
            if ( $( selector ).length ) {
                callback()
                return
            }
            const observer = new MutationObserver( () => {
                if ( $( selector ).length ) {
                    observer.disconnect()
                    callback()
                }
            } )
            observer.observe( document.body, { childList: true, subtree: true } )
        },
    }

    nexiCheckoutAdmin.init()
} )
