var url = window.location.href;

//jQuery(document).ready(function () {
	jQuery("#tinymcefourconfiguration").attr("action", url);
//});

jQuery("#tinymcefourconfiguration").on("submit", function (ev) {
	//alert(url);
});