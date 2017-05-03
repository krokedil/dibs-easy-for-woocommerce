jQuery(document).ready(function($) {
    var i = 0;
    var x = 0;
    function triggerDIBS() {
        var data = {
            'action': 'create_paymentID'
        };

        jQuery.post(wc_dibs_easy.ajaxurl, data, function (data) {
            if (true === data.success ) {
                var paymentID = data.data.paymentId.paymentId;
                var privateKey = data.data.privateKey;
                var language = data.data.language;
                intitCheckout(paymentID, privateKey, language);
                $('#dibs-complete-checkout').addClass('dibs-easy');
                $('#order_review_heading').addClass('dibs-easy');
                $('#order_review').addClass('dibs-easy');
                $('.form-row.notes').addClass('dibs-easy');
                $('.form-row.notes').insertAfter('#dibs-complete-checkout');
            } else {
                console.log('error');
            }
        });
    }
    // Add the div for the DIBS checkout iFrame
    $('#order_review').after("<div id='dibs-complete-checkout'></div>");

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
            $('#dibs-complete-checkout').empty();
            $('.woocommerce-billing-fields').hide();
            $('.woocommerce-shipping-fields').hide();
            $('.place-order').hide();
            $('#dibs-complete-checkout').show();
            if(i == 0) {
                i = 1;
                triggerDIBS();
            }
        } else{
            $('.woocommerce-billing-fields').show();
            $('.woocommerce-shipping-fields').show();
            $('.place-order').show();
            $('#dibs-complete-checkout').hide();
            $('#dibs-complete-checkout').empty();
            $('#dibs-complete-checkout').removeClass('dibs-easy');
            $('#order_review_heading').removeClass('dibs-easy');
            $('#order_review').removeClass('dibs-easy');
            $('.form-row.notes').removeClass('dibs-easy');

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
    function move_stuff() {
        if (i === 0){
            var order_review_header_parent = $("#order_review").closest("form").prop("id");
            var order_review_parent = $("#order_review").closest("form").prop("id");
            var order_note_parent = $("#order_comments_field").closest("div").prop("id");
        }
    }
});