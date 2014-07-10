<?php

/**
 *
 * @package Plugin
 * @subpackage FormAssistant
 * @version SVN Revision $Rev:$
 * @author marcus.gnass
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

plugin_include(Pifa::getName(), 'extensions/class.pifa.default_form_processor.php');

/**
 * The given data should be send via email to the systems mail address and a
 * confirmation mail to the user itself.
 * This feature can be accomplished by extending the class
 * PifaFormAbstractProcessor and implementing its method _processStoredData().
 *
 * Any uploads of the given form will be added as attachments to the system
 * mail.
 *
 * @author marcus.gnass
 */
class MailedFormProcessor extends DefaultFormProcessor {

    /**
     * Sends client & system mail independantly.
     * If an error occurs on sending the first mail the second mail is sent
     * nonetheless.
     *
     * @see DefaultFormProcessor::_processStoredData()
     * @throws PifaMailException if any mail could not be sent
     */
    protected function _processStoredData() {

        // array to collect errors
        $errors = array();

        // get values
        $values = $this->getForm()->getValues();

        // client mail
        try {
            // get subject from template
            $tpl = cSmartyFrontend::getInstance(true);
            $tpl->assign('values', $values);
            $subject = $tpl->fetch('eval:' . $this->getModule()->getSetting('pifaform_mail_client_subject'));
            // get body from template
            $tpl = cSmartyFrontend::getInstance(true);
            $tpl->assign('values', $values);
            $body = $tpl->fetchGeneral($this->getModule()->getSetting('pifaform_mail_client_template'));
            // send mail
            $this->getForm()->toMailRecipient(array(
                'from' => $this->getModule()->getSetting('pifaform_mail_client_from_email'),
                'fromName' => $this->getModule()->getSetting('pifaform_mail_client_from_name'),
                'to' => $values['email'],
                'subject' => $subject,
                'body' => $body,
                'charSet' => 'UTF-8'
            ));
        } catch (PifaMailException $e) {
            $errors[] = mi18n("PIFA_CLIENT_MAIL") . ": " . $e->getMessage();
        }	

        // system mail
        try {
            // get subject from template
            $tpl = cSmartyFrontend::getInstance(true);
            $tpl->assign('values', $values);
            $subject = $tpl->fetch('eval:' . $this->getModule()->getSetting('pifaform_mail_system_subject'));
            // get body from template
            $tpl = cSmartyFrontend::getInstance(true);
            $tpl->assign('values', $values);
            $body = $tpl->fetchGeneral($this->getModule()->getSetting('pifaform_mail_system_template'));
            // send mail
            $this->getForm()->toMailRecipient(array(
                'from' => $this->getModule()->getSetting('pifaform_mail_system_from_email'),
                'fromName' => $this->getModule()->getSetting('pifaform_mail_system_from_name'),
                'to' => $this->getModule()->getSetting('pifaform_mail_system_recipient_email'),
                'subject' => $subject,
                'body' => $body,
                'attachmentNames' => $this->_getAttachmentNames(),
                'attachmentStrings' => $this->_getAttachmentStrings(),
                'charSet' => 'UTF-8'
            ));
        } catch (PifaMailException $e) {
            $errors[] = mi18n("PIFA_SYSTEM_MAIL") . ": " . $e->getMessage(); 
        }

        // throw errors
        if (0 < count($errors)) {
            throw new PifaMailException(implode('<br>', $errors));
        }
    }

    /**
     * Return all files that were uploaded by the form as names of attachments
     * to be added to the system mail.
     *
     * @return array
     */
    protected function _getAttachmentNames() {

        // determine attachment names
        // these are already stored in the FS
        $attachmentNames = array();
        if (0 < count($this->getForm()->getFiles())) {
            $tableName = $this->getForm()->get('data_table');
            $lastInsertedId = $this->getForm()->getLastInsertedId();
            $cfg = cRegistry::getConfig();
            $destPath = $cfg['path']['contenido_cache'] . 'form_assistant/';
            foreach ($this->getForm()->getFiles() as $column => $file) {
                if (!is_array($file)) {
                    continue;
                }
                $destName = $tableName . '_' . $lastInsertedId . '_' . $column;
                $destName = preg_replace('/[^a-z0-9_]+/i', '_', $destName);
                $attachmentNames[$column] = $destPath . $destName;
            }
        }

        return $attachmentNames;
    }

    /**
     * Returns an empty array cause there are no attachments that will be
     * created on the fly.
     *
     * @return array
     */
    protected function _getAttachmentStrings() {
        return array();
    }
}

?>