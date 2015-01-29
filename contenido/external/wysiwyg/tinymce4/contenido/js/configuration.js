var url = window.location.href;

//jQuery(document).ready(function () {
	jQuery("#tinymcefourconfiguration").attr("action", url);
//});

jQuery("#tinymcefourconfiguration").on("submit", function (ev) {
	//alert(url);
});

// hack not selector for ie8
jQuery('label').not(".checkbox").css({"float": "left", "width": "5em"});
