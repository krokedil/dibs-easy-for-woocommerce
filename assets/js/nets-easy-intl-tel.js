/* global intlTelInput, netsEasyIntlTelParams */
jQuery( function ( $ ) {
    if ( typeof window.intlTelInput !== "function" ) {
        return
    }

    const params = window.netsEasyIntlTelParams || {}
    const phoneSelector = "#billing_phone"
    const countrySelector = "#billing_country"
    let iti = null

    function currentCountry() {
        const country = $( countrySelector ).val()
        return country ? country.toLowerCase() : "se"
    }

    function destroy() {
        if ( iti ) {
            try {
                iti.destroy()
            } catch ( e ) {
                // Ignore — element may already be detached.
            }
            iti = null
            window.netsEasyIti = null
        }
    }

    function init() {
        const input = document.querySelector( phoneSelector )
        if ( ! input ) {
            return
        }

        // Already wrapped by another instance — skip to avoid duplicate flag dropdowns.
        if ( input.parentNode && input.parentNode.classList.contains( "iti" ) && window.netsEasyIti ) {
            return
        }

        destroy()

        iti = window.intlTelInput( input, {
            initialCountry: currentCountry(),
            loadUtilsOnInit: params.utilsURL,
            separateDialCode: true,
            preferredCountries: [ "se", "no", "dk", "fi" ],
            nationalMode: false,
            formatOnDisplay: true,
        } )

        window.netsEasyIti = iti

        $( countrySelector )
            .off( "change.netsEasyIti" )
            .on( "change.netsEasyIti", function () {
                const country = $( this ).val()
                if ( country && iti ) {
                    iti.setCountry( country.toLowerCase() )
                }
            } )

        // Make sure the value submitted to WooCommerce is E.164, so the server-side
        // prefix/number split in class-nets-easy-checkout-helper.php always sees a
        // `+CC...` number rather than guessing the prefix from billing_country.
        $( "form.checkout" )
            .off( "checkout_place_order.netsEasyIti" )
            .on( "checkout_place_order.netsEasyIti", function () {
                if ( iti ) {
                    const e164 = iti.getNumber()
                    if ( e164 ) {
                        input.value = e164
                    }
                }
                return true
            } )
    }

    $( document ).ready( init )
    $( document.body ).on( "updated_checkout", init )
} )
