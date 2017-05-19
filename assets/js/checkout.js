jQuery(document).ready(function($) {
    var i = 0;
    var x = 0;
    function triggerDIBS() {
        // Get current URL
        var url = window.location.href;
        if(url.indexOf('paymentId') != -1){
            if( $('form #billing_first_name').val() != '' ) {
                $("#place_order").trigger("submit");
            }
        }else {
            var data = {
                'action': 'create_paymentID'
            };
            jQuery.post(wc_dibs_easy.ajaxurl, data, function (data) {
                if (true === data.success) {
                    var paymentID = data.data.paymentId.paymentId;
                    var privateKey = data.data.privateKey;
                    var language = data.data.language;
                    intitCheckout(paymentID, privateKey, language);
                } else {
                    console.log('error');
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
                $('body').addClass('dibs-enabled');

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
            $('body').removeClass('dibs-enabled');
            // Move the elements back to the old location using the temp divs
            $('#order_review_heading').insertAfter('#dibs-temp-div-1');
            $('#order_review').insertAfter('#dibs-temp-div-2');
            $('.woocommerce-additional-fields').insertAfter('dibs-temp-div-3');

            // Remove the temp divs to make sure there is no conflict
            $('#dibs-temp-div-1').remove();
            $('#dibs-temp-div-2').remove();
            $('#dibs-temp-div-3').remove();

            i = 0;
        }
    }
    function DIBS_Payment_Success(paymentId) {
        if (x === 0) {
            var data = {
                'action': 'payment_success',
                'paymentId': paymentId
            };

            jQuery.post(wc_dibs_easy.ajaxurl, data, function (data) {
                var returnCountry = data.data.payment.consumer.shippingAddress.country
                if (returnCountry === "SWE") {
                    var country = "SE"
                }

                $("form.checkout #billing_first_name").val(data.data.payment.consumer.privatePerson.firstName);
                $("form.checkout #billing_last_name").val(data.data.payment.consumer.privatePerson.lastName);
                $("form.checkout #billing_email").val(data.data.payment.consumer.privatePerson.email);
                $("form.checkout #billing_country").val(country);
                $("form.checkout #billing_address_1").val(data.data.payment.consumer.shippingAddress.addressLine1);
                $("form.checkout #billing_city").val(data.data.payment.consumer.shippingAddress.city);
                $("form.checkout #billing_postcode").val(data.data.payment.consumer.shippingAddress.postalCode);
                $("form.checkout #billing_phone").val(data.data.payment.consumer.privatePerson.phoneNumber.prefix + data.data.payment.consumer.privatePerson.phoneNumber.number);

                $("#place_order").trigger("submit");
            });
            x = 1;
        }
    }
});