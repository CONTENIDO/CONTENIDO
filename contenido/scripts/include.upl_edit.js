$(function() {
    $(".ZipExtract").hide();
    $("#13").hide();
});

function show() {
    if ($("#m8").is(":checked")) {
        $(".ZipExtract").show();
        $("#13").show();
    } else {
        $(".ZipExtract").hide();
        $("#13").hide();
    }
}