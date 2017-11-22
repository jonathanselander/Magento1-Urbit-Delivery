if(jQuery.fn.bindFirst == undefined) {
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

    this.init = function(hours)
    {
        if(hours != undefined && hours != null) {
            this.openhours = hours;
        }
    };

    this.validateOpenHours = function() {
        var selected_date = jQuery("select[name='shipping_delivery[day]']").val();
        if (selected_date != "") {
            for (var i = 0; i < this.openhours.length; i++) {
                if (this.openhours[i].date == selected_date) {
                    var start_hour = this.openhours[i].from.split(":")[0];
                    var end_hour = this.openhours[i].to.split(":")[0];

                    jQuery("select[name='shipping_delivery[hour]'] option").each(function () {
                        jQuery(this).attr('disabled', 'disabled');
                        if (jQuery(this).val() == "" || parseInt(jQuery(this).val()) >= parseInt(start_hour) && parseInt(jQuery(this).val()) <= parseInt(end_hour)) {
                            jQuery(this).removeAttr('disabled');
                        }
                    });

                    var selected_hour = jQuery("select[name='shipping_delivery[hour]']").val();
                    if (selected_hour != "") {

                        var start_minute = 0;
                        var end_minute = 59;

                        if (selected_hour == start_hour) {
                            start_minute = this.openhours[i].from.split(":")[1];
                            end_minute = 59;
                        }
                        if (selected_hour == end_hour) {
                            start_minute = 0;
                            end_minute = this.openhours[i].to.split(":")[1];
                        }

                        jQuery("select[name='shipping_delivery[minute]'] option").each(function () {
                            jQuery(this).attr('disabled', 'disabled');

                            if(jQuery(this).val() == "" || parseInt(jQuery(this).val()) >= parseInt(start_minute) && parseInt(jQuery(this).val()) <= parseInt(end_minute)){
                                jQuery(this).removeAttr('disabled');
                            }
                        });

                    }
                }
            }
        }
    }

    this.addValidation = function(){
        var postalObject = jQuery("#shipping\\:postcode").val() != "" ? jQuery("#shipping\\:postcode") : jQuery("#billing\\:postcode");
        var telephoneObject = jQuery("#shipping\\:telephone").val() != "" ? jQuery("#shipping\\:telephone") : jQuery("#billing\\:telephone");

        jQuery(".sp-methods input[type='radio']").click(function(){
            jQuery(".urbit_postcode").val(postalObject.val());
            jQuery(".urbit_telephone").val(telephoneObject.val());
        })

        postalObject.on('keyup change', function () {
            jQuery(".urbit_postcode").val(postalObject.val());
            jQuery(".urbit_telephone").val(telephoneObject.val());
        });

        if(!telephoneObject.hasClass("cellphone-validation")) {
            telephoneObject.addClass("cellphone-validation");
        }

        telephoneObject.on('change keyup blur input', function () {
            jQuery(".urbit_postcode").val(postalObject.val());
            jQuery(".urbit_telephone").val(telephoneObject.val());
        });

        jQuery("#co-billing-form, #co-shipping-form, .vco-form").on('mouseenter', function(){
            jQuery(".urbit_postcode").val(postalObject.val());
            jQuery(".urbit_telephone").val(telephoneObject.val());
        });

        this.addValidationFields();

    }

    this.addValidationFields = function(){
        Validation.addAllThese([
            ['cellphone-validation', 'Please enter a valid mobile phonenumber (+467.. | 00467.. | 07..)', function(value) {
                var pattern = /^(\+467[0-9]*|00467[0-9]*|07[0-9]*)$/;

                if(jQuery('#s_method_urbit_onehour_urbit_onehour').is(':checked') ||
                    jQuery('#s_method_urbit_onehour_urbit_specific').is(':checked')) {
                    if (value == "" || pattern.test(value) == false) {
                        return false;
                    }
                }
                return true;
            }],
            ['require-empty-validation', 'This is a mandatory field', function(value) {
                var pattern = /^(\+467[0-9]*|00467[0-9]*|07[0-9]*)$/;

                if(jQuery('#s_method_urbit_onehour_urbit_onehour').is(':checked') == false &&
                    jQuery('#s_method_urbit_onehour_urbit_specific').is(':checked')) {
                    if (value == "") {
                        return false;
                    }
                }
                return true;
            }]
        ]);
    }

}