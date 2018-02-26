if (jQuery.fn.bindFirst == undefined) {
    jQuery.fn.bindFirst = function (name, fn) {
        this.on(name, fn);
        this.each(function () {
            var handlers = jQuery._data(this, 'events')[name.split('.')[0]];
            var handler = handlers.pop();
            handlers.splice(0, 0, handler);
        });
    };
}

function Urbit() {

    this.url = null;
    this.openhours = [];

    this.init = function (hours) {
        if (hours != undefined && hours != null) {
            this.openhours = hours;
        }
    };

    this.addValidation = function () {

        var postalObject = jQuery("#shipping\\:postcode").val() != "" ? jQuery("#shipping\\:postcode") : jQuery("#billing\\:postcode");
        var telephoneObject = jQuery("#shipping\\:telephone").val() != "" ? jQuery("#shipping\\:telephone") : jQuery("#billing\\:telephone");

        this.updateInputs();
        this.getPossibleDays();
        this.checkHoursAndMinutesNeed();

        jQuery(".sp-methods input[type='radio']").click(function () {
            urbit.updateInputs();
        });

        postalObject.on('keyup change', function () {
            urbit.updateInputs();
        });

        if (!telephoneObject.hasClass("cellphone-validation")) {
            telephoneObject.addClass("cellphone-validation");
        }

        telephoneObject.on('change keyup blur input', function () {
            urbit.updateInputs();
        });

        jQuery("#co-billing-form, #co-shipping-form, .vco-form").on('mouseenter', function () {
            urbit.updateInputs();
        });

        jQuery(".address-validation").on('change', function () {
            urbit.validateAddress();
        });

        jQuery("#urbit_shipping_day").on('change', function () {
            urbit.checkHoursAndMinutesNeed();
        });

        jQuery("#urbit_shipping_hour").on('change', function () {
            urbit.getPossibleMinutes();
        });

        this.addValidationFields();
    };

    this.checkHoursAndMinutesNeed = function () {

        if (jQuery("#urbit_shipping_day").val() === "now" || !jQuery("#urbit_shipping_day").val()) {
            jQuery('#urbit-specific-time-block').css('display', 'none');
        } else {
            jQuery('#urbit-specific-time-block').css('display', 'block');
            this.getPossibleHours();
        }
    };

    this.getPossibleDays = function () {
        jQuery.ajax({
            url: jQuery('#ajax-delivery-days-url').val(),
            type: 'post',
            success: function (possibleDates) {

                if (possibleDates) {
                    var options = '';

                    jQuery.each(possibleDates, function (i, val) {
                        options += '<option value="' + val.date + '">' + val.label + '</option>';
                    });

                    jQuery('#urbit_shipping_day').html(options);
                    urbit.checkHoursAndMinutesNeed();
                }
            }
        });
    };

    this.getPossibleHours = function () {
        this.disableHours(true);
        this.disableMinutes(true);
        jQuery('#urbit_shipping_hour').html("");
        jQuery('#urbit_shipping_minute').html("");
        jQuery.ajax({
            url: jQuery('#ajax-delivery-hours-url').val(),
            data: {
                selected_date: jQuery("#urbit_shipping_day").val()
            },
            type: 'post',
            success: function (possibleHours) {
                var options = '<option value="">Hour</option>';

                jQuery.each(possibleHours, function (i, val) {
                    options += '<option value="' + val + '">' + val + '</option>';
                });

                jQuery('#urbit_shipping_hour').html(options);

                urbit.disableHours(false);
                urbit.disableMinutes(false);
            }
        });
    };

    this.getPossibleMinutes = function () {
        this.disableMinutes(true);
        jQuery('#urbit_shipping_minute').html("");
        jQuery.ajax({
            url: jQuery('#ajax-delivery-minutes-url').val(),
            data: {
                selected_date: jQuery("#urbit_shipping_day").val(),
                selected_hour: jQuery("#urbit_shipping_hour").val()
            },
            type: 'post',
            success: function (possibleMinutes) {
                var options = '<option value="">Minute</option>';

                jQuery.each(possibleMinutes, function (i, val) {
                    options += '<option value="' + val + '">' + val + '</option>';
                });

                jQuery('#urbit_shipping_minute').html(options);

                urbit.disableMinutes(false);
            }
        });
    };

    this.disableHours = function (disabled) {
        if (disabled) {
            jQuery('#urbit_shipping_hour').prop('disabled', 'disabled');
        } else {
            jQuery('#urbit_shipping_hour').prop('disabled', false);
        }
    };

    this.disableMinutes = function(disabled) {
        if (disabled) {
            jQuery('#urbit_shipping_minute').prop('disabled', 'disabled');
        } else {
            jQuery('#urbit_shipping_minute').prop('disabled', false);
        }
    }


    this.updateInputs = function () {
        var firstNameObject = jQuery("#shipping\\:firstname").val() != "" ? jQuery("#shipping\\:firstname") : jQuery("#billing\\:firstname");
        var lastNameObject = jQuery("#shipping\\:lastname").val() != "" ? jQuery("#shipping\\:lastname") : jQuery("#billing\\:lastname");
        var postalObject = jQuery("#shipping\\:postcode").val() != "" ? jQuery("#shipping\\:postcode") : jQuery("#billing\\:postcode");
        var telephoneObject = jQuery("#shipping\\:telephone").val() != "" ? jQuery("#shipping\\:telephone") : jQuery("#billing\\:telephone");
        var addressObject = jQuery("#shipping\\:street1").val() != "" ? jQuery("#shipping\\:street1") : jQuery("#billing\\:street1");
        var cityObject = jQuery("#shipping\\:city").val() != "" ? jQuery("#shipping\\:city") : jQuery("#billing\\:city");
        var emailObject = jQuery("#billing\\:email");

        jQuery("#urbit_shipping_firstname").val(firstNameObject.val());
        jQuery("#urbit_shipping_lastname").val(lastNameObject.val());
        jQuery("#urbit_shipping_postcode").val(postalObject.val());
        jQuery("#urbit_shipping_telephone").val(telephoneObject.val());
        jQuery("#urbit_shipping_street").val(addressObject.val());
        jQuery("#urbit_shipping_city").val(cityObject.val());
        jQuery("#urbit_shipping_email").val(emailObject.val());

        this.validateAddress();
    };

    /**
     * Show error Malformed address / Address outside the delivery area
     * @param show boolean
     */
    this.showAddressValidationError = function (show) {
        var displayValue = show ? 'block' : 'none';
        jQuery("#hp_urbit_address_validation_error").css('display', displayValue);
    };

    /**
     * Disable / Enable shipping methods continue button
     * @param show boolean
     */
    this.disableContinueBtn = function (disable) {
        jQuery("#shipping-method-buttons-container").find("button").prop('disabled', disable);
    };

    /**
     * Validate street, postcode and city by call to Urb-it API
     */
    this.validateAddress = function () {

        if (!window.__fieldValidationAjax_Flag) {
            window.__fieldValidationAjax_Flag = 1;
        }

        var street = jQuery("#urbit_shipping_street").val();
        var postcode = jQuery("#urbit_shipping_postcode").val();
        var city = jQuery("#urbit_shipping_city").val();

        this.disableContinueBtn();

        window.__fieldValidationAjax_Flag = Math.random();
        var local__fieldValidationAjax_Flag = window.__fieldValidationAjax_Flag;

        jQuery.ajax({
            url: jQuery('#ajax-validation-url').val(),
            type: 'post',
            data: {
                street: street,
                postcode: postcode,
                city: city
            },

            success: function (response) {
                if (window.__fieldValidationAjax_Flag !== local__fieldValidationAjax_Flag) {
                    return;
                }

                if (response.ajaxCheckValidateDelivery === "false") {
                    urbit.showAddressValidationError(true);
                    urbit.disableContinueBtn(true);
                } else {
                    urbit.showAddressValidationError(false);
                    urbit.disableContinueBtn(false);
                }
            }
        });
    };

    this.addValidationFields = function () {
        Validation.addAllThese([
            ['require-empty-validation', 'This is a mandatory field', function (value) {
                if (jQuery('#s_method_urbit_onehour_urbit_specific').is(':checked')) {
                    if (value == "") {
                        return false;
                    }
                }

                return true;
            }],
            ['urbit-time-validation', 'This is a mandatory field', function (value) {
                if (jQuery('#s_method_urbit_onehour_urbit_specific').is(':checked')) {

                    if (jQuery("#urbit_shipping_day").val() !== "now" && value === "") {
                        return false;
                    }
                }

                return true;
            }],
        ]);
    }

}