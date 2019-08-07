jQuery(function($) {
    const dibs_wc = {
        bodyEl: $('body'),

        // Payment method
        paymentMethodEl: $('input[name="payment_method"]'),
		paymentMethod: '',
        
        // Extra checkout fields.
		blocked: false,
		extraFieldsSelectorText: 'div#dibs-extra-checkout-fields input[type="text"], div#dibs-extra-checkout-fields input[type="password"], div#dibs-extra-checkout-fields textarea, div#dibs-extra-checkout-fields input[type="email"], div#dibs-extra-checkout-fields input[type="tel"]',
		extraFieldsSelectorNonText: 'div#dibs-extra-checkout-fields select, div#dibs-extra-checkout-fields input[type="radio"], div#dibs-extra-checkout-fields input[type="checkbox"], div#dibs-extra-checkout-fields input.checkout-date-picker, input#terms input[type="checkbox"]',

        /*
		 * Document ready function. 
		 * Runs on the $(document).ready event.
		 */
		documentReady: function() {
			//dibs_wc.DibsFreeze();
			
			// Extra checkout fields.
			dibs_wc.setFormFieldValues();
			dibs_wc.checkFormData();
            dibs_wc.moveExtraCheckoutFields();
            let fieldData = JSON.parse( sessionStorage.getItem( 'DIBSFieldData' ) );
            $('#order_comments').val( fieldData.order_comments );
        },
        
        /*
		 * Check if DIBS Easy is the selected gateway.
		 */
		DibsIsSelected: function() {
			if (dibs_wc.paymentMethodEl.length > 0) {
				dibs_wc.paymentMethod = dibs_wc.paymentMethodEl.filter(':checked').val();
				if( 'dibs_easy' === dibs_wc.paymentMethod ) {
					return true;
				}
			} 
			return false;
        },

        /*
		 * Locks the iFrame. 
		 */
		DibsFreeze: function() {
			dibsCheckout.freezeCheckout();
		},

        /*
		 * Unlocks the iFrame. 
		 */
		DibsResume: function() {
			if ( ! dibs_wc.blocked ) {
				dibsCheckout.thawCheckout();
			}
		},
        
        /**
		 * Checks for form Data on the page, and sets the checkout fields session storage.
		 */
		checkFormData: function() {
			let form = $('form[name="checkout"] input, form[name="checkout"] select, textarea');
            let requiredFields = [];
            let fieldData = {};
            // Get all form fields.
            for ( i = 0; i < form.length; i++ ) { 
                // Check if the form has a name set.
                if ( form[i]['name'] !== '' ) {
                    let name    = form[i]['name'];
                    let field = $('*[name="' + name + '"]');
                    let required = ( $('p#' + name + '_field').hasClass('validate-required') ? true : false );
                    // Only keep track of non standard WooCommerce checkout fields
                    if ($.inArray(name, wc_dibs_easy.standard_woo_checkout_fields) == '-1' && name.indexOf('[qty]') < 0 && name.indexOf( 'shipping_method' ) < 0 && name.indexOf( 'payment_method' ) < 0 ) {
                        // Only keep track of required fields for validation.
                        if ( required === true ) {
                            requiredFields.push(name);
                        }
                        // Get the value from the field.
                        let value = '';
                        if( field.is(':checkbox') ) {
                            if( field.is(':checked') ) {
                                value = form[i].value;
                            }
                        } else if( field.is(':radio') ) {
                            if( field.is(':checked') ) {
                                value = $( 'input[name="' + name + '"]:checked').val();
                            }
                        } else {
                            value = form[i].value
                        }
                        // Set field data with values.
                        fieldData[name] = value;
                    }
                }
            }
            sessionStorage.setItem( 'DIBSRequiredFields', JSON.stringify( requiredFields ) );
            sessionStorage.setItem( 'DIBSFieldData', JSON.stringify( fieldData ) );
        },
        
        /**
		 * Validates the required fields, checks if they have a value set.
		 */
		validateRequiredFields: function() {
			// Get data from session storage.
			let requiredFields = JSON.parse( sessionStorage.getItem( 'DIBSRequiredFields' ) );
			let fieldData = JSON.parse( sessionStorage.getItem( 'DIBSFieldData' ) );
			// Check if all data is set for required fields.
            let allValid = true;
            if( requiredFields !== null ) {
                for( i = 0; i < requiredFields.length; i++ ) {
                    fieldName = requiredFields[i];
                    if ( '' === fieldData[fieldName] ) {
                        allValid = false;
                    }
                }
            }
            if( false === allValid ) {
                dibs_wc.printValidationMessage();
            }
            return allValid;
        },
        
        /**
		 * Print the validation error message.
		 */
		printValidationMessage: function() {
			if ( ! $('#dibs-required-fields-notice').length ) {
				$('form.checkout').prepend( '<div id="dibs-required-fields-notice" class="woocommerce-NoticeGroup woocommerce-NoticeGroup-updateOrderReview"><ul class="woocommerce-error" role="alert"><li>' +  wc_dibs_easy.required_fields_text + '</li></ul></div>' );
				var etop = $('form.checkout').offset().top;
				$('html, body').animate({
					scrollTop: etop
				}, 1000);
			}
		},

        /**
		 * Sets the form fields values from the session storage.
		 */
		setFormFieldValues: function() {
            let form_data = JSON.parse( sessionStorage.getItem( 'DIBSFieldData' ) );
            if( form_data !== null ) {
                $.each( form_data, function( name, value ) {
                    let field = $('*[name="' + name + '"]');
                    let saved_value = value;
                    // Check if field is a checkbox
                    if( field.is(':checkbox') ) {
                        if( saved_value !== '' ) {
                            field.prop('checked', true);
                        }
                    } else if( field.is(':radio') ) {
                        for ( x = 0; x < field.length; x++ ) {
                            if( field[x].value === value ) {
                                $(field[x]).prop('checked', true);
                            }
                        }
                    } else {
                        field.val( saved_value );
                    }

                });
            }
        },

        /**
		 * Moves all non standard fields to the extra checkout fields.
		 */
		moveExtraCheckoutFields: function() {
			// Move order comments.
			$('.woocommerce-additional-fields').appendTo('#dibs-extra-checkout-fields');

			let form = $('form[name="checkout"] input, form[name="checkout"] select, textarea');
			for ( i = 0; i < form.length; i++ ) {
				let name = form[i]['name'];
				// Check if this is a standard field.
				if ( $.inArray( name, wc_dibs_easy.standard_woo_checkout_fields ) === -1 ) {
					// This is not a standard Woo field, move to our div.
					$('p#' + name + '_field').appendTo('#dibs-extra-checkout-fields');
				}
			}
        },
        
        /**
		 * Handle hashchange triggered when Woo order is created.
		 */
        handleHashChange : function(event){
			console.log('hashchange');
			var currentHash = location.hash;
			var splittedHash = currentHash.split("=");
            console.log(splittedHash[0]);
            console.log(splittedHash[1]);
            if(splittedHash[0] === "#dibseasy"){
                var response = JSON.parse( atob( splittedHash[1] ) );
                window.dibsRedirectUrl = response.redirect_url;
                console.log('response.return_url');
                console.log(response.return_url);
                sessionStorage.setItem( 'DIBSRedirectUrl', response.return_url );

                $('form.checkout').removeClass( 'processing' ).unblock();

				dibsCheckout.send('payment-order-finalized', true);
            }
        },
        
        /*
		 * Initiates the script and sets the triggers for the functions.
		 */
		init: function() {
			// Check if DIBS Easy is the selected payment method before we do anything.
			if( dibs_wc.DibsIsSelected() ) {

                $(document).ready( dibs_wc.documentReady() );

				// Extra checkout fields.
				dibs_wc.bodyEl.on('blur', dibs_wc.extraFieldsSelectorText, dibs_wc.checkFormData);
				dibs_wc.bodyEl.on('change', dibs_wc.extraFieldsSelectorNonText, dibs_wc.checkFormData);
                dibs_wc.bodyEl.on('click', 'input#terms', dibs_wc.checkFormData);
                window.addEventListener("hashchange", dibs_wc.handleHashChange);

			}
		},
    }
    dibs_wc.init();

    var i = 0;
    var x = 0;
    var checkout_initiated = wc_dibs_easy.checkout_initiated;
    var paymentId = wc_dibs_easy.paymentId;
            
    $( document ).ready( function() {
        if ("dibs_easy" === $("input[name='payment_method']:checked").val() ) {
            addressChangedListener();
            paymentInitializedListener();
            paymentCompletedListener();
        }
    });
    
    // Address updated in Easy checkout
    function addressChangedListener() {
        dibsCheckout.on('address-changed', function (address) {
            if( address ) {
                console.log('address-changed');    
                console.log(address);
                $.ajax(
                    wc_dibs_easy.customer_adress_updated_url,
                    {
                        type: "POST",
                        dataType: "json",
                        async: true,
                        data: {
                            action:		'customer_adress_updated',
                            address 	: address
                        },
                        success: function (response) {
                        },
                        error: function (response) {
                        },
                        complete: function (response) {
                            console.log('customer_adress_updated ');
                            console.log( response.responseJSON.data );
                            if( 'yes' == response.responseJSON.data.updateNeeded ) {
                                $( '#billing_country' ).val( response.responseJSON.data.country );
                                $( '#shipping_country' ).val( response.responseJSON.data.country );
                                $( 'input#billing_postcode' ).val( response.responseJSON.data.postCode );
                                $( 'input#shipping_postcode' ).val( response.responseJSON.data.postCode )
                            }
                            
                            if( 'yes' == response.responseJSON.data.mustLogin ) {
                                // Customer might need to login. Inform customer and freeze DIBS checkout.
                                var $form = $( 'form.checkout' );
                                $form.prepend( '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-updateOrderReview"><ul class="woocommerce-error" role="alert"><li>' + response.responseJSON.data.mustLoginMessage + '</li></ul></div>' );
                                dibsCheckout.freezeCheckout();
                                
                                var etop = $('form.checkout').offset().top;
                                $('html, body').animate({
                                    scrollTop: etop
                                  }, 1000);
                            } else {
                                // All good release checkout and trigger update_checkout event
                                dibsCheckout.thawCheckout();
                                $(document.body).trigger('update_checkout');
                            }
                        }
                    }
                );
            }
        });
    }

    // When customer clicks Pay button in Easy. Before redirect to 3DSecure.
    function paymentInitializedListener() {
        dibsCheckout.on('pay-initialized', function(response) {
            $(document.body).trigger('dibs_pay_initialized');
            console.log('dibs_pay_initialized');
            console.log(response);
            processWooCheckout(response);
        });
    }

    //After payment is complete
    function paymentCompletedListener() {
        dibsCheckout.on('payment-completed', function (response) {
            console.log('payment-completed');
            console.log(response.paymentId);
            //DIBS_Payment_Success(response.paymentId);
            var redirectUrl = sessionStorage.getItem( 'DIBSRedirectUrl' );
            console.log(redirectUrl);
            if( redirectUrl ) {
                window.location.href = redirectUrl;
            }
        });
    }

    function processWooCheckout(paymentId) {

        // $('body').addClass( 'dibs-checkout-processing' );
        // $( 'body' ).append( $( '<div class="dibs-modal"><div class="dibs-modal-content">' + wc_dibs_easy.dibs_process_order_text + '</div></div>' ) );
        $.ajax(
            wc_dibs_easy.get_order_data_url,
            {
                type: "POST",
                dataType: "json",
                async: true,
                data: {
                    action:		'payment_success',
                    'paymentId': paymentId
                },
                success: function (data) {
                    console.log(data);
                    if( false === data.success ) {
                        console.log( 'PaymentID already exist in order' );
                        console.log( data );
                        if( data.data.redirect ) {
                            window.location.href = data.data.redirect;
                        }
                    } else {

                        $("form.checkout #billing_address_1").val(data.data.payment.consumer.shippingAddress.addressLine1);
                        $("form.checkout #billing_postcode").val(data.data.payment.consumer.shippingAddress.postalCode);
                        $("form.checkout #billing_city").val(data.data.payment.consumer.shippingAddress.city);
                        $("form.checkout #billing_country").val(data.data.payment.consumer.shippingAddress.country);
                        
                        $("form.checkout #shipping_address_1").val(data.data.payment.consumer.shippingAddress.addressLine1);
                        $("form.checkout #shipping_postcode").val(data.data.payment.consumer.shippingAddress.postalCode);
                        $("form.checkout #shipping_city").val(data.data.payment.consumer.shippingAddress.city);
                        $("form.checkout #shipping_country").val(data.data.payment.consumer.shippingAddress.country);

                        if(data.data.payment.consumer.company.name != null) {
                            // B2B purchase
                            $("form.checkout #billing_company").val(data.data.payment.consumer.company.name);
                            $("form.checkout #shipping_company").val(data.data.payment.consumer.company.name);
                            $("form.checkout #billing_first_name").val(data.data.payment.consumer.company.contactDetails.firstName);
                            $("form.checkout #billing_last_name").val(data.data.payment.consumer.company.contactDetails.lastName);
                            $("form.checkout #shipping_first_name").val(data.data.payment.consumer.company.contactDetails.firstName);
                            $("form.checkout #shipping_last_name").val(data.data.payment.consumer.company.contactDetails.lastName);
                            $("form.checkout #billing_email").val(data.data.payment.consumer.company.contactDetails.email);
                            $("form.checkout #billing_phone").val(data.data.payment.consumer.company.contactDetails.phoneNumber.prefix + data.data.payment.consumer.company.contactDetails.phoneNumber.number);
                        } else {
                            // B2C purchase
                            $("form.checkout #billing_company").val('');
                            $("form.checkout #shipping_company").val('');
                            $("form.checkout #billing_first_name").val(data.data.payment.consumer.privatePerson.firstName);
                            $("form.checkout #billing_last_name").val(data.data.payment.consumer.privatePerson.lastName);
                            $("form.checkout #shipping_first_name").val(data.data.payment.consumer.privatePerson.firstName);
                            $("form.checkout #shipping_last_name").val(data.data.payment.consumer.privatePerson.lastName);
                            $("form.checkout #billing_email").val(data.data.payment.consumer.privatePerson.email);
                            $("form.checkout #billing_phone").val(data.data.payment.consumer.privatePerson.phoneNumber.prefix + data.data.payment.consumer.privatePerson.phoneNumber.number);
                        }
                        
                        if(data.data.payment.consumer.shippingAddress.addressLine2 != null) {
                            $("form.checkout #billing_address_2").val(data.data.payment.consumer.shippingAddress.addressLine2);
                            $("form.checkout #shipping_address_2").val(data.data.payment.consumer.shippingAddress.addressLine2);
                        }

                        // Check Terms checkbox, if it exists
                        if ($("form.checkout #terms").length > 0) {
                            $("form.checkout #terms").prop("checked", true);
                        }
                        $('input#ship-to-different-address-checkbox').prop('checked', true);
                        $('form.woocommerce-checkout').append( '<input type="hidden" id="dibs_payment_id" name="dibs_payment_id" value="' + paymentId + '" />' )
                        $('form[name="checkout"]').submit();
                        $('form.woocommerce-checkout').addClass( 'processing' );

                    } 
                },
                error: function (data) {
                },
                complete: function (data) {
                }
            }
        );
       
    }

    // Update DIBS Easy checkout (after Woo updated_checkout)
    function update_checkout() {
        if( checkout_initiated == 'yes' && wc_dibs_easy.paymentId == null ) {
            console.log('update checkout');
            $.ajax(
                wc_dibs_easy.update_checkout_url,
                {
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action  : 'update_checkout'
                    },
                    success: function(response) {
                        if (true === response.success) {
                            let nonce = response.data.nonce;
                            $('#dibs-nonce-wrapper').html(nonce); // Updates the nonce used on checkout
                            console.log('update checkout success');
                            dibsCheckout.thawCheckout();
                            
                        } else {
                            console.log('error');
                            window.location.href = response.data.redirect_url;
                        }
                    }
                }
            );

        } else {
            checkout_initiated = 'yes';
        }
    }

    
    var wc_dibs_body_class = function wc_dibs_body_class() {
		if ("dibs_easy" === $("input[name='payment_method']:checked").val()) {
			$("body").addClass("dibs-selected").removeClass("dibs-deselected");
		} else {
			$("body").removeClass("dibs-selected").addClass("dibs-deselected");
		}
    };

    // When Select another payment method button is clicked
    $(document).on('click', '#dibs-easy-select-other', function (e) {
        e.preventDefault();
			
        $.ajax(
            wc_dibs_easy.change_payment_method_url,
            {
                type: "POST",
                dataType: "json",
                async: true,
                data: {
                    action:		"dibs_change_payment_method",
                    dibs_easy 	: false
                },
                success: function (data) {
                },
                error: function (data) {
                },
                complete: function (data) {
                    console.log('Change payment method sucess');
                    console.log(data.responseJSON.data.redirect);
					$('body').removeClass('dibs-selected');
					window.location.href = data.responseJSON.data.redirect;
                }
            }
        );
    });
    
    // When payment method is changed
    $(document).on("change", "input[name='payment_method']", function (event) {
        if ( "dibs_easy" === $("input[name='payment_method']:checked").val() ) {	
            $.ajax(
                wc_dibs_easy.change_payment_method_url,
                {
                    type: "POST",
                    dataType: "json",
                    async: true,
                    data: {
                        action:		"dibs_change_payment_method",
                        dibs_easy 	: true
                    },
                    success: function (data) {
                    },
                    error: function (data) {
                    },
                    complete: function (data) {
                        console.log('Change payment method sucess');
                        console.log(data.responseJSON.data.redirect);
                        $('body').removeClass('dibs-deselected');
                        window.location.href = data.responseJSON.data.redirect;
                    }
                }
            );
        }
	});
    
    // When WooCommerce checkout submission fails
	$(document).on("checkout_error", function () {
		if ("dibs_easy" === $("input[name='payment_method']:checked").val()) {
            /*
            var error_message = $( ".woocommerce-NoticeGroup-checkout" ).text();
			$.ajax(
	            wc_dibs_easy.ajax_on_checkout_error_url,
	            {
	                type: "POST",
	                dataType: "json",
	                async: true,
	                data: {
                        action:		"dibs_on_checkout_error",
                        error_message: error_message,
	                },
	                success: function (data) {
					},
					error: function (data) {
					},
					complete: function (data) {
						console.log('dibs checkout error');
						console.log(data.responseJSON);
						window.location.href = data.responseJSON.data.redirect;
					}
	            }
            );
            */
           console.log('responded with payment-order-finalized false');
           dibsCheckout.send('payment-order-finalized', false);
		}
    });
    
    // Suspend DIBS Checkout during WooCommerce checkout update
    $(document).on('update_checkout', function () {
        if ("dibs_easy" === $("input[name='payment_method']:checked").val() && checkout_initiated == 'yes' && paymentId == null ) {
            dibsCheckout.freezeCheckout();
        }
    });

    // Send an updated cart to DIBS after the checkout has been updated in Woo
    $(document).on('updated_checkout', function () {
        if ("dibs_easy" === $("input[name='payment_method']:checked").val()) {
	        update_checkout();
        }
    });

    // Send an updated cart to DIBS after the checkout has been updated in Woo
    $(document).on('blur', function () {
        if ("dibs_easy" === $("input[name='payment_method']:checked").val()) {
	        update_checkout();
        }
    });

});