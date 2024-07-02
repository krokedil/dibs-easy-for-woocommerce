/**
 * @member wcDibsEasy
 *
 */
jQuery( function ( $ ) {
    if ( typeof wcDibsEasy === "undefined" ) {
        return false
    }
    /**
     * The main object.
     *
     * @type {Object} dibsEasyForWoocommerce
     */
    const dibsEasyForWoocommerce = {
        bodyEl: $( "body" ),
        paymentMethodEl: $( 'input[name="payment_method"]' ),
        dibsCheckout: null,
        blocked: false,
        selectAnotherSelector: "#dibs-easy-select-other",
        checkoutInitiated: wcDibsEasy.checkoutInitiated,
        dibsOrderProcessing: false,
        checkoutFormSelector: "form.checkout",
        shippingSelector: ".woocommerce-checkout-review-order-table",
        standardWooFields: wcDibsEasy.standard_woo_checkout_fields,
        wooTerms: "form.checkout #terms",

        /**
         * Initialize the gateway
         */
        init() {
            $( document ).ready( dibsEasyForWoocommerce.loadDibs )
            dibsEasyForWoocommerce.bodyEl.on(
                "click",
                dibsEasyForWoocommerce.selectAnotherSelector,
                dibsEasyForWoocommerce.changeFromDibsEasy,
            )
        },

        /**
         * Check if DIBS Easy is the selected gateway.
         */
        dibsIsSelected() {
            if ( dibsEasyForWoocommerce.paymentMethodEl.length > 0 ) {
                dibsEasyForWoocommerce.paymentMethod = dibsEasyForWoocommerce.paymentMethodEl.filter( ":checked" ).val()
                if ( "dibs_easy" === dibsEasyForWoocommerce.paymentMethod ) {
                    return true
                }
            }
            return false
        },
        /**
         * Triggers on document ready.
         */
        loadDibs() {
            if ( dibsEasyForWoocommerce.dibsIsSelected() ) {
                dibsEasyForWoocommerce.moveExtraCheckoutFields()
                dibsEasyForWoocommerce.initDibsCheckout()
                dibsEasyForWoocommerce.bodyEl.on( "update_checkout", dibsEasyForWoocommerce.updateCheckout )
                dibsEasyForWoocommerce.bodyEl.on( "updated_checkout", dibsEasyForWoocommerce.updatedCheckout )
            }
        },

        /**
         * Triggers after a successful payment.
         *
         * @param {Object} response
         */
        paymentCompleted( response ) {
            dibsEasyForWoocommerce.logToFile(
                `Payment completed is triggered with payment id: ${ response.paymentId }`,
            )
            const redirectUrl = sessionStorage.getItem( "redirectNets" )
            if ( redirectUrl ) {
                window.location.href = redirectUrl
            }
        },

        /**
         *  Display login error
         *
         * @param {string} message
         */
        displayMustLoginError( message ) {
            const mustLoginClass = "woocommerce-NoticeGroupwoocommerce-NoticeGroup-updateOrderReview"
            const mustLoginNotice = `<div class="${ mustLoginClass }">
				<ul class="woocommerce-error" role="alert">
					<li>${ message }</li>
				</ul>
			</div>`
            dibsEasyForWoocommerce.checkoutFormSelector.prepend( mustLoginNotice )
        },
        /**
         * Update WC form if needed
         *
         * @param {Object} data
         */
        updateAddress( data ) {
            if ( "yes" === data.updateNeeded ) {
                $( "#billing_country" ).val( data.country )
                $( "#shipping_country" ).val( data.country )
                $( "#billing_postcode" ).val( data.postCode )
                $( "#shipping_postcode" ).val( data.postCode )
            }

            if ( "yes" === data.mustLogin ) {
                // Customer might need to log in. Inform customer and freeze DIBS checkout.
                dibsEasyForWoocommerce.displayMustLoginError( data.mustLoginMessage )
                dibsEasyForWoocommerce.dibsCheckout.freezeCheckout()

                const etop = dibsEasyForWoocommerce.checkoutFormSelector.offset().top
                $( "html, body" ).animate(
                    {
                        scrollTop: etop,
                    },
                    1000,
                )
            } else {
                // All good release checkout and trigger update_checkout event
                dibsEasyForWoocommerce.dibsCheckout.thawCheckout()
                $( document.body ).trigger( "update_checkout" )
            }
        },

        updateCheckout() {
            console.log( "update_checkout" )
            if ( window.Dibs !== undefined ) {
                // lock iframe
                dibsEasyForWoocommerce.dibsCheckout.freezeCheckout()
            }
        },
        updatedCheckout() {
            console.log( "updated_checkout" )
            if ( window.Dibs !== undefined ) {
                // unlock iframe
                dibsEasyForWoocommerce.dibsCheckout.thawCheckout()
                $( "#dibs-order-review" ).unblock()
            }
        },
        /**
         * Triggers whenever customer updates address information
         *
         * @param {Object} address
         */
        addressChanged( address ) {
            dibsEasyForWoocommerce.logToFile( "Address changed is triggered." )
            if ( address ) {
                console.log( "address-changed" )
                console.log( address )
                $.ajax( {
                    type: "POST",
                    dataType: "json",
                    async: true,
                    url: wcDibsEasy.customer_address_updated_url,
                    data: {
                        action: "customer_address_updated",
                        address,
                        nonce: wcDibsEasy.nets_checkout_nonce,
                    },
                    success( response ) {},
                    error( response ) {},
                    complete( response ) {
                        console.log( "COMPLETED" )
                        console.log( "customer_address_updated " )
                        console.log( response.responseJSON.data )
                        dibsEasyForWoocommerce.updateAddress( response.responseJSON.data )
                        // Scroll to top if
                        if ( wcDibsEasy.isMobile ) {
                            $( "html, body" ).animate(
                                {
                                    scrollTop: $( wcDibsEasy.shippingSelector ).offset().top - 10,
                                },
                                1000,
                            )
                        }
                    },
                } )
            }
        },
        /**
         * Triggers whenever customer updates address information from ApplePay window.
         *
         */
        applePayAddressChanged( address ) {
            console.log( "applepay-contact-updated", address )
            dibsEasyForWoocommerce.logToFile( "ApplePay address changed is triggered." )
            if ( address ) {
                console.log( "applepay-contact-updated" )
                $.ajax( {
                    type: "POST",
                    dataType: "json",
                    async: true,
                    url: wcDibsEasy.customer_address_updated_url,
                    data: {
                        action: "customer_address_updated",
                        address,
                        nonce: wcDibsEasy.nets_checkout_nonce,
                    },
                    success( response ) {},
                    error( response ) {},
                    complete( response ) {
                        console.log( "COMPLETED" )
                        console.log( "customer_address_updated " )
                        console.log( response.responseJSON.data )
                        dibsEasyForWoocommerce.updateAddress( response.responseJSON.data )
                        dibsEasyForWoocommerce.dibsCheckout.completeApplePayShippingContactUpdate(
                            response.responseJSON.data.cart_total,
                        )
                    },
                } )
            }
        },
        /**
         * Init Dibs Easy Checkout
         */
        initDibsCheckout() {
            // Constructs a new Checkout object.
            dibsEasyForWoocommerce.dibsCheckout = new Dibs.Checkout( {
                checkoutKey: wcDibsEasy.privateKey,
                paymentId: wcDibsEasy.dibs_payment_id,
                containerId: "dibs-complete-checkout",
                language: wcDibsEasy.locale,
            } )
            dibsEasyForWoocommerce.dibsCheckout.on( "pay-initialized", dibsEasyForWoocommerce.getDibsEasyOrder )
            dibsEasyForWoocommerce.dibsCheckout.on( "payment-completed", dibsEasyForWoocommerce.paymentCompleted )
            dibsEasyForWoocommerce.dibsCheckout.on( "address-changed", dibsEasyForWoocommerce.addressChanged )
            dibsEasyForWoocommerce.dibsCheckout.on(
                "applepay-contact-updated",
                dibsEasyForWoocommerce.applePayAddressChanged,
            )
        },
        /**
         * Triggers when customer clicks the pay button.
         * Gets the Nets Easy order and starts the order submission
         *
         * @param {string} paymentId
         */
        getDibsEasyOrder( paymentId ) {
            dibsEasyForWoocommerce.dibsOrderProcessing = true
            dibsEasyForWoocommerce.logToFile( `Pay initialized is triggered with payment id: ${ paymentId }` )
            $( document.body ).trigger( "dibs_pay_initialized" )
            $.ajax( {
                type: "POST",
                dataType: "json",
                async: true,
                url: wcDibsEasy.get_order_data_url,
                data: {
                    action: "payment_success",
                    paymentId,
                    nonce: wcDibsEasy.nets_checkout_nonce,
                },
                success( data ) {
                    console.log( data )
                    if ( false === data.success ) {
                        console.log( "PaymentID already exist in order" )
                        console.log( data )
                        dibsEasyForWoocommerce.failOrder(
                            "ajax-error",
                            '<div class="woocommerce-error">' + data.data + "</div>",
                        )
                        if ( data.data.redirect ) {
                            window.location.href = data.data.redirect
                        }
                    } else {
                        dibsEasyForWoocommerce.setAddressData( data )
                    }
                },
                error( data ) {
                    console.log( data, "error_data" )
                },
                complete( data ) {},
            } )
        },
        /**
         * Sets the customer data.
         *
         * @param {Object} data
         */
        setAddressData( data ) {
            dibsEasyForWoocommerce.logToFile( 'Received "customer data" from Nets Easy' )
            const consumer = data.data.payment.consumer
            const { billingAddress, shippingAddress } = consumer

            // billingAddress have country with 3 letters.

            // check do we have data
            const hasBillingData = typeof billingAddress === "object" && ! Array.isArray( billingAddress )
            // Use shipping data if billing data do not exist.
            $( "#billing_address_1" ).val( hasBillingData ? billingAddress.addressLine1 : shippingAddress.addressLine1 )
            $( "#billing_postcode" ).val( hasBillingData ? billingAddress.postalCode : shippingAddress.postalCode )
            $( "#billing_city" ).val( hasBillingData ? billingAddress.city : shippingAddress.city )
            $( "#billing_country" ).val( shippingAddress.country )

            $( "#shipping_address_1" ).val( shippingAddress.addressLine1 )
            $( "#shipping_postcode" ).val( shippingAddress.postalCode )
            $( "#shipping_city" ).val( shippingAddress.city )
            $( "#shipping_country" ).val( shippingAddress.country )

            if ( consumer.company.hasOwnProperty( "name" ) ) {
                // B2B purchase
                const company = consumer.company
                const { firstName, lastName, email, phoneNumber } = company.contactDetails
                const { prefix, number } = phoneNumber
                $( "#billing_company" ).val( company.name )
                $( "#shipping_company" ).val( company.name )
                $( "#billing_first_name" ).val( firstName )
                $( "#billing_last_name" ).val( lastName )
                $( "#shipping_first_name" ).val( firstName )
                $( "#shipping_last_name" ).val( lastName )
                $( "#billing_email" ).val( email )
                $( "#billing_phone" ).val( `${ prefix }${ number }` )
                // trigger events for 3rd part plugins.
                $( "#billing_country" ).change()
                $( "#billing_email" ).change()
                $( "#billing_email" ).blur()
            } else {
                // B2C purchase
                const { firstName, lastName, email, phoneNumber } = consumer.privatePerson
                const { prefix, number } = phoneNumber
                $( "#billing_company" ).val( "" )
                $( "#shipping_company" ).val( "" )
                $( "#billing_first_name" ).val( firstName )
                $( "#billing_last_name" ).val( lastName )
                $( "#shipping_first_name" ).val( firstName )
                $( "#shipping_last_name" ).val( lastName )
                $( "#billing_email" ).val( email )
                // trigger events for 3rd part plugins.
                $( "#billing_email" ).change()
                $( "#billing_email" ).blur()
                $( "#billing_phone" ).val( `${ prefix }${ number }` )
            }

            // eslint-disable-next-line eqeqeq
            if ( shippingAddress.addressLine2 != null ) {
                $( "#billing_address_2" ).val( shippingAddress.addressLine2 )
                $( "#shipping_address_2" ).val( shippingAddress.addressLine2 )
            }

            // Check Terms checkbox, if it exists
            if ( $( dibsEasyForWoocommerce.wooTerms ).length > 0 ) {
                $( dibsEasyForWoocommerce.wooTerms ).prop( "checked", true )
            }
            $( "input#ship-to-different-address-checkbox" ).prop( "checked", true )
            dibsEasyForWoocommerce.submitOrder()
        },
        /**
         * Submit the order using the WooCommerce AJAX function.
         */
        submitOrder() {
            $( ".woocommerce-checkout-review-order-table" ).block( {
                message: null,
                overlayCSS: {
                    background: "#fff",
                    opacity: 0.6,
                },
            } )
            $.ajax( {
                type: "POST",
                url: wcDibsEasy.submitOrder,
                data: $( "form.checkout" ).serialize(),
                dataType: "json",
                success( data ) {
                    try {
                        if ( "success" === data.result ) {
                            dibsEasyForWoocommerce.logToFile( "Successfully placed order." )
                            window.sessionStorage.setItem( "redirectNets", data.redirect )
                            dibsEasyForWoocommerce.dibsCheckout.send( "payment-order-finalized", true )
                        } else {
                            throw "Result failed"
                        }
                    } catch ( err ) {
                        if ( data.messages ) {
                            dibsEasyForWoocommerce.logToFile( "Checkout error | " + data.messages )
                            dibsEasyForWoocommerce.failOrder( "submission", data.messages )
                        } else {
                            dibsEasyForWoocommerce.logToFile( "Checkout error | No message" )
                            dibsEasyForWoocommerce.failOrder(
                                "submission",
                                '<div class="woocommerce-error">' + "Checkout error" + "</div>",
                            )
                        }
                    }
                },
                error( data ) {
                    try {
                        dibsEasyForWoocommerce.logToFile( "AJAX error | " + JSON.stringify( data ) )
                    } catch ( e ) {
                        dibsEasyForWoocommerce.logToFile( "AJAX error | Failed to parse error message." )
                    }
                    dibsEasyForWoocommerce.failOrder(
                        "ajax-error",
                        '<div class="woocommerce-error">Internal Server Error</div>',
                    )
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
            $( dibsEasyForWoocommerce.checkoutFormSelector ).block( {
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
                url: wcDibsEasy.change_payment_method_url,
                data: {
                    action: "dibs_change_payment_method",
                    dibs_easy: false,
                    nonce: wcDibsEasy.nets_checkout_nonce,
                },
                success( data ) {},
                error( data ) {},
                complete( data ) {
                    console.log( "Change payment method success" )
                    console.log( data.responseJSON.data.redirect )
                    dibsEasyForWoocommerce.bodyEl.removeClass( "dibs-selected" )
                    window.location.href = data.responseJSON.data.redirect
                },
            } )
        },
        /**
         * Moves all non-standard fields to the extra checkout fields.
         */
        moveExtraCheckoutFields() {
            // Move order comments.
            $( ".woocommerce-additional-fields" ).appendTo( "#dibs-extra-checkout-fields" )

            const form = $( 'form[name="checkout"] input, form[name="checkout"] select, textarea' )
            for ( let i = 0; i < form.length; i++ ) {
                const name = form[ i ].name
                const fields = dibsEasyForWoocommerce.standardWooFields
                // Check if this is a standard field.
                if ( $.inArray( name, fields ) === -1 ) {
                    // This is not a standard Woo field, move to our div.
                    $( "p#" + name + "_field" ).appendTo( "#dibs-extra-checkout-fields" )
                }
            }
        },
        /**
         * Logs the message to the Dibs Easy log in WooCommerce.
         *
         * @param {string} message
         */
        logToFile( message ) {
            $.ajax( {
                url: wcDibsEasy.log_to_file_url,
                type: "POST",
                dataType: "json",
                data: {
                    message,
                    nonce: wcDibsEasy.log_to_file_nonce,
                },
            } )
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
            dibsEasyForWoocommerce.dibsCheckout.send( "payment-order-finalized", false )
            // Reenable the form.
            dibsEasyForWoocommerce.bodyEl.trigger( "updated_checkout" )
            $( dibsEasyForWoocommerce.checkoutFormSelector ).removeClass( "processing" )
            // $( dibsEasyForWoocommerce.checkoutFormSelector ).unblock();
            // $( '.woocommerce-checkout-review-order-table' ).unblock();

            // Print error messages, and trigger checkout_error, and scroll to notices.
            $( ".woocommerce-NoticeGroup-checkout," + ".woocommerce-error," + ".woocommerce-message" ).remove()

            $( dibsEasyForWoocommerce.checkoutFormSelector ).prepend( errorWrapper )
            // $( dibsEasyForWoocommerce.checkoutFormSelector )
            // 	.removeClass( 'processing' )
            // 	.unblock();
            $( dibsEasyForWoocommerce.checkoutFormSelector )
                .find( ".input-text, select, input:checkbox" )
                .trigger( "validate" )
                .blur()
            $( document.body ).trigger( "checkout_error", [ errorMessage ] )
            $( "html, body" ).animate(
                {
                    scrollTop: $( dibsEasyForWoocommerce.checkoutFormSelector ).offset().top - 100,
                },
                1000,
            )
        },
    }

    dibsEasyForWoocommerce.init()
} )
