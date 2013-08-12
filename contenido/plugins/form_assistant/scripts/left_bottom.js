
$(function() {

    /**
     */
    function getTrans(key) {
        // get translations
        var value = pifaTranslations[key];
        // htmldecode value
        value = $('<div/>').html(value).text();
        return value;
    }

    /**
     * Add security question for deleting a form.
     */
    $('.pifa-icon-delete-form').on('click', function(e) {
        return confirm(getTrans('confirm_delete_form'));
    });

});
