
//This function add's new hidden element to the form and submit it.
$(function() {

    $('#create_linkfields').click(function() {
        var input = parseInt($('#text_field').val());
        if (input) {
            $('form[name="editcontent"]').append('<input type="hidden" value="'+input+'" name="linkCount">');
            $('form[name="editcontent"]').submit();
        }
    });

});