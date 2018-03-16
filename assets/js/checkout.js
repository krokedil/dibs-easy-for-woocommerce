jQuery(document).ready(function($) {
    var i = 0;
    var x = 0;
    function triggerDIBS() {
	    
        // Get current URL
        var url = window.location.href;
        if(url.indexOf('paymentId') != -1){
            if( $('form #billing_first_name').val() != '' ) {
	            
	            // Check Terms checkbox, if it exists
                if ($("form.checkout #terms").length > 0) {
                    $("form.checkout #terms").prop("checked", true);
                }
                
                $("#place_order").trigger("submit");
            }
        }else {
            var data = {
                'action': 'create_paymentID',
                'dibs_payment_id' : wc_dibs_easy.dibs_payment_id
            };
            console.log( wc_dibs_easy.dibs_payment_id );
            jQuery.post(wc_dibs_easy.ajaxurl, data, function (data) {
                if (true === data.success) {
                    var paymentID = data.data.paymentId.paymentId;
                    var privateKey = data.data.privateKey;
                    var language = data.data.language;
                    intitCheckout(paymentID, privateKey, language);
                } else {
                    console.log( data.data );
                }
            });
        }
    }
    // Add the div for the DIBS checkout iFrame
    $('form.checkout').append("<div id='dibs-order-review'></div>");
    $('form.checkout').append("<div id='dibs-complete-checkout'></div>");

    // Load the iFrame and get response from DIBS after checkout is complete
    function intitCheckout(paymentID, privateKey, language) {
        var checkoutOptions = {
            checkoutKey: privateKey,

            paymentId: paymentID,
            containerId: 'dibs-complete-checkout',
            language: language
        };

        var checkout = new Dibs.Checkout(checkoutOptions);
        //After payment is complete
        checkout.on('payment-completed', function (response) {
            console.log(response.paymentId);
            DIBS_Payment_Success(response.paymentId);
        });
    }
    if(i === 0) {
        $('body').on('updated_checkout', function () {
            usingGateway();
            $('input[name="payment_method"]').change(function () {
                usingGateway();
            });
        });
    }
    $('body').on('updated_checkout', function () {
        usingGateway();
        i = 0;
    });

    function usingGateway() {
        if ($('form[name="checkout"] input[name="payment_method"]:checked').val() == 'dibs_easy') {
	        
            if($('ul.wc_payment_methods.payment_methods').children().size() == 1){
                $('ul.wc_payment_methods.payment_methods').hide();
            }
            // Hide/Show the different elements and empty the checkout to prevent duplicate iframes
            $('#dibs-complete-checkout').empty();
            $('.woocommerce-billing-fields').hide();
            $('.woocommerce-shipping-fields').hide();
            $('.place-order').hide();
            $('#dibs-complete-checkout').show();

            if(i == 0) {
                // Add body class
				wc_dibs_body_class();
                // Add temp divs before each of the elements that we want to move
                $('#order_review_heading').after('<div id="dibs-temp-div-1"></div>');
                $('#order_review').after('<div id="dibs-temp-div-2"></div>');
                $('.woocommerce-additional-fields').after('<div id="dibs-temp-div-3"></div>');

                // Move the elements
                $('#order_review_heading').appendTo('#dibs-order-review');
                $('#order_review').appendTo('#dibs-order-review');
                $('.woocommerce-additional-fields').appendTo('#dibs-order-review');
                i = 1;
                triggerDIBS();
            }
        } else{
	        
            // Show/Hide the different elements, also empty checkout to prevent duplicate iframes
            $('.woocommerce-billing-fields').show();
            $('.woocommerce-shipping-fields').show();
            $('.place-order').show();
            $('#dibs-complete-checkout').hide();
            $('#dibs-complete-checkout').empty();

            // Remove body class
            wc_dibs_body_class();
            // Move the elements back to the old location using the temp divs
            $('#order_review_heading').insertAfter('#dibs-temp-div-1');
            $('#order_review').insertAfter('#dibs-temp-div-2');
            $('.woocommerce-additional-fields').insertAfter('#dibs-temp-div-3');

            // Remove the temp divs to make sure there is no conflict
            $('#dibs-temp-div-1').remove();
            $('#dibs-temp-div-2').remove();
            $('#dibs-temp-div-3').remove();

            i = 0;
        }
    }
    function DIBS_Payment_Success(paymentId) {
        if (x === 0) {
            $.ajax(
	            wc_dibs_easy.ajaxurl,
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
                            console.log( data );
                        } else {
                            $("form.checkout #billing_first_name").val(data.data.payment.consumer.privatePerson.firstName);
                            $("form.checkout #billing_last_name").val(data.data.payment.consumer.privatePerson.lastName);
                            $("form.checkout #billing_email").val(data.data.payment.consumer.privatePerson.email);
                            $("form.checkout #billing_country").val(data.data.payment.consumer.shippingAddress.country);
                            $("form.checkout #billing_address_1").val(data.data.payment.consumer.shippingAddress.addressLine1);
                            $("form.checkout #billing_city").val(data.data.payment.consumer.shippingAddress.city);
                            $("form.checkout #billing_postcode").val(data.data.payment.consumer.shippingAddress.postalCode);
                            $("form.checkout #billing_phone").val(data.data.payment.consumer.privatePerson.phoneNumber.prefix + data.data.payment.consumer.privatePerson.phoneNumber.number);
                            $("form.checkout #shipping_first_name").val(data.data.payment.consumer.privatePerson.firstName);
                            $("form.checkout #shipping_last_name").val(data.data.payment.consumer.privatePerson.lastName);
                            $("form.checkout #shipping_country").val(data.data.payment.consumer.shippingAddress.country);
                            $("form.checkout #shipping_address_1").val(data.data.payment.consumer.shippingAddress.addressLine1);
                            $("form.checkout #shipping_city").val(data.data.payment.consumer.shippingAddress.city);
                            $("form.checkout #shipping_postcode").val(data.data.payment.consumer.shippingAddress.postalCode);

                            if(data.data.payment.consumer.shippingAddress.addressLine2 != null) {
                                $("form.checkout #billing_address_2").val(data.data.payment.consumer.shippingAddress.addressLine2);
                                $("form.checkout #shipping_address_2").val(data.data.payment.consumer.shippingAddress.addressLine2);
                            }
                        }
                        
                        // Check Terms checkbox, if it exists
                        if ($("form.checkout #terms").length > 0) {
                            $("form.checkout #terms").prop("checked", true);
                        }
                        $("#place_order").trigger("submit");
					},
					error: function (data) {
					},
					complete: function (data) {
					}
	            }
	        );
            x = 1;
        }
    }

    $('#order_comments').focusout(function(){
        var text = $('#order_comments').val();
        if( text.length > 0 ) {
            $.ajax(
                wc_dibs_easy.ajaxurl,
                {
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action  : 'dibs_customer_order_note',
                        order_note : text
                    },
                    success: function(response) {
                    }
                }
            );
        }
    });
    
    var wc_dibs_body_class = function wc_dibs_body_class() {
		if ("dibs_easy" === $("input[name='payment_method']:checked").val()) {
			$("body").addClass("dibs-selected").removeClass("dibs-deselected");
		} else {
			$("body").removeClass("dibs-selected").addClass("dibs-deselected");
		}
    };
    
    // When WooCommerce checkout submission fails
	$(document.body).on("checkout_error", function () {
		if ("dibs_easy" === $("input[name='payment_method']:checked").val()) {
			
			$.ajax(
	            wc_dibs_easy.ajaxurl,
	            {
	                type: "POST",
	                dataType: "json",
	                async: true,
	                data: {
	                    action:		"dibs_on_checkout_error"
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
			
		}
	});
});
