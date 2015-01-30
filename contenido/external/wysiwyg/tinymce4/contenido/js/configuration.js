var url = window.location.href;

//jQuery(document).ready(function () {
	jQuery("#tinymcefourconfiguration").attr("action", url);
//});

jQuery("#tinymcefourconfiguration").on("submit", function (ev) {
	//alert(url);
});

// hack not selector for ie8
jQuery('fieldset').not('#externalplugins').find('label').not(".checkbox").css({"float": "left", "width": "5em"});

// add plus buttons to specify multiple external plugins
//jQuery('#externalplugins').find('.externalplugin').add(jQuery('<input />')).append(jQuery(""));
jQuery('#externalplugins').append(jQuery('<input />'));