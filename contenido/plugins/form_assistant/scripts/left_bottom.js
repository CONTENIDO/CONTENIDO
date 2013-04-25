
$(function() {

    /**
     * Add security question for deleting a form.
     */
    $('.pifa_icon_delete_form').on('click', function(e){
        return confirm('Do you really want to delete this form?');
    });

});
