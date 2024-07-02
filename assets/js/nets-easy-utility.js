/**
 * @member nets_easy_utility_params
 *
 */
jQuery( function ( $ ) {
    if ( typeof nets_easy_utility_params === "undefined" ) {
        return false
    }
    /**
     * The main object.
     *
     * @type {Object} netsEasyUtility
     */
    const netsEasyUtility = {
        bodyEl: $( "body" ),
        checkoutFormSelector: "form.checkout",

        /**
         * Initialize the gateway
         */
        init() {
            netsEasyUtility.bodyEl.on( "change", 'input[name="payment_method"]', netsEasyUtility.maybeChangeToDibsEasy )
        },

        /**
         * When the customer changes to Dibs Easy from other payment methods.
         */
        maybeChangeToDibsEasy() {
            if ( "dibs_easy" === $( this ).val() ) {
                $( ".woocommerce-info" ).remove()
                $( netsEasyUtility.checkoutFormSelector ).block( {
                    message: null,
                    overlayCSS: {
                        background: "#fff",
                        opacity: 0.6,
                    },
                } )
                $.ajax( {
                    type: "POST",
                    data: {
                        nonce: nets_easy_utility_params.nets_checkout_nonce,
                    },
                    dataType: "json",
                    url: nets_easy_utility_params.change_payment_method_url,
                    success( data ) {},
                    error( data ) {},
                    complete( data ) {
                        window.location.href = data.responseJSON.data.redirect
                    },
                } )
            }
        },
    }

    netsEasyUtility.init()
} )
