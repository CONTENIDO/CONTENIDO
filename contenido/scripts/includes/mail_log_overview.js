/**
 * CONTENIDO mail_log.js JavaScript module.
 *
 * @version    SVN Revision $Rev: 5937 $
 * @requires   jQuery, Con
 * @author     ???
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

(function(Con, $) {

    $(function() {
        // flip selection
        $('a.flip_mark').click(function() {
            $('div.bulk_editing_functions').hide();
            $('input.mark_emails').each(function() {
                // show the bulk editing functions if there
                if ($(this).prop('checked')) {
                    $(this).removeProp('checked');
                } else {
                    $('div.bulk_editing_functions').show();
                    $(this).prop('checked', true);
                }
            });
            return false;
        });

        // show or hide functions on click on checkboxes
       $('input.mark_emails').click(function(eventObject) {
            if ($('input.mark_emails:checked').length > 0) {
                $('div.bulk_editing_functions').show();
            } else {
                $('div.bulk_editing_functions').hide();
            }
            // prevent that the click event is also called on the parent tr object
            eventObject.stopPropagation();
        });

        $('tr').click(function(eventObject) {
            // if user clicked on an action button, do nothing
            if ($(eventObject.target).is('img')) {
                return;
            }
            // only toggle the checkbox if there is one
            var checkbox = $(this).find('input.mark_emails');
            if (checkbox.length === 1) {
                checkbox.prop('checked', !checkbox.prop('checked'));
            }
            // show the bulk editing functions if an email is selected
            if ($('input.mark_emails:checked').length > 0) {
                $('div.bulk_editing_functions').show();
            } else {
                $('div.bulk_editing_functions').hide();
            }
        });
    });

    /**
     * Redirects to the detail page for the mail with the given ID.
     *
     * @param {Number} idmail the ID of the email
     */
    function showInfo(idmail) {
        // set the form values and send the form
        $('form.action-form input[name="area"]').val('mail_log_detail');
        $('form.action-form input[name="idmail"]').val(idmail);
        $('form.action-form').submit();
    }

    /**
     * Sends a request to the server to delete emails.
     * If the idmail is given, the email with this ID is deleted.
     * Otherwise, the IDs are taken from the selected checkboxes.
     *
     * @param {Number} idmail [optional] the ID of the mail
     */
    function deleteEmails(idmail) {
        var idmails = '';
        if (typeof idmail !== 'undefined') {
            // a single email should be deleted
            idmails = '[' + idmail + ']';
        } else {
            // bulk editing: delete the marked items
            idmails = '[';
            $('input.mark_emails:checked').each(function() {
                idmails += $(this).val() + ',';
            });
            idmails = idmails.substring(0, idmails.length - 1);
            idmails += ']';
        }
        // set the form values and send the form
        $('form.action-form input[name="area"]').val('mail_log_overview');
        $('form.action-form input[name="action"]').val('mail_log_delete');
        $('form.action-form input[name="idmails"]').val(idmails);
        $('form.action-form').submit();
    }

    /**
     *
     * @param idmailsuccess
     */
    function resendEmail(idmailsuccess) {
        // set the form values and send the form
        $('form.action-form input[name="area"]').val('mail_log_detail');
        $('form.action-form input[name="action"]').val('mail_log_resend');
        $('form.action-form input[name="idmailsuccess"]').val(idmailsuccess);
        $('form.action-form').submit();
    }

    window.showInfo = showInfo;
    window.deleteEmails = deleteEmails;
    window.resendEmail = resendEmail;

})(Con, Con.$);