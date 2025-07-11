jQuery( function ( $ ) {
    if ( typeof nexiCheckoutParams === "undefined" ) {
        return false
    }

    /**
     * The main object.
     *
     * @type {Object} wcNexiCheckout
     */
    const wcNexiCheckout = {
        bodyEl: $( "body" ),
        paymentMethodEl: $( 'input[name="payment_method"]' ),
        nexiCheckout: null,
        checkoutFormSelector: "form.checkout",
        selectedGateway: "",
        log: ( ...args ) => {
            if ( nexiCheckoutParams.debug ) {
                console.log( ...args )
            }
        },

        /**
         * Initialize the gateway
         */
        init() {
            wcNexiCheckout.selectedGateway = $( 'input[name="payment_method"]:checked' ).val()
            $( document ).ready( () => {
                // When an update_order_review happens, WC will replace the payment methods fragment, resulting in the payment method element being replaced. Therefore, we have to listen on the body element.
                $( "body" ).on( "change", 'input[name="payment_method"]', ( e ) => {
                    // Do not cache the payment method element, as it will be replaced by a fragment.
                    const changedGateway = $( 'input[name="payment_method"]:checked' ).val()

                    // If neither the changed nor the previously selected gateway is Nexi Checkout, return.
                    if ( changedGateway !== "dibs_easy" && wcNexiCheckout.selectedGateway !== "dibs_easy" ) {
                        return
                    }

                    e.preventDefault()

                    // Updated the internal reference in case it was replaced by a fragment.
                    wcNexiCheckout.paymentMethodEl = $( e.target )
                    wcNexiCheckout.blockUI()

                    wcNexiCheckout.changeSelectedGateway( changedGateway === "dibs_easy" )

                    // In case the payment method change fails due to an AJAX error, we want to prevent WC from updating the chosen payment method. Instead, the chosen payment method should be set by the AJAX handler which only happens if the transition was successful.
                    wcNexiCheckout.unblockUI()
                } )

                wcNexiCheckout.loadNexi()

                // Update the Nexi Checkout when the checkout is updated.
                wcNexiCheckout.bodyEl.on( "update_checkout", wcNexiCheckout.updateCheckout )
                wcNexiCheckout.bodyEl.on( "updated_checkout", wcNexiCheckout.updatedCheckout )
            } )

            $( "#nexi-inline-close-modal" ).on( "click", () => {
                wcNexiCheckout.toggleInlineOverlay()
                wcNexiCheckout.unblockUI()
            } )

            $( "#dibs-easy-select-other" ).on( "click", ( e ) => {
                e.preventDefault()
                wcNexiCheckout.blockUI()
                wcNexiCheckout.changeSelectedGateway( false )
            } )
        },

        updateCheckout() {
            wcNexiCheckout.log( "update_checkout" )
            if ( window.Dibs !== undefined ) {
                wcNexiCheckout.blockUI()
                wcNexiCheckout.nexiCheckout.freezeCheckout()
            }
        },
        updatedCheckout() {
            wcNexiCheckout.log( "updated_checkout" )
            if ( window.Dibs !== undefined ) {
                wcNexiCheckout.nexiCheckout.thawCheckout()
                wcNexiCheckout.unblockUI()
            }
        },

        /**
         * Check if Nexi Checkout is the selected gateway.
         */
        isGatewaySelected() {
            if ( $( wcNexiCheckout.paymentMethodEl ).length > 0 ) {
                const selectedGateway = wcNexiCheckout.paymentMethodEl.filter( ":checked" ).val()
                if ( "dibs_easy" === selectedGateway ) {
                    return true
                }
            }
            return false
        },
        /**
         * Triggers on document ready.
         */
        loadNexi() {
            wcNexiCheckout.initNexiCheckout()
        },

        /**
         * Triggers after a successful payment.
         *
         * @param {Object} response
         */
        paymentCompleted( response ) {
            wcNexiCheckout.logToFile( `Payment completed is triggered with payment id: ${ response.paymentId }` )
            const redirectUrl = sessionStorage.getItem( "redirectNets" )
            if ( redirectUrl ) {
                window.location.href = redirectUrl
            }
        },

        /**
         * Triggers whenever customer updates address information from ApplePay window.
         *
         */
        applePayAddressChanged( address ) {
            wcNexiCheckout.log( "applepay-contact-updated", address )
            wcNexiCheckout.logToFile( "ApplePay address changed is triggered." )
            if ( address ) {
                wcNexiCheckout.log( "applepay-contact-updated" )
                $.ajax( {
                    type: "POST",
                    dataType: "json",
                    async: true,
                    url: nexiCheckoutParams.customer_address_updated_url,
                    data: {
                        action: "customer_address_updated",
                        address,
                        nonce: nexiCheckoutParams.nets_checkout_nonce,
                    },
                    success: ( response ) => {
                        wcNexiCheckout.log( "COMPLETED" )
                        wcNexiCheckout.log( "customer_address_updated " )
                        wcNexiCheckout.log( response.responseJSON.data )
                        wcNexiCheckout.updateAddress( response.responseJSON.data )
                        wcNexiCheckout.nexiCheckout.completeApplePayShippingContactUpdate(
                            response.responseJSON.data.cart_total,
                        )
                    },
                } )
            }
        },
        /**
         * Initializes a new checkout instance from Nexi.
         */
        initNexiCheckout() {
            // Constructs a new Checkout object.
            wcNexiCheckout.nexiCheckout = new Dibs.Checkout( {
                checkoutKey: nexiCheckoutParams.privateKey,
                paymentId: nexiCheckoutParams.paymentId,
                containerId: "dibs-complete-checkout",
                language: nexiCheckoutParams.locale,
            } )
            wcNexiCheckout.nexiCheckout.on( "payment-completed", wcNexiCheckout.paymentCompleted )
            wcNexiCheckout.nexiCheckout.on( "applepay-contact-updated", wcNexiCheckout.applePayAddressChanged )

            wcNexiCheckout.nexiCheckout.on( "pay-initialized", ( paymentId ) => {
                wcNexiCheckout.submitOrder()
                wcNexiCheckout.logToFile( "Pay initialized event is triggered." )
            } )
        },

        /**
         * Submit the order using the WooCommerce AJAX function.
         */
        submitOrder() {
            wcNexiCheckout.blockUI()

            $.ajax( {
                type: "POST",
                url: nexiCheckoutParams.submitOrder,
                data: $( "form.checkout" ).serialize(),
                dataType: "json",
                success( data ) {
                    try {
                        if ( "success" === data.result ) {
                            wcNexiCheckout.logToFile( "Successfully placed order." )
                            window.sessionStorage.setItem( "redirectNets", data.redirect )
                            wcNexiCheckout.nexiCheckout.send( "payment-order-finalized", true )
                            wcNexiCheckout.toggleInlineOverlay()
                        } else {
                            throw "Result failed"
                        }
                    } catch ( err ) {
                        if ( data.messages ) {
                            wcNexiCheckout.logToFile( "Checkout error | " + data.messages )
                            wcNexiCheckout.failOrder( "submission", data.messages )
                        } else {
                            wcNexiCheckout.logToFile( "Checkout error | No message" )
                            wcNexiCheckout.failOrder(
                                "submission",
                                '<div class="woocommerce-error">' + "Checkout error" + "</div>",
                            )
                        }
                    }
                },
                error( data ) {
                    try {
                        wcNexiCheckout.logToFile( "AJAX error | " + JSON.stringify( data ) )
                    } catch ( e ) {
                        wcNexiCheckout.logToFile( "AJAX error | Failed to parse error message." )
                    }
                    wcNexiCheckout.failOrder(
                        "ajax-error",
                        '<div class="woocommerce-error">Internal Server Error</div>',
                    )

                    wcNexiCheckout.unblockUI()
                },
            } )
        },
        /**
         * When the customer changes to or from Nexi Checkout.
         *
         * @param {boolean} toNexi - True if changing to Nexi, false if changing to different gateway.
         */
        changeSelectedGateway( toNexi ) {
            $.ajax( {
                type: "POST",
                dataType: "json",
                async: true,
                url: nexiCheckoutParams.changePaymentMethodURL,
                data: {
                    action: "dibs_change_payment_method",
                    dibs_easy: toNexi,
                    nonce: nexiCheckoutParams.nonce,
                },
                complete( data ) {
                    if ( data.responseJSON.success ) {
                        wcNexiCheckout.log( "Change payment method success" )
                        wcNexiCheckout.log( data.responseJSON.data.redirect )
                        window.location.href = data.responseJSON.data.redirect
                    } else {
                        wcNexiCheckout.log( "Change payment method failed", data.responseJSON.data.redirect )
                    }
                },
            } )
        },

        /**
         * Logs the message to the Nexi Checkout log in WooCommerce.
         *
         * @param {string} message
         */
        logToFile( message ) {
            wcNexiCheckout.log( message )
            $.ajax( {
                url: nexiCheckoutParams.logToFileURL,
                type: "POST",
                dataType: "json",
                data: {
                    message,
                    nonce: nexiCheckoutParams.logToFileNonce,
                },
            } )
        },
        /**
         * Unblocks the UI.
         * @returns {void}
         */
        unblockUI: () => {
            $( ".woocommerce-checkout-review-order-table" ).unblock()
            $( "#customer_details" ).removeClass( "processing" ).unblock()
        },

        /**
         * Blocks the UI.
         * @returns {void}
         */
        blockUI: () => {
            /* Order review. */
            $( ".woocommerce-checkout-review-order-table" ).block( {
                message: null,
                overlayCSS: {
                    background: "#fff",
                    opacity: 0.6,
                },
            } )

            // form.checkout will block the inlined Nexi payment form.
            $( "#customer_details" ).addClass( "processing" )
            $( "#customer_details" ).block( {
                message: null,
                overlayCSS: {
                    background: "#fff",
                    opacity: 0.6,
                },
            } )
        },

        /**
         * Fails the order with Nexi Checkout on a checkout error and timeout.
         *
         * @param {string} event
         * @param {string} errorMessage
         */
        failOrder( event, errorMessage ) {
            const errorClasses = "woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout"
            const errorWrapper = `<div class="${ errorClasses }">${ errorMessage }</div>`

            // Send false and cancel
            wcNexiCheckout.nexiCheckout.send( "payment-order-finalized", false )
            // Reenable the form.
            wcNexiCheckout.bodyEl.trigger( "updated_checkout" )
            $( wcNexiCheckout.checkoutFormSelector ).removeClass( "processing" )
            wcNexiCheckout.unblockUI()

            // Print error messages, and trigger checkout_error, and scroll to notices.
            $( ".woocommerce-NoticeGroup-checkout," + ".woocommerce-error," + ".woocommerce-message" ).remove()

            $( wcNexiCheckout.checkoutFormSelector ).prepend( errorWrapper )
            // $( wcNexiCheckout.checkoutFormSelector )
            // 	.removeClass( 'processing' )
            // 	.unblock();
            $( wcNexiCheckout.checkoutFormSelector )
                .find( ".input-text, select, input:checkbox" )
                .trigger( "validate" )
                .blur()
            $( document.body ).trigger( "checkout_error", [ errorMessage ] )
            $( "html, body" ).animate(
                {
                    scrollTop: $( wcNexiCheckout.checkoutFormSelector ).offset().top - 100,
                },
                1000,
            )
        },

        toggleInlineOverlay: () => {
            $( "#nexi-inline-modal" ).toggleClass( "netseasy-modal" )
            $( "#nexi-inline-modal-box" ).toggleClass( "netseasy-modal-box" )
        },
    }

    wcNexiCheckout.init()
} )
