<?php

/**
 *
 * @package Plugin
 * @subpackage FormAssistant
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

/**
 * This class is the default implementation for PIFA form modules.
 * On a GET request it displays a form. On a POST request the posted data is
 * processed. If an error occurs the form is displayed again only that now all
 * valid data is displayed in the form and error messages (if defined) are
 * displayed for all invalid form fields.
 *
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 */
class DefaultFormModule extends PifaAbstractFormModule {

    /**
     * Handle GET request.
     *
     * @param array $values to be displayed in form
     * @param array $errors to be displayed in form
     * @throws Exception if form could not be loaded
     * @see PifaAbstractFormModule::doGet()
     */
    protected function doGet(array $values = [], array $errors = []) {

        // set template to use
        $this->setTemplateName($this->getSetting('pifaform_template_get'));

        // create and load form
        $pifaForm = new PifaForm($this->getIdform());

        // catch error
        if (true !== $pifaForm->isLoaded()) {
            $msg = Pifa::i18n('FORM_LOAD_ERROR');
            throw new PifaException($msg);
        }

        // set values (keep default values if NULL!)
        $pifaForm->setValues($values);

        // set errors
        $pifaForm->setErrors($errors);

        $actionPath = cUri::getInstance()->build([
            'idart' => cRegistry::getArticleId(),
            'lang' => cRegistry::getLanguageId()
        ], true);

        if (Pifa::isHttps()) {
            $actionPath = str_replace('http://', 'https://', $actionPath);
        }

        // assign rendered form
        $this->getTpl()->assign('form', $pifaForm->toHtml([
            'action' => $actionPath,
            'headline' => $this->getSetting('pifaform_headline')
        ]));
    }

    /**
     * Handle POST request.
     *
     * @see PifaAbstractFormModule::doPost()
     * @throws Exception
     */
    protected function doPost() {

        // set template to use
        $this->setTemplateName($this->getSetting('pifaform_template_post'));

        try {

            // get name of processor class
            $processorClass = $this->getSetting('pifaform_processor');

            // get name of file in which processor class could be found
            $filename = Pifa::fromCamelCase($processorClass);
            $filename = "extensions/class.pifa.$filename.php";
            if (false === file_exists(Pifa::getPath() . $filename)) {
                $msg = Pifa::i18n('MISSING_PROCESSOR_FILE');
                $msg = sprintf($msg, $filename);
                throw new PifaException($msg);
            }
            plugin_include(Pifa::getName(), $filename);
            if (false === class_exists($processorClass)) {
                $msg = Pifa::i18n('MISSING_PROCESSOR_CLASS');
                $msg = sprintf($msg, $processorClass);
                throw new PifaException($msg);
            }

            // create processor instance
            // pass module in order to access its settings
            /** @var PifaAbstractFormProcessor $postProcessor */
            $postProcessor = new $processorClass($this);
            $postProcessor->process();

            // assign reply to post template
            $this->getTpl()->assign('reply', [
                'headline' => mi18n("REPLY_HEADLINE"),
                'text' => mi18n("REPLY_TEXT")
            ]);
        } catch (PifaValidationException $e) {

            // display form with valid values again
            $this->doGet($postProcessor->getForm()->getValues(), $e->getErrors());

            // store validation state as (global) application so another module
            // can show a reply text but when validation is successfull
            cRegistry::setAppVar('pifaFormValidity', 'invalid');
        } catch (PifaException $e) {
            // display form with valid values again
            $this->doGet($postProcessor->getForm()->getValues());

            Pifa::displayException($e);
        } catch (Exception $e) {

            Pifa::logException($e);
            Pifa::displayException($e);
        }
    }
}
