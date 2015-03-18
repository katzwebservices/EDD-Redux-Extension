/*global jQuery, document, redux, ajaxurl */
(function( $ ) {
	"use strict";

	redux.field_objects = redux.field_objects || {};
	redux.field_objects.edd_license = redux.field_objects.edd_license || {};

	redux.field_objects.edd_license.init = function() {
        jQuery('.redux-edd-input').change(function() {
            jQuery(this).parent().find('.redux-edd-status').val('');
        });
        jQuery(document).on("click", ".redux-EDDAction", function(e) {
            e.preventDefault();
            var parent = jQuery(this).parents('.redux-container-edd_license:first');
            var id = jQuery(this).attr('data-id');
            var theData = {};
            parent.find('.redux-edd').each(function() {
                theData[jQuery(this).attr('id').replace(id + '-', '')] = jQuery(this).val();
            });
            jQuery('#' + id + '-notice').attr('class', "redux-info-field redux-info");
            jQuery('#' + id + '-status_notice').html(jQuery('#' + id + '-notice').data('verify'));
            jQuery('#' + id + '-status_notice').attr('background-image');

            theData.edd_action = jQuery(this).attr('data-edd_action');
            theData.opt_name = redux.args.opt_name;
            jQuery.post(
            ajaxurl, {
                'action': 'redux_edd_' + redux.args.opt_name + '_license',
                'data': theData
            }, function(response) {
                response = jQuery.parseJSON(response);
                jQuery('#' + id + '-status').val(response.license);
                jQuery('#' + id + '-status_notice').html(response.message);
                if (response.license === "valid") {
                    //jQuery('#'+id+'-notice').switchClass( "big", "blue", 1000, "easeInOutQuad" );
                    jQuery('#' + id + '-notice').attr('class', "redux-info-field redux-success");
                    jQuery('#' + id + '-activate').fadeOut('medium', function() {
                        jQuery('#' + id + '-deactivate').fadeIn().css("display", "inline-block");
                    });
                } else if (response.license === "failed") {
                    jQuery('#' + id + '-notice').attr('class', "redux-info-field redux-critical");
                } else if (response.license === "deactivated") {
                    jQuery('#' + id + '-notice').attr('class', "redux-info-field redux-warning");
                    jQuery('#' + id + '-deactivate').fadeOut('medium', function() {
                        jQuery('#' + id + '-activate').fadeIn().css("display", "inline-block");
                    });
                } else { // Inactive or bad
                    jQuery('#' + id + '-deactivate').fadeOut('medium', function() {
                        jQuery('#' + id + '-notice').attr('class', "redux-info-field redux-critical");
                        jQuery('#' + id + '-activate').fadeIn().css("display", "inline-block");
                    });
                }
            });
        });
    };
})(jQuery);
