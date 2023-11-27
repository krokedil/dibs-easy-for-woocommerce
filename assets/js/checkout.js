function alog( msg ) {
    console.log( msg )
}

jQuery( function ( $ ) {
    // eslint-disable-next-line camelcase
    const dibs_wc = {
        bodyEl: $( "body" ),

        // Payment method
        paymentMethodEl: $( 'input[name="payment_method"]' ),
        paymentMethod: "",

        // Extra checkout fields.
        blocked: false,

        // Dibs processing order.
        dibsOrderProcessing: false,

        /*
         * Document ready function.
         * Runs on the $(document).ready event.
         */
        documentReady() {
            alog( 1 )
            // Extra checkout fields.
            dibs_wc.moveExtraCheckoutFields()
        },

        /*
         * Check if DIBS Easy is the selected gateway.
         */
        DibsIsSelected() {
            alog( 2 )
            if ( dibs_wc.paymentMethodEl.length > 0 ) {
                dibs_wc.paymentMethod = dibs_wc.paymentMethodEl.filter( ":checked" ).val()
                if ( "dibs_easy" === dibs_wc.paymentMethod ) {
                    return true
                }
            }
            return false
        },

        /*
         * Locks the iFrame.
         */
        DibsFreeze() {
            alog( 3 )
            dibsCheckout.freezeCheckout()
        },

        /*
         * Unlocks the iFrame.
         */
        DibsResume() {
            alog( 4 )
            if ( ! dibs_wc.blocked ) {
                dibsCheckout.thawCheckout()
            }
        },

        /**
         * Moves all non standard fields to the extra checkout fields.
         */
        moveExtraCheckoutFields() {
            alog( 5 )
            // Move order comments.
            $( ".woocommerce-additional-fields" ).appendTo( "#dibs-extra-checkout-fields" )

            const form = $( 'form[name="checkout"] input, form[name="checkout"] select, textarea' )
            for ( i = 0; i < form.length; i++ ) {
                const name = form[ i ].name
                // Check if this is a standard field.
                if ( $.inArray( name, wc_dibs_easy.standard_woo_checkout_fields ) === -1 ) {
                    // This is not a standard Woo field, move to our div.
                    $( "p#" + name + "_field" ).appendTo( "#dibs-extra-checkout-fields" )
                }
            }
        },

        /**
         * Handle hashchange triggered when Woo order is created.
         *
         * @param  event
         */
        handleHashChange( event ) {
            alog( 6 )

            console.log( "hashchange" )
            const currentHash = location.hash
            const splittedHash = currentHash.split( "=" )
            console.log( splittedHash[ 0 ] )
            console.log( splittedHash[ 1 ] )
            if ( splittedHash[ 0 ] === "#dibseasy" ) {
                const response = JSON.parse( atob( splittedHash[ 1 ] ) )
                window.dibsRedirectUrl = response.redirect_url
                console.log( "response.return_url" )
                console.log( response.return_url )
                sessionStorage.setItem( "DIBSRedirectUrl", response.return_url )
                $( "#dibs-order-review" ).block( {
                    message: null,
                    overlayCSS: {
                        background: "#fff",
                        opacity: 0.6,
                    },
                } )
                $( "form.checkout" ).removeClass( "processing" ).unblock()
                dibs_wc.dibsOrderProcessing = false
                dibsCheckout.send( "payment-order-finalized", true )
            }
        },

        /*
         * Initiates the script and sets the triggers for the functions.
         */
        init() {
            alog( 7 )

            // Check if DIBS Easy is the selected payment method before we do anything.
            if ( dibs_wc.DibsIsSelected() ) {
                $( document ).ready( dibs_wc.documentReady() )

                window.addEventListener( "hashchange", dibs_wc.handleHashChange )
            }
        },
    }
    dibs_wc.init()

    var i = 0
    const x = 0
    let checkout_initiated = wc_dibs_easy.checkout_initiated
    const paymentId = wc_dibs_easy.paymentId

    $( document ).ready( function () {
        alog( 8 )

        if ( "dibs_easy" === $( "input[name='payment_method']:checked" ).val() ) {
            addressChangedListener()
            paymentInitializedListener()
            paymentCompletedListener()
        }
    } )

    // Address updated in Easy checkout
    function addressChangedListener() {
        dibsCheckout.on( "address-changed", function ( address ) {
            alog( 9 )

            if ( address ) {
                console.log( "address-changed" )
                console.log( address )
                $.ajax( wc_dibs_easy.customer_address_updated_url, {
                    type: "POST",
                    dataType: "json",
                    async: true,
                    data: {
                        action: "customer_address_updated",
                        address,
                        nonce: wc_dibs_easy.nets_checkout_nonce,
                    },
                    success( response ) {},
                    error( response ) {},
                    complete( response ) {
                        console.log( "customer_address_updated " )
                        console.log( response.responseJSON.data )
                        if ( "yes" === response.responseJSON.data.updateNeeded ) {
                            $( "#billing_country" ).val( response.responseJSON.data.country )
                            $( "#shipping_country" ).val( response.responseJSON.data.country )
                            $( "#billing_postcode" ).val( response.responseJSON.data.postCode )
                            $( "#shipping_postcode" ).val( response.responseJSON.data.postCode )
                        }

                        if ( "yes" === response.responseJSON.data.mustLogin ) {
                            // Customer might need to login. Inform customer and freeze DIBS checkout.
                            const $form = $( "form.checkout" )
                            $form.prepend(
                                '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-updateOrderReview"><ul class="woocommerce-error" role="alert"><li>' +
                                    response.responseJSON.data.mustLoginMessage +
                                    "</li></ul></div>",
                            )
                            dibsCheckout.freezeCheckout()

                            const etop = $( "form.checkout" ).offset().top
                            $( "html, body" ).animate(
                                {
                                    scrollTop: etop,
                                },
                                1000,
                            )
                        } else {
                            // All good release checkout and trigger update_checkout event
                            dibsCheckout.thawCheckout()
                            $( document.body ).trigger( "update_checkout" )
                        }
                    },
                } )
            }
        } )
    }

    // When customer clicks Pay button in Easy. Before redirect to 3DSecure.
    function paymentInitializedListener() {
        dibsCheckout.on( "pay-initialized", function ( response ) {
            alog( 10 )

            dibs_wc.dibsOrderProcessing = true
            $( document.body ).trigger( "dibs_pay_initialized" )
            console.log( "dibs_pay_initialized" )
            console.log( response )
            processWooCheckout( response )
        } )
    }

    //After payment is complete
    function paymentCompletedListener() {
        dibsCheckout.on( "payment-completed", function ( response ) {
            alog( 11 )

            console.log( "payment-completed" )
            console.log( response.paymentId )
            //DIBS_Payment_Success(response.paymentId);
            const redirectUrl = sessionStorage.getItem( "DIBSRedirectUrl" )
            console.log( redirectUrl )
            if ( redirectUrl ) {
                window.location.href = redirectUrl
            }
        } )
    }

    function processWooCheckout( paymentId ) {
        alog( 12 )

        // $('body').addClass( 'dibs-checkout-processing' );
        // $( 'body' ).append( $( '<div class="dibs-modal"><div class="dibs-modal-content">' + wc_dibs_easy.dibs_process_order_text + '</div></div>' ) );
        $.ajax( wc_dibs_easy.get_order_data_url, {
            type: "POST",
            dataType: "json",
            async: true,
            data: {
                action: "payment_success",
                paymentId,
                nonce: wc_dibs_easy.nets_checkout_nonce,
            },
            success( data ) {
                console.log( data )
                if ( false === data.success ) {
                    console.log( "PaymentID already exist in order" )
                    console.log( data )
                    if ( data.data.redirect ) {
                        window.location.href = data.data.redirect
                    }
                } else {
                    $( "form.checkout #billing_address_1" ).val(
                        data.data.payment.consumer.shippingAddress.addressLine1,
                    )
                    $( "form.checkout #billing_postcode" ).val( data.data.payment.consumer.shippingAddress.postalCode )
                    $( "form.checkout #billing_city" ).val( data.data.payment.consumer.shippingAddress.city )
                    $( "form.checkout #billing_country" ).val( data.data.payment.consumer.shippingAddress.country )

                    $( "form.checkout #shipping_address_1" ).val(
                        data.data.payment.consumer.shippingAddress.addressLine1,
                    )
                    $( "form.checkout #shipping_postcode" ).val( data.data.payment.consumer.shippingAddress.postalCode )
                    $( "form.checkout #shipping_city" ).val( data.data.payment.consumer.shippingAddress.city )
                    $( "form.checkout #shipping_country" ).val( data.data.payment.consumer.shippingAddress.country )

                    if ( data.data.payment.consumer.company.name != null ) {
                        // B2B purchase
                        $( "form.checkout #billing_company" ).val( data.data.payment.consumer.company.name )
                        $( "form.checkout #shipping_company" ).val( data.data.payment.consumer.company.name )
                        $( "form.checkout #billing_first_name" ).val(
                            data.data.payment.consumer.company.contactDetails.firstName,
                        )
                        $( "form.checkout #billing_last_name" ).val(
                            data.data.payment.consumer.company.contactDetails.lastName,
                        )
                        $( "form.checkout #shipping_first_name" ).val(
                            data.data.payment.consumer.company.contactDetails.firstName,
                        )
                        $( "form.checkout #shipping_last_name" ).val(
                            data.data.payment.consumer.company.contactDetails.lastName,
                        )
                        $( "form.checkout #billing_email" ).val(
                            data.data.payment.consumer.company.contactDetails.email,
                        )
                        $( "form.checkout #billing_phone" ).val(
                            data.data.payment.consumer.company.contactDetails.phoneNumber.prefix +
                                data.data.payment.consumer.company.contactDetails.phoneNumber.number,
                        )
                    } else {
                        // B2C purchase
                        $( "form.checkout #billing_company" ).val( "" )
                        $( "form.checkout #shipping_company" ).val( "" )
                        $( "form.checkout #billing_first_name" ).val(
                            data.data.payment.consumer.privatePerson.firstName,
                        )
                        $( "form.checkout #billing_last_name" ).val( data.data.payment.consumer.privatePerson.lastName )
                        $( "form.checkout #shipping_first_name" ).val(
                            data.data.payment.consumer.privatePerson.firstName,
                        )
                        $( "form.checkout #shipping_last_name" ).val(
                            data.data.payment.consumer.privatePerson.lastName,
                        )
                        $( "form.checkout #billing_email" ).val( data.data.payment.consumer.privatePerson.email )
                        $( "form.checkout #billing_phone" ).val(
                            data.data.payment.consumer.privatePerson.phoneNumber.prefix +
                                data.data.payment.consumer.privatePerson.phoneNumber.number,
                        )
                    }

                    if ( data.data.payment.consumer.shippingAddress.addressLine2 != null ) {
                        $( "form.checkout #billing_address_2" ).val(
                            data.data.payment.consumer.shippingAddress.addressLine2,
                        )
                        $( "form.checkout #shipping_address_2" ).val(
                            data.data.payment.consumer.shippingAddress.addressLine2,
                        )
                    }

                    // Check Terms checkbox, if it exists
                    if ( $( "form.checkout #terms" ).length > 0 ) {
                        $( "form.checkout #terms" ).prop( "checked", true )
                    }
                    $( "input#ship-to-different-address-checkbox" ).prop( "checked", true )
                    $( "form.woocommerce-checkout" ).append(
                        '<input type="hidden" id="dibs_payment_id" name="dibs_payment_id" value="' + paymentId + '" />',
                    )
                    $( 'form[name="checkout"]' ).submit()
                    $( "form.woocommerce-checkout" ).addClass( "processing" )
                }
            },
            error( data ) {
                console.log( data, "error_data" )
            },
            complete( data ) {},
        } )
    }

    // Update DIBS Easy checkout (after Woo updated_checkout)
    function update_checkout() {
        alog( 13 )

        if (
            ( checkout_initiated === "yes" && wc_dibs_easy.paymentId == null ) ||
            ( wc_dibs_easy.paymentId !== null && wc_dibs_easy.paymentFailed !== null )
        ) {
            console.log( "update checkout" )
            $.ajax( wc_dibs_easy.update_checkout_url, {
                type: "POST",
                dataType: "json",
                data: {
                    action: "update_checkout",
                    nonce: wc_dibs_easy.nets_checkout_nonce,
                },
                success( response ) {
                    if ( true === response.success ) {
                        const nonce = response.data.nonce
                        $( "#dibs-nonce-wrapper" ).html( nonce ) // Updates the nonce used on checkout
                        console.log( "update checkout success" )
                        dibsCheckout.thawCheckout()
                    } else {
                        console.log( "error" )
                        window.location.href = response.data.redirect_url
                    }
                },
            } )
        } else {
            checkout_initiated = "yes"
        }
    }

    const wc_dibs_body_class = function wc_dibs_body_class() {
        alog( 14 )

        if ( "dibs_easy" === $( "input[name='payment_method']:checked" ).val() ) {
            $( "body" ).addClass( "dibs-selected" ).removeClass( "dibs-deselected" )
        } else {
            $( "body" ).removeClass( "dibs-selected" ).addClass( "dibs-deselected" )
        }
    }

    // When Select another payment method button is clicked - ovo sam uradio.
    $( document ).on( "click", "#dibs-easy-select-other", function ( e ) {
        e.preventDefault()

        $.ajax( wc_dibs_easy.change_payment_method_url, {
            type: "POST",
            dataType: "json",
            async: true,
            data: {
                action: "dibs_change_payment_method",
                dibs_easy: false,
                nonce: wc_dibs_easy.nets_checkout_nonce,
            },
            success( data ) {},
            error( data ) {},
            complete( data ) {
                console.log( "Change payment method sucess" )
                console.log( data.responseJSON.data.redirect )
                $( "body" ).removeClass( "dibs-selected" )
                window.location.href = data.responseJSON.data.redirect
            },
        } )
    } )

    // When payment method is changed ovo sam uradio.
    $( document ).on( "change", "input[name='payment_method']", function ( event ) {
        alog( 14 )

        if ( true !== dibs_wc.dibsOrderProcessing ) {
            if ( "dibs_easy" === $( "input[name='payment_method']:checked" ).val() ) {
                $.ajax( wc_dibs_easy.change_payment_method_url, {
                    type: "POST",
                    dataType: "json",
                    async: true,
                    data: {
                        action: "dibs_change_payment_method",
                        dibs_easy: true,
                        nonce: wc_dibs_easy.nets_checkout_nonce,
                    },
                    success( data ) {},
                    error( data ) {},
                    complete( data ) {
                        console.log( "Change payment method sucess" )
                        console.log( data.responseJSON.data.redirect )
                        $( "body" ).removeClass( "dibs-deselected" )
                        window.location.href = data.responseJSON.data.redirect
                    },
                } )
            }
        }
    } )

    // When WooCommerce checkout submission fails
    $( document ).on( "checkout_error", function ( wpe ) {
        alog( 15 )

        console.log( wpe )
        if ( "dibs_easy" === $( "input[name='payment_method']:checked" ).val() ) {
            alog( "15aa" )
            console.log( "responded with payment-order-finalized false" )
            dibsCheckout.send( "payment-order-finalized", false )
        }
    } )

    // Suspend DIBS Checkout during WooCommerce checkout update
    $( document ).on( "update_checkout", function () {
        alog( 16 )

        if (
            "dibs_easy" === $( "input[name='payment_method']:checked" ).val() &&
            checkout_initiated == "yes" &&
            paymentId == null
        ) {
            alog( 17 )

            dibsCheckout.freezeCheckout()
        }
    } )

    // Send an updated cart to DIBS after the checkout has been updated in Woo
    $( document ).on( "updated_checkout", function () {
        alog( 18 )
        if ( "dibs_easy" === $( "input[name='payment_method']:checked" ).val() ) {
            alog( 19 )
            update_checkout()
        }
    } )

    // Send an updated cart to DIBS after the checkout has been updated in Woo
    $( document ).on( "blur", function () {
        alog( 20 )
        if ( "dibs_easy" === $( "input[name='payment_method']:checked" ).val() ) {
            alog( 21 )
            update_checkout()
        }
    } )
} )
