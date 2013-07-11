
$(function() {

    /**
     * Add security question for deleting a form.
     */
    $('.pifa-icon-delete-form').on('click', function(e) {
        var confirmed = confirm('Do you really want to delete this form?')
        return confirmed;
    });

});
