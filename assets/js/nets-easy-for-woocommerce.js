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
        alreadyRedirected: false,
        isFrozen: false,
        isLoginFrozen: false,
        pendingUpdates: 0,
        lastUpdateStatus: "success",
        thawTimer: null,
        watchdogTimer: null,
        thawDelay: 500,
        watchdogDelay: 20000,

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
                $( document ).on( "ajaxSend", dibsEasyForWoocommerce.onAjaxSend )
                $( document ).on( "ajaxComplete", dibsEasyForWoocommerce.onAjaxComplete )
            }
        },

        /**
         * Checks if a request is a WooCommerce update_order_review request.
         *
         * @param {Object} settings The jQuery AJAX settings.
         * @return {boolean} True if it is an update_order_review request.
         */
        isUpdateOrderReview( settings ) {
            return (
                !! settings && "string" === typeof settings.url && -1 !== settings.url.indexOf( "update_order_review" )
            )
        },

        /**
         * Counts the update_order_review requests in flight.
         *
         * @param {Event}  event
         * @param {Object} jqXHR
         * @param {Object} settings
         */
        onAjaxSend( event, jqXHR, settings ) {
            if ( ! dibsEasyForWoocommerce.isUpdateOrderReview( settings ) ) {
                return
            }
            dibsEasyForWoocommerce.pendingUpdates++
            clearTimeout( dibsEasyForWoocommerce.thawTimer )
        },

        /**
         * Thaws the checkout once no update_order_review requests are left, whatever their outcome.
         * WooCommerce only triggers updated_checkout on success, so an error or abort would leave it frozen.
         *
         * @param {Event}  event
         * @param {Object} jqXHR
         * @param {Object} settings
         */
        onAjaxComplete( event, jqXHR, settings ) {
            if ( ! dibsEasyForWoocommerce.isUpdateOrderReview( settings ) ) {
                return
            }
            dibsEasyForWoocommerce.pendingUpdates = Math.max( 0, dibsEasyForWoocommerce.pendingUpdates - 1 )

            if ( "abort" === jqXHR.statusText ) {
                dibsEasyForWoocommerce.lastUpdateStatus = "abort"
            } else if ( jqXHR.status < 200 || jqXHR.status >= 300 ) {
                dibsEasyForWoocommerce.lastUpdateStatus = "error"
            } else {
                dibsEasyForWoocommerce.lastUpdateStatus = "success"
            }

            if ( 0 === dibsEasyForWoocommerce.pendingUpdates ) {
                const reason = dibsEasyForWoocommerce.lastUpdateStatus
                clearTimeout( dibsEasyForWoocommerce.thawTimer )
                dibsEasyForWoocommerce.thawTimer = setTimeout(
                    () => dibsEasyForWoocommerce.thaw( reason ),
                    dibsEasyForWoocommerce.thawDelay,
                )
            }
        },

        /**
         * Freezes the Nexi checkout, which blocks the pay button. Safe to call more than once.
         *
         * @param {boolean} forLogin True if the customer must log in before paying.
         */
        freeze( forLogin = false ) {
            if ( ! dibsEasyForWoocommerce.dibsCheckout ) {
                return
            }
            clearTimeout( dibsEasyForWoocommerce.thawTimer )

            if ( forLogin ) {
                dibsEasyForWoocommerce.isLoginFrozen = true
                clearTimeout( dibsEasyForWoocommerce.watchdogTimer )
            } else if ( ! dibsEasyForWoocommerce.isLoginFrozen ) {
                clearTimeout( dibsEasyForWoocommerce.watchdogTimer )
                dibsEasyForWoocommerce.watchdogTimer = setTimeout(
                    () => dibsEasyForWoocommerce.thaw( "watchdog" ),
                    dibsEasyForWoocommerce.watchdogDelay,
                )
            }

            if ( ! dibsEasyForWoocommerce.isFrozen ) {
                dibsEasyForWoocommerce.isFrozen = true
                dibsEasyForWoocommerce.dibsCheckout.freezeCheckout()
            }
        },

        /**
         * Thaws the Nexi checkout, which enables the pay button and reloads the amount. Safe to call more than once.
         *
         * @param {string} reason Why the checkout is thawed: success, error, abort, watchdog, address or failOrder.
         */
        thaw( reason ) {
            clearTimeout( dibsEasyForWoocommerce.thawTimer )
            if ( ! dibsEasyForWoocommerce.dibsCheckout || dibsEasyForWoocommerce.isLoginFrozen ) {
                return
            }
            clearTimeout( dibsEasyForWoocommerce.watchdogTimer )
            $( "#dibs-order-review" ).unblock()

            if ( ! dibsEasyForWoocommerce.isFrozen ) {
                return
            }
            dibsEasyForWoocommerce.isFrozen = false
            dibsEasyForWoocommerce.dibsCheckout.thawCheckout()

            if ( "watchdog" === reason ) {
                dibsEasyForWoocommerce.logToFile(
                    `Nexi checkout thawed by the watchdog after ${
                        dibsEasyForWoocommerce.watchdogDelay / 1000
                    } seconds. Checkout updates in flight: ${ dibsEasyForWoocommerce.pendingUpdates }.`,
                )
            } else if ( "error" === reason || "abort" === reason ) {
                dibsEasyForWoocommerce.logToFile(
                    `Nexi checkout thawed after checkout update ended with "${ reason }".`,
                )
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
                // Prevent multiple redirects.
                if ( dibsEasyForWoocommerce.alreadyRedirected ) {
                    return
                }

                dibsEasyForWoocommerce.alreadyRedirected = true

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
            $( dibsEasyForWoocommerce.checkoutFormSelector ).prepend( mustLoginNotice )
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
                dibsEasyForWoocommerce.freeze( true )

                const etop = $( dibsEasyForWoocommerce.checkoutFormSelector ).offset().top
                $( "html, body" ).animate(
                    {
                        scrollTop: etop,
                    },
                    1000,
                )
            } else {
                // All good release checkout and trigger update_checkout event
                dibsEasyForWoocommerce.isLoginFrozen = false
                dibsEasyForWoocommerce.thaw( "address" )
                $( document.body ).trigger( "update_checkout" )
            }
        },

        updateCheckout() {
            console.log( "update_checkout" )
            if ( window.Dibs !== undefined ) {
                dibsEasyForWoocommerce.freeze()
            }
        },
        updatedCheckout() {
            console.log( "updated_checkout" )
            // The update_order_review request is thawed by onAjaxComplete. Thaw here for other triggers of the event.
            if ( window.Dibs !== undefined && 0 === dibsEasyForWoocommerce.pendingUpdates ) {
                clearTimeout( dibsEasyForWoocommerce.thawTimer )
                dibsEasyForWoocommerce.thawTimer = setTimeout(
                    () => dibsEasyForWoocommerce.thaw( "success" ),
                    dibsEasyForWoocommerce.thawDelay,
                )
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
            let paymentId = $( "#nexi_payment_id" ).val()
            if ( ! paymentId ) {
                paymentId = wcDibsEasy.dibs_payment_id
                $( "#nexi_payment_id" ).val( paymentId )
            }

            // Constructs a new Checkout object.
            dibsEasyForWoocommerce.dibsCheckout = new Dibs.Checkout( {
                checkoutKey: wcDibsEasy.privateKey,
                paymentId: paymentId,
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
            dibsEasyForWoocommerce.getOrderData( paymentId, 0 )
        },
        /**
         * Fetches the Nets Easy order data, retrying while the customer email is missing.
         *
         * @param {string} paymentId
         * @param {number} attempt
         */
        getOrderData( paymentId, attempt ) {
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
                    // The nonce goes stale when the checkout logged the customer in. Reload to get fresh ones.
                    if ( false === data.success && "bad_nonce" === data.data ) {
                        dibsEasyForWoocommerce.reloadCheckout()
                        return
                    }
                    if ( false === data.success && data.data && data.data.retry ) {
                        // Nexi can lack the customer email right after the pay click, so fetch it again a few times.
                        if ( attempt < 2 ) {
                            const nextAttempt = attempt + 1
                            dibsEasyForWoocommerce.logToFile(
                                `Customer email missing for payment id: ${ paymentId }. Fetching the payment again (retry ${ nextAttempt }).`,
                            )
                            setTimeout(
                                () => dibsEasyForWoocommerce.getOrderData( paymentId, nextAttempt ),
                                nextAttempt * 1000,
                            )
                        } else {
                            dibsEasyForWoocommerce.logToFile(
                                `Customer email still missing for payment id: ${ paymentId } after ${ attempt } retries.`,
                            )
                            dibsEasyForWoocommerce.failOrder(
                                "ajax-error",
                                '<div class="woocommerce-error">' + data.data.message + "</div>",
                            )
                        }
                    } else if ( false === data.success ) {
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
                const { prefix = "", number = "" } = phoneNumber || {}
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
                const { prefix = "", number = "" } = phoneNumber || {}
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

            const $shippingPhone = $( "#shipping_phone" )
            if ( $shippingPhone.length ) {
                const spn = shippingAddress.phoneNumber
                const phoneVal = spn ? `${ spn.prefix }${ spn.number }` : $( "#billing_phone" ).val()
                if ( phoneVal ) {
                    $shippingPhone.val( phoneVal )
                }
            }
            const $shippingEmail = $( "#shipping_email" )
            if ( $shippingEmail.length ) {
                const emailVal = $( "#billing_email" ).val()
                if ( emailVal ) {
                    $shippingEmail.val( emailVal )
                }
            }

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
                        // WooCommerce asks for a reload after the checkout logged the customer in.
                        if ( true === data.reload ) {
                            dibsEasyForWoocommerce.reloadCheckout()
                            return
                        }
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
         * Cancels the pending payment in the Nexi box and reloads the checkout.
         */
        reloadCheckout() {
            dibsEasyForWoocommerce.dibsCheckout.send( "payment-order-finalized", false )
            const url = new URL( window.location.href )
            url.searchParams.set( "nexi_new_payment", "1" )
            window.location.replace( url.toString() )
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
            dibsEasyForWoocommerce.thaw( "failOrder" )
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
