/**
 * CONTENIDO mail_log_overview.js JavaScript module.
 *
 * @requires   jQuery, Con
 * @author     ???
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

(function(Con, $) {

    var DEFAULT_OPTIONS = {
        rootSelector: 'body',
        markMailsSelector: '',
        markMailsCssClass: '',
        bulkEditingFunctionsSelector: '',
        text_deleteConfirmation: 'Do you really want to delete this email?',
        text_deleteMultipleConfirmation: 'Do you really want to delete the selected emails?',
    };

    /**
     * Mail log overview class
     * @class  MailLogOverview
     * @constructor
     * @param {Object}  options  Configuration properties as follows
     * <pre>
     *    rootSelector  (String)  Selector for the root element of this component
     *    markMailsSelector  (String)  Selector for mark mails checkboxes
     *    markMailsCssClass  (String)  CSS classname of mark mails checkboxes
     *    bulkEditingFunctionsSelector  (String)  Selector for the bulk editing functions element
     *    text_deleteConfirmation  (String)
     *    text_deleteMultipleConfirmation  (String)
     * </pre>
     * @return {UplFilesOverview}
     */
    Con.MailLogOverview = function(options) {

        /**
         * @property  {Object}  _options
         * @private
         */
        var _options = $.extend(DEFAULT_OPTIONS, options),
            /**
             * @property  {jQuery}  $_root  Root node of this component
             * @private
             */
            $_root = $(_options.rootSelector),

            $_form, $_bulkEditingFunctions;

        initialize();
        registerEventHandler();

        /**
         * Initialize the component
         */
        function initialize() {
            $_form = $_root.find('form.action-form');
            $_bulkEditingFunctions = $_root.find(_options.bulkEditingFunctionsSelector);
        }

        /**
         * Register event handler
         */
        function registerEventHandler() {
            $_root.find('[data-action]').live('click', function(event) {
                var $element = $(this),
                    action = $element.data('action');

                if (action === 'invert_selection') {
                    actionInvertSelection();
                } else if (action === 'invert_selection_row') {
                    return actionInvertSelectionRow($element, $(event.target));
                } else if (action === 'show_info') {
                    actionShowInfo($element);
                } else if (action === 'delete_email') {
                    actionDeleteEmail($element);
                } else if (action === 'delete_selected_emails') {
                    actionDeleteSelectedEmails();
                } else if (action === 'resend_email') {
                    actionResendEmail($element);
                }
            });

            $_root.find(_options.markMailsSelector).live('change', function () {
                actionOnMarkMailsCheckboxChange();
            });
        }

        /**
         * Action to invert selection of checkboxes
         */
        function actionInvertSelection() {
            $_root.find(_options.markMailsSelector).each(function(pos, item) {
                $(item).prop('checked', !$(item).prop('checked'));
            });
            actionOnMarkMailsCheckboxChange();
        }

        /**
         * Action to invert selection of a checkbox for a row.
         *
         * @param {jQuery} $element
         * @param {jQuery} $target
         * @returns {boolean}
         */
        function actionInvertSelectionRow($element, $target) {
            // If user clicked on an action button, or on the checkbox itself, do nothing
            if ($target.is('img')) {
                return;
            } else if ($target.hasClass(_options.markMailsCssClass)) {
                return;
            }

            // Toggle the checkbox
            var checkbox = $element.find(_options.markMailsSelector);
            checkbox.prop('checked', !checkbox.prop('checked'));

            actionOnMarkMailsCheckboxChange();
            return false;
        }

        /**
         * Action to display the email information detail page.
         *
         * @param {jQuery} $element The clicked info element
         */
        function actionShowInfo($element) {
            var idmail = $element.closest('tr').data('idmail');
            // set the form values and send the form
            $_form.find('input[name="area"]').val('mail_log_detail');
            $_form.find('input[name="idmail"]').val(idmail);
            $_form.submit();
        }

        /**
         * Action on mark mail checkbox change.
         */
        function actionOnMarkMailsCheckboxChange() {
            if ($_root.find(_options.markMailsSelector + ':checked').length > 0) {
                $_bulkEditingFunctions.removeClass('nodisplay');
            } else {
                $_bulkEditingFunctions.addClass('nodisplay');
            }
        }

        /**
         * Action to delete a single email.
         *
         * @param {jQuery} $element The clicked info element
         */
        function actionDeleteEmail($element) {
            var idmail = $element.closest('tr').data('idmail');
            if (!idmail) {
                // Delete on a
                idmail = $element.data('idmail');
            }
            if (!idmail) {
                return;
            }

            Con.showConfirmation(_options.text_deleteConfirmation, function() {
                var idmail = $element.closest('tr').data('idmail');
                submitDeleteEmailForm('[' + idmail + ']');
            });
        }

        /**
         * Action to delete one or multiple selected emails.
         */
        function actionDeleteSelectedEmails() {
            var idmails = [];
            $_root.find(_options.markMailsSelector + ':checked').each(function(pos, element) {
                var idmail = $(element).closest('tr').data('idmail');
                idmails.push('[' + idmail + ']');
            });
            idmails = idmails.join(',');
            if (!idmails.length) {
                return;
            }

            Con.showConfirmation(_options.text_deleteMultipleConfirmation, function() {
                console.log('actionDeleteSelectedEmails idmails', idmails);
                submitDeleteEmailForm(idmails);
            });
        }

        /**
         * Action to submit the form to resend an email.
         *
         * @param {jQuery} $element
         */
        function actionResendEmail($element) {
            var idmailsuccess = $element.data('idmailsuccess');
            $_form.find('input[name="area"]').val('mail_log_detail');
            $_form.find('input[name="action"]').val('mail_log_resend');
            $_form.find('input[name="idmailsuccess"]').val(idmailsuccess);
            $_form.submit();
        }

        /**
         * Submits the form with the action to delete one or multiple emails.
         *
         * @param {String} idmails The email ('[1]') or the emails ('[1],[2],[3]...') to delete
         */
        function submitDeleteEmailForm(idmails) {
            // Set the form values and send the form
            $_form.find('input[name="area"]').val('mail_log_overview');
            $_form.find('input[name="action"]').val('mail_log_delete');
            $_form.find('input[name="idmails"]').val(idmails);
            $_form.submit();
        }

        return {
            // There is nothing public
        };
    };

})(Con, Con.$);