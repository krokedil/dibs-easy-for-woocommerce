/* global netseasyParams */
jQuery(function ($) {
    var netsEasyForWooCommerce = {
        init: function () {
            window.addEventListener("hashchange", netsEasyForWooCommerce.handleHashChange);
        },

        handleHashChange: function() {
            var currentHash = location.hash;
            var splittedHash = currentHash.split(":");
            if( splittedHash[0] === "#netseasy" ){
                var url = atob( splittedHash[1] );
                netsEasyForWooCommerce.addIframe( url );
            }
        },

        addIframe: function( url ) {
            $('body').append( `<div class="netseasy-wrapper" id="netseasy-wrapper"><iframe class="netseasy-iframe" id="netseasy-iframe" src="${url}"></iframe></div>` )
        }
    };

    netsEasyForWooCommerce.init();
});