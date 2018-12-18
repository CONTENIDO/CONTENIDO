<?php

/**
 * This file renders the main mail log view.
 *
 * @package Core
 * @subpackage Backend
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $area, $action, $perm;

$page = new cGuiPage('mail_log_overview');

if (!$perm->have_perm_area_action($area)) {
    $page->displayError(i18n('Permission denied'));
    $page->abortRendering();
    $page->render();
    exit();
}

// show notification if mail logging is disabled
if (getSystemProperty('system', 'mail_log') === 'false') {
    $page->displayWarning(i18n('Mail logging is disabled!'));
}

$mailLogCollection = new cApiMailLogCollection();

// execute the actions
if ($action == 'mail_log_delete') {
    // delete all mails and the corresponding success entries with the given
    // idmails
    if (!empty($_REQUEST['idmails'])) {
        $mailLogSuccessCollection = new cApiMailLogSuccessCollection();
        $idmails = json_decode($_REQUEST['idmails'], true);
        foreach ($idmails as $idmail) {
            $mailLogCollection->delete($idmail);
            // also delete the entries in mail_log_success
            $mailLogSuccessCollection->deleteBy('idmail', $idmail);
        }
    }
} else if ($action == 'mail_log_resend') {
    $mailer = new cMailer();
    $mailer->resendMail($_REQUEST['idmailsuccess']);
}

$mailLogCollection->query();

// show notification if there no mails have been logged yet
if ($mailLogCollection->count() === 0) {

    // Display this info only if the mail logging is not disabled (CON-2702)
    if (getSystemProperty('system', 'mail_log') !== 'false') {
        $page->displayInfo(i18n('No mails have been logged yet.'));
    }

    $page->abortRendering();
    $page->render();
    exit();
}

if ($area === 'mail_log' || $area === 'mail_log_overview') {
    // construct the hidden form
    $form = new cHTMLForm('bulk_editing', '', 'post', 'action-form');
    $form->setVar('area', '');
    $form->setVar('frame', '4');
    $form->setVar('action', '');
    $form->setVar('contenido', cRegistry::getSession()->id);
    $form->setVar('idmail', '');
    $form->setVar('idmails', '');
    $page->appendContent($form);

    // add the top bulk editing functions
    $page->appendContent(mailLogBulkEditingFunctions());

    // construct the overview table
    $table = new cHTMLTable();
    $table->setClass('generic');
    $table->setWidth('100%');

    // construct the header
    $headers = array(
        'checkbox' => i18n('Mark'),
        'subject' => i18n('Subject'),
        'to' => i18n('To'),
        'created' => i18n('Date'),
        'client' => i18n('Client'),
        'action' => i18n('Action')
    );
    $thead = new cHTMLTableHeader();
    $tr = new cHTMLTableRow();
    foreach ($headers as $header) {
        $th = new cHTMLTableHead();
        $th->setContent($header);
        $tr->appendContent($th);
    }
    $thead->setContent($tr);
    $table->appendContent($thead);

    // construct the content rows containing the mails
    $tbody = new cHTMLTableBody();
    // iterate over all logged mails
    while (($item = $mailLogCollection->next()) !== false) {
        $tr = new cHTMLTableRow();
        foreach ($headers as $key => $value) {
            $td = new cHTMLTableData();
            switch ($key) {
                case 'checkbox':
                    $checkbox = new cHTMLCheckbox('', $item->get('idmail'), '', false, '', '', '', 'mark_emails');
                    $td->setContent($checkbox->toHtml(false));
                    break;
                case 'client':
                    $idclient = $item->get('idclient');
                    $clientItem = new cApiClient($idclient);
                    $td->setContent($clientItem->get('name') . '&nbsp;');
                    break;
                case 'from':
                case 'to':
                    $addresses = $item->get($key);
                    $addresses = mailLogDecodeAddresses($addresses);
                    $td->setContent($addresses . '&nbsp;');
                    break;
                case 'action':
                    // construct the info link
                    $img = new cHTMLImage('images/info.gif');
                    $link = new cHTMLLink('javascript:void(0)');
                    $link->setEvent('click', 'showInfo(' . $item->get('idmail') . ');');
                    $link->setContent($img);
                    $link->appendStyleDefinition('margin-right', '5px');
                    $td->appendContent($link);
                    // construct the delete link
                    $img = new cHTMLImage('images/delete.gif');
                    $link = new cHTMLLink('javascript:void(0)');
                    $link->setEvent('click', 'Con.showConfirmation("' . i18n('Do you really want to delete this email?') . '", function() { deleteEmails(' . $item->get('idmail') . '); });return false;');
                    $link->setContent($img);
                    $td->appendContent($link);
                    break;
                default:
                    $td->setContent($item->get($key) . '&nbsp;');
            }
            $tr->appendContent($td);
        }
        $tbody->appendContent($tr);
    }
    $table->appendContent($tbody);
    $page->appendContent($table);

    // add the bottom bulk editing functions
    $page->appendContent(mailLogBulkEditingFunctions());
} else if ($area === 'mail_log_detail') {
    if (isset($_REQUEST['idmail']) && is_numeric($_REQUEST['idmail'])) {
        // construct the back button
        $link = new cHTMLLink(cRegistry::getBackendUrl() . 'main.php?area=mail_log&frame=4&contenido=' . cRegistry::getSession()->id);
        $image = new cHTMLImage('images/but_back.gif');
        $image->appendStyleDefinition('cursor', 'pointer');
        $image->appendStyleDefinition('margin-bottom', '10px');
        $link->setContent($image);
        $page->appendContent($link);

        $idmail = $_REQUEST['idmail'];
        // construct the hidden form
        $form = new cHTMLForm('', '', 'post', 'action-form');
        $form->setVar('area', '');
        $form->setVar('frame', '4');
        $form->setVar('action', 'mail_log_overview');
        $form->setVar('contenido', cRegistry::getSession()->id);
        $form->setVar('idmail', $idmail);
        $form->setVar('idmails', '[' . $idmail . ']');
        $form->setVar('idmailsuccess', '');
        $page->appendContent($form);

        // construct the email details table
        $tableHeaderDetail = array(
            'from' => i18n('From'),
            'to' => i18n('To'),
            'reply_to' => i18n('Reply to'),
            'cc' => i18n('CC'),
            'bcc' => i18n('BCC'),
            'subject' => i18n('Subject'),
            'body' => i18n('Body'),
            'created' => i18n('Date')
        );
        $table = new cHTMLTable();
        $table->setClass('generic');
        $table->appendStyleDefinition('border-top', '1px solid #B3B3B3');
        $mailItem = new cApiMailLog($idmail);
        foreach ($tableHeaderDetail as $key => $value) {
            $tr = new cHTMLTableRow();
            $td = new cHTMLTableData();
            $td->setContent($value . '&nbsp;');
            $tr->appendContent($td);
            switch ($key) {
                // addresses are saved JSON-encoded, so decode them accordingly
                case 'from':
                case 'to':
                case 'reply_to':
                case 'cc':
                case 'bcc':
                    $td = new cHTMLTableData();
                    $addresses = mailLogDecodeAddresses($mailItem->get($key));
                    $td->setContent($addresses . '&nbsp;');
                    $tr->appendContent($td);
                    break;
                default:
                    $td = new cHTMLTableData();
                    $data = $mailItem->get($key);
                    if ($mailItem->get('content_type') === 'text/plain') {
                        $data = nl2br($data);
                    }
                    $td->setContent($data . '&nbsp;');
                    $tr->appendContent($td);
            }
            $table->appendContent($tr);
        }
        // construct the action row
        $tr = new cHTMLTableRow();
        $td = new cHTMLTableData();
        $td->setContent(i18n('Action'));
        $tr->appendContent($td);
        $td = new cHTMLTableData();
        $link = new cHTMLLink('javascript:void(0)');
        $link->setClass('con_deletemails');
        $link->setEvent('click', 'Con.showConfirmation("' . i18n('Do you really want to delete this email?') . '", function() { deleteEmails(' . $idmail . '); });return false;');
        $image = new cHTMLImage('images/delete.gif');
        $image->setAlt(i18n('Delete emails'));
        $link->setContent($image);
        $td->setContent($link);
        $tr->appendContent($td);
        $table->appendContent($tr);
        $page->appendContent($table);

        // generate the success table
        $successTable = new cHTMLTable();
        $successTable->setClass('generic');
        $successTable->appendStyleDefinition('margin-top', '20px');
        // get all status entries for the given idmail
        $mailLogSuccessCollection = new cApiMailLogSuccessCollection();
        $mailLogSuccessCollection->select('`idmail`=' . $idmail);
        $tr = new cHTMLTableRow();
        $th = new cHTMLTableHead();
        $th->setContent(i18n('Recipient'));
        $tr->appendContent($th);
        $th = new cHTMLTableHead();
        $th->setContent(i18n('Status'));
        $tr->appendContent($th);
        $successTable->appendContent($tr);

        // construct a table row for each recipient
        while (($mailSuccessItem = $mailLogSuccessCollection->next()) !== false) {
            $tr = new cHTMLTableRow();
            $td = new cHTMLTableData();
            $td->setContent(mailLogDecodeAddresses($mailSuccessItem->get('recipient')));
            $tr->appendContent($td);
            $td = new cHTMLTableData();
            if ($mailSuccessItem->get('success')) {
                // mail has been sent successfully, show OK image
                $image = new cHTMLImage('images/but_ok.gif');
                $image->appendStyleDefinition('display', 'block');
                $image->appendStyleDefinition('margin', '0 auto');
                $image->appendStyleDefinition('padding', '3px');
                $image->setAlt(i18n('Mail sent successfully!'));
                $td->setContent($image);
            } else {
                // mail could not be sent yet, show resend button
                $link = new cHTMLLink('javascript:void(0)');
                $link->setEvent('click', 'resendEmail(' . $mailSuccessItem->get('idmailsuccess') . ')');
                $link->setAlt(i18n('Resend email'));
                $image = new cHTMLImage('images/but_refresh.gif');
                $image->appendStyleDefinition('display', 'block');
                $image->appendStyleDefinition('margin', '0 auto');
                $image->appendStyleDefinition('padding', '3px');
                $link->setContent($image);
                $td->setContent($link);
            }
            $tr->appendContent($td);
            $successTable->appendContent($tr);
        }
        $page->appendContent($successTable);
    } else {
        // no mail has been selected, show error
        $contenidoNotification = new cGuiNotification();
        $contenidoNotification->displayNotification('error', i18n('No item selected!'));
    }
}

$page->render();

/**
 * Takes an associative array where the keys represent the mail addresses
 * and the values optionally represent the mailer name and returns an HTML
 * representation in the following form:
 * Vorname Nachname <vorname.nachname@domain.tld>
 * Vorname2 Nachname2 <vorname2.nachname2@domain2.tld>
 *
 * @param array $addresses
 *         associative array containing the mail addresses as keys
 *         and the mailer names as values
 * @return string
 *         HTML code showing the given mail addresses and names
 */
function mailLogDecodeAddresses($addresses) {
    $result = '';
    $addresses = json_decode($addresses, true);
    if (!is_array($addresses)) {
        return "";
    }
    foreach ($addresses as $mail => $name) {
        $result .= $name . ' &lt;' . $mail . '&gt;<br>';
    }
    $result = cString::getPartOfString($result, 0, cString::getStringLength($result) - 4);

    return $result;
}

/**
 *
 * @return cHTMLTable
 * 
 * @throws cException
 */
function mailLogBulkEditingFunctions() {

    $table = new cHTMLTable();
    $table->setClass('generic');
    $table->setWidth('100%');
    $table->appendStyleDefinition('margin', '10px 0');

    $tr = new cHTMLTableRow();

    $th = new cHTMLTableHead();
    $th->appendStyleDefinition('border-bottom', '1px solid #B3B3B3');

    // construct the invert selection function
    $link = new cHTMLLink();
    $link->setClass('flip_mark');
    $image = new cHTMLImage('images/but_invert_selection.gif');
    $image->setAlt(i18n('Flip Selection'));
    $link->appendContent($image);
    $link->appendContent(' ' . i18n('Flip Selection'));
    $th->appendContent($link);

    // construct the bulk editing functions
    $link = new cHTMLLink('javascript:void(0)');
    $link->setClass('con_deletemails');
    $link->setEvent('click', 'Con.showConfirmation("' . i18n('Do you really want to delete the selected emails?') . '", deleteEmails);return false;');
    $image = new cHTMLImage('images/delete.gif');
    $image->setAlt(i18n('Delete emails'));
    $link->setContent($image);
    $div = new cHTMLDiv(i18n('Apply to all selected emails:'), 'bulk_editing_functions');
    $div->appendContent($link);
    $th->appendContent($div);

    $tr->setContent($th);
    $table->setContent($tr);

    return $table;

}
