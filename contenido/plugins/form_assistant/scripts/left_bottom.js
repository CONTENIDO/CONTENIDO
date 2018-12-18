
(function(Con, $) {

    $(function() {

        // Get reference to FormAssistant
        var formAssistant = Con.Plugin.FormAssistant;

        /**
         * Add security question for deleting a form.
         */
        $('.pifa-icon-delete-form').on('click', function(e) {
            return confirm(formAssistant.getTrans('confirm_delete_form'));
        });

    });

})(Con, Con.$);
