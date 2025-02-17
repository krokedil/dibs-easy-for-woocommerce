/* global netseasyParams */
jQuery( function ( $ ) {
    var netsEasyForWooCommerce = {
        init: function () {
            window.addEventListener( "hashchange", netsEasyForWooCommerce.handleHashChange )
            window.addEventListener( "message", netsEasyForWooCommerce.handleMessage )
        },

        handleHashChange: function () {
            var currentHash = location.hash
            var splittedHash = currentHash.split( ":" )
            if ( splittedHash[ 0 ] === "#netseasy" ) {
                var url = atob( splittedHash[ 1 ] )
                netsEasyForWooCommerce.addIframe( url )
            }
        },

        // Handle messages from the iframe.
        handleMessage: function ( evt ) {
            if ( evt.origin !== this.window.location.origin ) {
                return
            }

            const events = [ "nexi-close-overlay" ]
            if ( ! events.includes( evt.data.event ) ) {
                return
            }

            netsEasyForWooCommerce.closeOverlay()
        },

        addIframe: function ( url ) {
            $( "body" ).append(
                `<div class="netseasy-modal" id="netseasy-modal"><div class="netseasy-modal-box" id="netseasy-modal-box"><span class="close-netseasy-modal">&times;</span><iframe class="netseasy-iframe" id="netseasy-iframe" src="${ url }"></iframe></div></div>`,
            )

            $( ".close-netseasy-modal" ).on( "click", netsEasyForWooCommerce.closeOverlay )
        },

        closeOverlay: function () {
            $( ".netseasy-modal" ).hide()
            $( "form.checkout" ).removeClass( "processing" ).unblock()
            $( ".woocommerce-checkout-review-order-table" ).unblock()
            $( "form.checkout" ).unblock()
        },
    }

    netsEasyForWooCommerce.init()
} )
