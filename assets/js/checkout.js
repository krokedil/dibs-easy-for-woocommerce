(function ($) {
    'use strict';

    var i = 0;
    var x = 0;
    var checkout_initiated = wc_dibs_easy.checkout_initiated;
    var paymentId = wc_dibs_easy.paymentId;
    /*
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
        }
    }
   */
            
    $( document ).ready( function() {
        if ("dibs_easy" === $("input[name='payment_method']:checked").val() ) {

            addressChangedListener();

            paymentCompletedListener();
        }
    });
    
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
                            console.table( response.responseJSON.data);
                            if( 'yes' == response.responseJSON.data.updateNeeded ) {
                                $( '#billing_country' ).val( response.responseJSON.data.country );
                                $( '#shipping_country' ).val( response.responseJSON.data.country );
                                $( 'input#billing_postcode' ).val( response.responseJSON.data.postCode );
                                $( 'input#shipping_postcode' ).val( response.responseJSON.data.postCode )
                                $(document.body).trigger('update_checkout'); 
                            }
                        }
                    }
                );
                dibsCheckout.thawCheckout();
            }
        });
    }

    //After payment is complete
    function paymentCompletedListener() {
        dibsCheckout.on('payment-completed', function (response) {
            console.log('payment-completed');
            console.log(response.paymentId);
            DIBS_Payment_Success(response.paymentId);
        });
    }

    function DIBS_Payment_Success(paymentId) {
        if (x === 0) {
            $('body').block({
                message: "",
                baseZ: 99999,
                overlayCSS:
                    {
                        background: "#fff",
                        opacity: 0.6
                    },
                css: {
                    padding:        "20px",
                    zindex:         "9999999",
                    textAlign:      "center",
                    color:          "#555",
                    backgroundColor:"#fff",
                    cursor:         "wait",
                    lineHeight:		"24px",
                }
            });
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
                        $('input#ship-to-different-address-checkbox').prop('checked', true);
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

    $('#order_comments').focusout(function(){
        var text = $('#order_comments').val();
        if( text.length > 0 ) {
            $.ajax(
                wc_dibs_easy.dibs_add_customer_order_note_url,
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

}(jQuery));