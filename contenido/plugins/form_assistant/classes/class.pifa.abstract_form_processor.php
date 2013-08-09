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

/**
 * This form post helper should simplify the implementation of typical form
 * processing.
 * Its main method process() wraps the steps read data from request,
 * validate data and persist data in database. In order to make this approach
 * more customizable three protected "event handler" were defined that can be
 * implemented by a concrete implementation of this class. These allow to
 * preprocess the data read from the request before it's going to be validated
 * and to be postprocessed after it's been persisted into the database.
 *
 * @author marcus.gnass
 */
abstract class PifaAbstractFormProcessor {

    /**
     *
     * @var PifaAbstractFormModule
     */
    private $_module = NULL;

    /**
     *
     * @todo should be private as it can be accessed via getForm()
     * @var PifaForm
     */
    protected $_form = NULL;

    /**
     * Instantiates the processor.
     * The processor aggregates the form module and the form.
     * The idform is read from the given modules settings.
     *
     * In former implementations of the processor the modules had no settings
     * and thus no idform. Thats why optionally the idfrm gan be given
     * explicitly. This shoud be removed when all processors are refactored.
     *
     * @param int $idform
     * @throws ModuleException
     */
    public function __construct(PifaAbstractFormModule $module = NULL, $idform = NULL) {
        $this->_module = $module;

        // for backward compatibility
        if (NULL !== $this->_module) {
            $idform = $this->_module->getSetting('pifaform_idform');
        }

        // assure $idform to be an integer
        $idform = cSecurity::toInteger($idform);
        if (0 === $idform) {
            $msg = Pifa::i18n('MISSING_IDFORM');
            throw new PifaException($msg);
        }

        // load form
        $this->_form = new PifaForm($idform);
        if (false === $this->_form->isLoaded()) {
            $msg = Pifa::i18n('FORM_LOAD_ERROR');
            throw new PifaException($msg);
        }
    }

    /**
     *
     * @return PifaAbstractFormModule
     */
    public function getModule() {
        return $this->_module;
    }

    /**
     *
     * @param PifaAbstractFormModule $_module
     */
    public function setModule($_module) {
        $this->_module = $_module;
    }

    /**
     *
     * @return PifaForm
     */
    public function getForm() {
        return $this->_form;
    }

    /**
     *
     * @param PifaForm $_form
     */
    public function setForm($_form) {
        $this->_form = $_form;
    }

    /**
     * Template method to postprocess data that has just been read from request.
     * This can be usefull e.g. to remove default values for certain form
     * fields.
     */
    abstract protected function _processReadData();

    /**
     * Template method to postprocess data that has just been validated.
     * I cannot yet imagine a situatio where this could be useful but added
     * this method for completeness' sake.
     */
    abstract protected function _processValidatedData();

    /**
     * Template method to postprocess data that has just been stored to
     * database.
     * This can be usefull e.g. to send form values via email or process them
     * in another way.
     */
    abstract protected function _processStoredData();

    /**
     * Processes a form.
     * Therefor the forms values are read from the appropriate request,
     * validated and written to database.
     * After each step a method is called that allows to postprocess the forms
     * data or even the form itself. This postprocessing is optional and can be
     * implemented in concrete implementations of this abstratc class
     *
     * @throws ModuleException if there is no form to process
     * @throws PifaValidationException if data is invalid
     * @throws PifaDatabaseException if data could not be stored
     */
    public function process() {

        // assert there is a form to process
        if (NULL === $this->_form) {
            $msg = Pifa::i18n('MISSING_IDFORM');
            throw new PifaException($msg);
        }

        // perform steps as described in documentation

        $this->_form->fromForm();
        $this->_processReadData();

        $this->_form->validate();
        $this->_processValidatedData();

        $this->_form->storeData();
        $this->_processStoredData();
    }
}
