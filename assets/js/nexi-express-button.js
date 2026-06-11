/* global Easy, nexiExpressParams */
( function () {
	'use strict';

	if ( typeof Easy === 'undefined' || ! nexiExpressParams ) {
		return;
	}

	var params    = nexiExpressParams;
	var easy      = Easy( { checkoutKey: params.checkoutKey, language: params.locale } );
	var widget    = null;
	var container = document.getElementById( 'nexi-express-button-container' );

	if ( ! container ) {
		return;
	}

	/**
	 * Sends an AJAX POST to the given WC AJAX endpoint.
	 *
	 * @param {string}   url      WC AJAX URL.
	 * @param {Object}   data     POST body (nonce added automatically).
	 * @param {Function} success  Called with the `data` property of a success response.
	 * @param {Function} error    Called with an error message string.
	 */
	function ajax( url, data, success, error ) {
		var body    = Object.assign( { nonce: params.nonce }, data );
		var encoded = Object.keys( body )
			.map( function ( k ) {
				return encodeURIComponent( k ) + '=' + encodeURIComponent( body[ k ] );
			} )
			.join( '&' );

		var xhr = new XMLHttpRequest();
		xhr.open( 'POST', url, true );
		xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8' );
		xhr.onreadystatechange = function () {
			if ( xhr.readyState !== 4 ) {
				return;
			}
			try {
				var res = JSON.parse( xhr.responseText );
				if ( res.success ) {
					success( res.data );
				} else {
					error( res.data || 'Unknown error' );
				}
			} catch ( e ) {
				error( 'Invalid response from server.' );
			}
		};
		xhr.send( encoded );
	}

	/**
	 * Initialises the Express widget once we have a payment ID.
	 *
	 * @param {string} paymentId Nexi payment ID.
	 */
	function initWidget( paymentId ) {
		widget = easy.renderExpress( {
			paymentId:   paymentId,
			containerId: 'nexi-express-button-container',
		} );

		widget.on( 'shippingaddresschange', function ( event ) {
			ajax(
				params.shippingUpdateUrl,
				{
					payment_id:   paymentId,
					country_code: event.address.countryCode,
					postal_code:  event.address.postalCode || '',
				},
				function ( data ) {
					widget.update( { amount: data.amount } );
				},
				function ( msg ) {
					if ( params.debug ) {
						// eslint-disable-next-line no-console
						console.error( '[Nexi Express] Shipping update failed:', msg );
					}
				}
			);
		} );

		widget.on( 'paymentcompleted', function ( event ) {
			ajax(
				params.paymentCompleteUrl,
				{ payment_id: event.paymentId },
				function ( data ) {
					window.location.href = data.redirect;
				},
				function ( msg ) {
					if ( params.debug ) {
						// eslint-disable-next-line no-console
						console.error( '[Nexi Express] Payment complete failed:', msg );
					}
				}
			);
		} );
	}

	/**
	 * Requests a payment ID from the server and initialises the widget.
	 * Called when the page loads (lazy: triggered on first render call by the SDK,
	 * or proactively here so the button appears immediately).
	 */
	function createPayment() {
		var form       = document.querySelector( 'form.cart' );
		var productId  = container.dataset.productId;
		var qtyInput   = form ? form.querySelector( '[name="quantity"]' ) : null;
		var quantity   = qtyInput ? parseInt( qtyInput.value, 10 ) || 1 : 1;

		ajax(
			params.createPaymentUrl,
			{ product_id: productId, quantity: quantity },
			function ( data ) {
				initWidget( data.paymentId );
			},
			function ( msg ) {
				if ( params.debug ) {
					// eslint-disable-next-line no-console
					console.error( '[Nexi Express] Create payment failed:', msg );
				}
				container.style.display = 'none';
			}
		);
	}

	createPayment();
} )();
