jQuery( function ( $ ) {
    if ( typeof nexiExpressParams === "undefined" ) {
        return false
    }

    /**
     * The main object.
     *
     * @type {Object} NexiExpress
     */
    const NexiExpress = {
        easy: null,
        widget: null,
        paymentId: null,
        currentVariationId: 0,

        log: ( ...args ) => {
            if ( nexiExpressParams.debug ) {
                console.log( ...args )
            }
        },

        /**
         * Entry point.
         */
        init() {
            if ( nexiExpressParams.isCheckoutFinalize ) {
                NexiExpress.finalizeCheckout()
                return
            }

            if ( nexiExpressParams.isProductPage ) {
                NexiExpress.initProductButton()
            }

            if ( nexiExpressParams.isCartPage ) {
                NexiExpress.initCartButton()
            }
        },

        /* ------------------------------------------------------------------ */
        /* Product page                                                       */
        /* ------------------------------------------------------------------ */

        initProductButton() {
            const $container = $( "#nexi-express-button-product" )
            if ( ! $container.length ) {
                return
            }

            const $form = $( "form.cart" )
            if ( ! $form.hasClass( "variations_form" ) ) {
                NexiExpress.renderButton( "product", $container )
                return
            }

            // Variable product: withhold the button until a valid, purchasable, in-stock
            // variation is selected, mirroring how WooCommerce core's own variation form works.
            $form.on( "found_variation", ( e, variation ) => {
                if ( variation.is_in_stock && variation.is_purchasable ) {
                    NexiExpress.currentVariationId = variation.variation_id
                    NexiExpress.renderButton( "product", $container )
                }
            } )

            $form.on( "reset_data woocommerce_variation_has_changed hide_variation", () => {
                NexiExpress.currentVariationId = 0
                NexiExpress.teardownButton( $container )
            } )
        },

        /* ------------------------------------------------------------------ */
        /* Cart page                                                          */
        /* ------------------------------------------------------------------ */

        initCartButton() {
            const $container = $( "#nexi-express-button-cart" )
            if ( ! $container.length ) {
                return
            }

            NexiExpress.renderButton( "cart", $container )

            // Re-create the session whenever the cart totals change (qty steppers, coupon apply).
            $( document.body ).on( "updated_cart_totals", () => {
                NexiExpress.renderButton( "cart", $container )
            } )
        },

        /* ------------------------------------------------------------------ */
        /* Shared render/create                                               */
        /* ------------------------------------------------------------------ */

        renderButton( context, $container ) {
            const payload = {
                nonce: nexiExpressParams.nonce,
                context,
            }

            if ( "product" === context ) {
                payload.product_id = $container.data( "product-id" )
                payload.variation_id = NexiExpress.currentVariationId || 0
                payload.quantity = parseInt( $( "form.cart input.qty" ).val(), 10 ) || 1
            }

            $.post( nexiExpressParams.createSessionUrl, payload )
                .done( ( response ) => {
                    if ( ! response.success ) {
                        NexiExpress.log( "nexi_express_create_session failed", response )
                        NexiExpress.teardownButton( $container )
                        return
                    }

                    NexiExpress.paymentId = response.data.paymentId
                    NexiExpress.teardownButton( $container )

                    NexiExpress.easy = Easy( {
                        checkoutKey: nexiExpressParams.checkoutKey,
                        language: nexiExpressParams.locale,
                    } )
                    NexiExpress.widget = NexiExpress.easy.renderExpress( {
                        paymentId: NexiExpress.paymentId,
                        containerId: $container.attr( "id" ),
                    } )

                    NexiExpress.widget.on( "shippingaddresschange", ( payload ) => {
                        NexiExpress.onShippingAddressChange( payload )
                    } )

                    NexiExpress.widget.on( "paymentcompleted", () => {
                        NexiExpress.onPaymentCompleted()
                    } )
                } )
                .fail( ( error ) => {
                    NexiExpress.log( "nexi_express_create_session AJAX error", error )
                } )
        },

        teardownButton( $container ) {
            $container.empty()
            NexiExpress.widget = null
        },

        onShippingAddressChange( payload ) {
            const shippingAddress = ( payload && payload.shippingAddress ) || {}

            $.post( nexiExpressParams.shippingChangeUrl, {
                nonce: nexiExpressParams.nonce,
                payment_id: NexiExpress.paymentId,
                country: shippingAddress.country,
                postal_code: shippingAddress.postalCode,
            } ).always( () => {
                if ( NexiExpress.widget ) {
                    NexiExpress.widget.update()
                }
            } )
        },

        onPaymentCompleted() {
            $.post( nexiExpressParams.paymentCompleteUrl, {
                nonce: nexiExpressParams.nonce,
                payment_id: NexiExpress.paymentId,
            } ).done( ( response ) => {
                if ( response.success ) {
                    window.location.href = response.data.redirect
                } else {
                    NexiExpress.log( "nexi_express_payment_completed failed", response )
                }
            } )
        },

        /* ------------------------------------------------------------------ */
        /* Checkout page finalize                                             */
        /* ------------------------------------------------------------------ */

        finalizeCheckout() {
            $.ajax( {
                type: "POST",
                dataType: "json",
                url: nexiExpressParams.getOrderDataUrl,
                data: {
                    action: "get_order_data",
                    paymentId: nexiExpressParams.paymentId,
                    nonce: nexiExpressParams.checkoutNonce,
                },
                success: ( data ) => {
                    if ( false === data.success ) {
                        NexiExpress.log( "get_order_data failed", data )
                        if ( data.data && data.data.redirect ) {
                            window.location.href = data.data.redirect
                        }
                        return
                    }

                    NexiExpress.populateCheckoutFields( data.data.payment.consumer )
                    NexiExpress.submitCheckoutForm()
                },
                error: ( error ) => {
                    NexiExpress.log( "get_order_data AJAX error", error )
                },
            } )
        },

        /**
         * Maps a completed Nexi order's consumer/shipping data onto the real WooCommerce checkout
         * form fields, mirroring nets-easy-for-woocommerce.js's setAddressData().
         *
         * @param {Object} consumer
         */
        populateCheckoutFields( consumer ) {
            if ( ! consumer ) {
                return
            }

            $( 'input[name="payment_method"][value="dibs_easy"]' ).prop( "checked", true )

            const shippingAddress = consumer.shippingAddress || {}
            const billingAddress = consumer.billingAddress || shippingAddress
            const person = consumer.privatePerson || ( consumer.company && consumer.company.contact ) || {}

            $( "#billing_address_1" ).val( billingAddress.addressLine1 || "" )
            $( "#billing_postcode" ).val( billingAddress.postalCode || "" )
            $( "#billing_city" ).val( billingAddress.city || "" )
            $( "#billing_country" ).val( shippingAddress.country || "" )

            $( "#shipping_address_1" ).val( shippingAddress.addressLine1 || "" )
            $( "#shipping_postcode" ).val( shippingAddress.postalCode || "" )
            $( "#shipping_city" ).val( shippingAddress.city || "" )
            $( "#shipping_country" ).val( shippingAddress.country || "" )

            $( "#billing_first_name" ).val( person.firstName || "" )
            $( "#billing_last_name" ).val( person.lastName || "" )
            $( "#shipping_first_name" ).val( person.firstName || "" )
            $( "#shipping_last_name" ).val( person.lastName || "" )

            if ( consumer.email ) {
                $( "#billing_email" ).val( consumer.email )
            }

            const phone = shippingAddress.phoneNumber || person.phoneNumber
            if ( phone ) {
                $( "#billing_phone" ).val( `${ phone.prefix || "" }${ phone.number || "" }` )
            }

            if ( consumer.company && consumer.company.name ) {
                $( "#billing_company" ).val( consumer.company.name )
                $( "#shipping_company" ).val( consumer.company.name )
            }

            if ( $( "form.checkout #terms" ).length ) {
                $( "form.checkout #terms" ).prop( "checked", true )
            }
            $( "input#ship-to-different-address-checkbox" ).prop( "checked", true )
        },

        submitCheckoutForm() {
            $( ".woocommerce-checkout-review-order-table" ).block( {
                message: null,
                overlayCSS: {
                    background: "#fff",
                    opacity: 0.6,
                },
            } )

            $.ajax( {
                type: "POST",
                url: nexiExpressParams.checkoutSubmitUrl,
                data: $( "form.checkout" ).serialize(),
                dataType: "json",
                success: ( data ) => {
                    if ( "success" === data.result ) {
                        window.location.href = data.redirect
                    } else {
                        // Required fields were missing or another validation error occurred.
                        // Fall back to letting the customer see and complete the normal checkout
                        // form themselves, pre-filled with whatever Nexi did collect.
                        NexiExpress.log( "Checkout submission did not succeed, falling back to manual checkout.", data )
                        $( ".woocommerce-checkout-review-order-table" ).unblock()
                        $( document.body ).trigger( "update_checkout" )
                    }
                },
                error: ( error ) => {
                    NexiExpress.log( "Checkout AJAX error, falling back to manual checkout.", error )
                    $( ".woocommerce-checkout-review-order-table" ).unblock()
                },
            } )
        },
    }

    $( document ).ready( () => NexiExpress.init() )
} )
