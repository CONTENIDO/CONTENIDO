<?php

/**
 *
 * @package Plugin
 * @subpackage PIFA Form Asistant
 * @version SVN Revision $Rev:$
 * @author marcus.gnass
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') or die('Illegal call');

plugin_include(Pifa::getName(), 'extensions/class.pifa.default_form_processor.php');

/**
 * The given data should be send via email to the systems mail address and a
 * confirmation mail to the user itself.
 * This feature can be accomplished by extending the class
 * PifaFormAbstractProcessor and implementing its method _processStoredData().
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

        $cfg = cRegistry::getConfig();

        // get values
        $values = $this->getForm()->getValues();

        // array to collect errors
        $errors = array();

        // client mail
        try {
            // get body from template
            $tplFile = $this->getModule()->getSetting('pifaform_mail_client_template');
            $tplMail = cSmartyFrontend::getInstance(true);
            $body = $tplMail->fetchGeneral($tplFile);
            // send mail
            $this->getForm()->toMailRecipient(array(
                'from' => $this->getModule()->getSetting('pifaform_mail_client_from_email'),
                'fromName' => $this->getModule()->getSetting('pifaform_mail_client_from_name'),
                'to' => $values['email'],
                'subject' => $this->getModule()->getSetting('pifaform_mail_client_subject'),
                'body' => $body,
                'charSet' => 'UTF-8'
            ));
        } catch (PifaMailException $e) {
            $errors[] = 'client mail could not be sent: ' . $e->getMessage();
        }

        // system mail
        try {
            // get body from template
            $tplFile = $this->getModule()->getSetting('pifaform_mail_system_template');
            $tplMail = cSmartyFrontend::getInstance(true);
            $tplMail->assign('values', $values);
            $body = $tplMail->fetchGeneral($tplFile);
            // send mail
            $this->getForm()->toMailRecipient(array(
                'from' => $this->getModule()->getSetting('pifaform_mail_system_from_email'),
                'fromName' => $this->getModule()->getSetting('pifaform_mail_system_from_name'),
                'to' => $this->getModule()->getSetting('pifaform_mail_system_to_email'),
                'subject' => $this->getModule()->getSetting('pifaform_mail_system_subject'),
                'body' => $body,
                'charSet' => 'UTF-8'
            ));
        } catch (PifaMailException $e) {
            $errors[] = 'system mail could not be sent: ' . $e->getMessage();
        }

        // throw errors
        if (0 < count($errors)) {
            throw new PifaMailException(implode('<br>', $errors));
        }

    }

}

?>