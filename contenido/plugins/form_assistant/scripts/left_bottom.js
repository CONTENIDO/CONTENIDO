
(function(Con, $) {

    $(function() {

        // Get reference to FormAssistant
        var formAssistant = Con.Plugin.FormAssistant;

        /**
         * Add security question for deleting a form.
         * @deprecated [29.02.2020], see function actionDeleteForm in template.left_bottom.html
         */
        $('.pifa-icon-delete-form').on('click', function(e) {
            return confirm(formAssistant.getTrans('confirm_delete_form'));
        });

    });

})(Con, Con.$);
