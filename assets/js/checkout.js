jQuery(document).ready(function($) {
    // Hide sidebar and give main content 100% width
    $('#secondary').hide();
    $('#primary').css('width', '100%');
    var i = 0;
    function triggerDIBS() {
        var data = {
            'action': 'create_paymentID'
        };

        jQuery.post(wc_dibs_easy.ajaxurl, data, function (data) {
            var paymentID = data.data.paymentId.paymentId;
            var privateKey = data.data.privateKey;
            var language = data.data.language;
            intitCheckout(paymentID, privateKey, language);
        });
    }
    // Add the div for the DIBS checkout iFrame
    $('#customer_details').after("<div class='col2-set' id='dibs-complete-checkout'></div>");

    // Load the iFrame and get response from DIBS after checkout is complete
    function intitCheckout(paymentID, privateKey, language) {
        var checkoutOptions = {
            checkoutKey: privateKey,

            paymentId: paymentID,
            containerId: 'dibs-complete-checkout',
            language: language
        };

        var checkout = new Dibs.Checkout(checkoutOptions);
        $('#dibs-complete-checkout').addClass('col2-set');
        //After payment is complete
        checkout.on('payment-completed', function (response) {

            //Response:
            //paymentId: string (GUID without dashes)
            DIBS_Payment_Success(response.paymentId);
            //window.location = '/PaymentSuccessful';
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
            $('#customer_details').hide();
            $('.place-order').hide();
            $('#dibs-complete-checkout').show();
            if(i == 0) {
                i = 1;
                triggerDIBS();
            }
        } else{
            $('#customer_details').show();
            $('.place-order').show();
            $('#dibs-complete-checkout').hide();
            $('#dibs-complete-checkout').empty();
        }
    }
    function DIBS_Payment_Success(paymentId) {
        var data = {
            'action': 'payment_success',
            'paymentId': paymentId
        };

        jQuery.post(wc_dibs_easy.ajaxurl, data, function (data) {
            var returnCountry = data.data.payment.consumer.shippingAddress.country
            if(returnCountry === "SWE")
            {
                var country = "SE"
            }

            $("form.checkout #billing_first_name").val(data.data.payment.consumer.privatePerson.firstName);
            $("form.checkout #billing_last_name").val(data.data.payment.consumer.privatePerson.lastName);
            $("form.checkout #billing_email").val(data.data.payment.consumer.privatePerson.email);
            $("form.checkout #billing_country").val(country);
            $("form.checkout #billing_address_1").val(data.data.payment.consumer.shippingAddress.addressLine1);
            $("form.checkout #billing_city").val(data.data.payment.consumer.shippingAddress.city);
            $("form.checkout #billing_postcode").val(data.data.payment.consumer.shippingAddress.postalCode);
            $("form.checkout #billing_phone").val(data.data.payment.consumer.privatePerson.phoneNumber.prefix + data.data.payment.consumer.privatePerson.phoneNumber.number );

            $("#place_order").trigger("submit");
        });
    }

});