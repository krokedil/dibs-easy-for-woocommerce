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
        selectAnotherSelector: "#dibs-easy-select-other",
        checkoutFormSelector: "form.checkout",

        /**
         * Initialize the gateway
         */
        init() {
            $( document ).ready( wcNexiCheckout.loadDibs )
            wcNexiCheckout.bodyEl.on(
                "click",
                wcNexiCheckout.selectAnotherSelector,
                wcNexiCheckout.changeFromDibsEasy,
            )

            $('#nexi-inline-close-modal').on('click', () => { 
                wcNexiCheckout.toggleInlineOverlay()
                wcNexiCheckout.unblockUI()
            })
        },

        /**
         * Check if DIBS Easy is the selected gateway.
         */
        dibsIsSelected() {
            if ( wcNexiCheckout.paymentMethodEl.length > 0 ) {
                wcNexiCheckout.paymentMethod = wcNexiCheckout.paymentMethodEl.filter( ":checked" ).val()
                if ( "dibs_easy" === wcNexiCheckout.paymentMethod ) {
                    return true
                }
            }
            return false
        },
        /**
         * Triggers on document ready.
         */
        loadDibs() {
            wcNexiCheckout.initNexiCheckout()
        },

        /**
         * Triggers after a successful payment.
         *
         * @param {Object} response
         */
        paymentCompleted( response ) {
            wcNexiCheckout.logToFile(
                `Payment completed is triggered with payment id: ${ response.paymentId }`,
            )
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
            console.log( "applepay-contact-updated", address )
            wcNexiCheckout.logToFile( "ApplePay address changed is triggered." )
            if ( address ) {
                console.log( "applepay-contact-updated" )
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
                    success( response ) {},
                    error( response ) {},
                    complete( response ) {
                        console.log( "COMPLETED" )
                        console.log( "customer_address_updated " )
                        console.log( response.responseJSON.data )
                        wcNexiCheckout.updateAddress( response.responseJSON.data )
                        wcNexiCheckout.nexiCheckout.completeApplePayShippingContactUpdate(
                            response.responseJSON.data.cart_total,
                        )
                    },
                } )
            }
        },
        /**
         * Init Dibs Easy Checkout
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
            wcNexiCheckout.nexiCheckout.on(
                "applepay-contact-updated",
                wcNexiCheckout.applePayAddressChanged,
            )

            wcNexiCheckout.nexiCheckout.on("pay-initialized", (paymentId) => {
                wcNexiCheckout.submitOrder()
                wcNexiCheckout.logToFile("Pay initialized event is triggered.")
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
                success(data) {

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
         * When the customer changes from Dibs Easy to other payment methods.
         *
         * @param {Event} e
         */
        changeFromDibsEasy( e ) {
            e.preventDefault()
            $( wcNexiCheckout.checkoutFormSelector ).block( {
                message: null,
                overlayCSS: {
                    background: "#fff",
                    opacity: 0.6,
                },
            } )
            $.ajax( {
                type: "POST",
                dataType: "json",
                async: true,
                url: nexiCheckoutParams.change_payment_method_url,
                data: {
                    action: "dibs_change_payment_method",
                    dibs_easy: false,
                    nonce: nexiCheckoutParams.nets_checkout_nonce,
                },
                success( data ) {},
                error( data ) {},
                complete( data ) {
                    console.log( "Change payment method success" )
                    console.log( data.responseJSON.data.redirect )
                    wcNexiCheckout.bodyEl.removeClass( "dibs-selected" )
                    window.location.href = data.responseJSON.data.redirect
                },
            } )
        },

        /**
         * Logs the message to the Dibs Easy log in WooCommerce.
         *
         * @param {string} message
         */
        logToFile( message ) {
            $.ajax( {
                url: nexiCheckoutParams.log_to_file_url,
                type: "POST",
                dataType: "json",
                data: {
                    message,
                    nonce: nexiCheckoutParams.log_to_file_nonce,
                },
            } )
        },
        /**
         * Unblocks the UI.
         * @returns {void}
         */
        unblockUI: () => {
            $( ".woocommerce-checkout-review-order-table" ).unblock()
            $("#customer_details").removeClass("processing").unblock()
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
            })
        },

        /**
         * Fails the order with Dibs Easy on a checkout error and timeout.
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
            $(wcNexiCheckout.checkoutFormSelector).removeClass("processing")
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
            $('#nexi-inline-modal').toggleClass('netseasy-modal')
            $('#nexi-inline-modal-box').toggleClass('netseasy-modal-box')
        }
    }

    wcNexiCheckout.init()
} )
