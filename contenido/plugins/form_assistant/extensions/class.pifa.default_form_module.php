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

/**
 *
 * @author marcus.gnass
 */
class DefaultFormModule extends PifaAbstractFormModule {

    /**
     *
     * @param array $values
     * @param array $errors TODO not yet implemented
     * @throws Exception if form could not be loaded
     */
    protected function doGet(array $values = array(), array $errors = array()) {

        $this->templateName = $this->_settings['pifaform_template_get'];

        // create and load form
        $pifaForm = new PifaForm($this->idform);

        // catch error
        if (true !== $pifaForm->isLoaded()) {
            throw new PifaException('form could not be loaded');
        }

        // set values (keep default values if NULL!)
        $pifaForm->setValues($values);

        // set errors
        $pifaForm->setErrors($errors);

        // assign rendered form
        $this->_tpl->assign('form', $pifaForm->toHtml(array(
            'action' => cUri::getInstance()->build(array(
                'idart' => cRegistry::getArticleId(),
                'lang' => cRegistry::getLanguageId()
            ), true)
        )));

    }

    /**
     */
    protected function doPost() {

        $this->templateName = $this->_settings['pifaform_template_post'];

        $postProcessor = new ContentApplicationFormProcessor($this->idform);
        $postProcessor->fromAddress = $this->fromAddress;
        $postProcessor->fromName = $this->fromName;
        $postProcessor->toAddress = $this->toAddress;
        $postProcessor->clientSubject = $this->clientSubject;
        $postProcessor->systemSubject = $this->systemSubject;

        try {

            $postProcessor->process();

            $this->_tpl->assign('reply', $this->label['reply']);

        } catch (PifaValidationException $e) {

            // display form with valid values again
            $this->doGet($postProcessor->getForm()->getValues(), $e->getErrors());

        }

    }

}

/**
 * Processor for the application form.
 *
 * @author marcus.gnass
 */
class ContentApplicationFormProcessor extends PifaDefaultFormProcessor {

    /**
     *
     * @var string
     */
    public $fromAddress;

    /**
     *
     * @var string
     */
    public $fromName;

    /**
     *
     * @var string
     */
    public $toAddress;

    /**
     *
     * @var string
     */
    public $clientSubject;

    /**
     *
     * @var string
     */
    public $systemSubject;

    /**
     * The given data should be send via email to the systems mail address
     * and a confirmation mail to the user istelf.
     * This feature can be accomplished by a processing handler the class
     * PifaFormAbstractProcessor offers.
     *
     * @see PifaAbstractFormProcessor::_processStoredData()
     */
    protected function _processStoredData() {

        $cfg = cRegistry::getConfig();

        // get values
        $values = $this->getForm()->getValues();

        $errors = array();

        // get body from template
        $tplMail = Contenido_SmartyWrapper::getInstance(true);
        $body = $tplMail->fetch('content_application_form/template/mail_client.tpl');

        // send mail to client
        $successClientMail = $this->_form->toMailRecipient(array(
            'from' => $this->fromAddress,
            'fromName' => $this->fromName,
            'to' => $values['email'],
            'subject' => $this->clientSubject,
            'body' => $body,
            'charSet' => 'UTF-8'
        ));
        if (!$successClientMail) {
            $errors[] = 'mail to client could not be sent';
        }

        // get body from template
        $tplMail = Contenido_SmartyWrapper::getInstance(true);
        $tplMail->assign('values', $values);
        $body = $tplMail->fetch('content_application_form/template/mail_system.tpl');

        // determine attachment names
        // these are already stored in the FS
        $attachmentNames = array();
        if (0 < count($this->getForm()->getFiles())) {
            $tableName = $this->getForm()->get('data_table');
            $lastInsertedId = $this->getForm()->getLastInsertedId();
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

        // determine attachment strings
        // these are created on the fly and include additional fields
        $additionalFields = array(
            // 'zeitstempel' => date(time(),
            // cRegistry::getLanguage()->getProperty('dateformat', 'full')),
            'zeitstempel' => date(time(), 'YmdHis'),
            // Bewerbung
            // Initiativbewerbung
            // Kontaktformular
            // Reparaturauftrag
            // Onlineregistrierung
            // Newsletter Anmeldung
            'formulartyp' => 'Bewerbung'
        );
        $attachmentStrings = array(
            'result.csv' => $this->getForm()->getCsv(true, $additionalFields),
            'result2.csv' => $this->getForm()->getCsv(false, $additionalFields)
        );

        // send mail to system
        $successSystemMail = $this->_form->toMailRecipient(array(
            'from' => $this->fromAddress,
            'fromName' => $this->fromName,
            'to' => $this->toAddress,
            'subject' => $this->systemSubject,
            'body' => $body,
            'attachmentNames' => $attachmentNames,
            'attachmentStrings' => $attachmentStrings,
            'charSet' => 'UTF-8'
        ));

        if (!$successSystemMail) {
            $errors[] = 'mail to system could not be sent';
        }

        if (0 < count($errors)) {
            throw new Exception(implode('<br>', $errors));
        }

    }

}

?>