/* global netseasyParams */
jQuery( function ( $ ) {
    var netsEasyForWooCommerce = {
        init: function () {
            window.addEventListener( "hashchange", netsEasyForWooCommerce.handleHashChange )
        },

        handleHashChange: function () {
            var currentHash = location.hash
            var splittedHash = currentHash.split( ":" )
            if ( splittedHash[ 0 ] === "#netseasy" ) {
                var url = atob( splittedHash[ 1 ] )
                netsEasyForWooCommerce.addIframe( url )
            }
        },

        addIframe: function ( url ) {
            $( "body" ).append(
                `<div class="netseasy-modal" id="netseasy-modal"><div class="netseasy-modal-box" id="netseasy-modal-box"><span class="close-netseasy-modal">&times;</span><iframe class="netseasy-iframe" id="netseasy-iframe" src="${ url }"></iframe></div></div>`,
            )

            $( ".close-netseasy-modal" ).on( "click", function () {
                $( ".netseasy-modal" ).hide()
                $( "form.checkout" ).removeClass( "processing" ).unblock()
                $( ".woocommerce-checkout-review-order-table" ).unblock()
                $( "form.checkout" ).unblock()
            } )
        },
    }

    netsEasyForWooCommerce.init()
} )
