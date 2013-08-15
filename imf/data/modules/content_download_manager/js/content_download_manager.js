$(document).ready(function() {

    $('.all').on('click', function() {
        $('.selectedBox').attr('checked', $(this).is(":checked"));
    });

});