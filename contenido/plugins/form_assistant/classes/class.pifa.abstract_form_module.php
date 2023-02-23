<?php

/**
 * This file contains the PifaAbstractFormModule class.
 *
 * @package    Plugin
 * @subpackage FormAssistant
 * @author     Marcus Gnaß <marcus.gnass@4fb.de>
 * @copyright  four for business AG
 * @link       https://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Abstract base class for all classes that are used as PIFA form module.
 *
 * In order for an extension class to be displayed in the CMS_PIFAFORM's editor
 * as module class it has to extend this class and implement its abstract
 * methods doGet() & doPost().
 *
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 */
abstract class PifaAbstractFormModule {

    /**
     * The HTTP GET request method.
     *
     * @var string
     */
    const GET = 'GET';

    /**
     * The HTTP POST request method.
     *
     * @var string
     */
    const POST = 'POST';

    /**
     * Array of settings as defined for a content type CMS_PIFAFORM.
     *
     * @var array
     */
    protected $_settings = [];

    /**
     * The unique ID of the form to be displayed and processed by this module.
     * This ID is read from the given settings (pifaform_idform).
     *
     * @var int
     */
    private $_idform = 0;

    /**
     * The current template name to be used when displaying the form.
     * This name usually depends upon the request method to be used.
     * These names are read from the given settings.
     *
     * @var string
     */
    private $_templateName = '';

    /**
     * @var cSmartyWrapper
     */
    private $_tpl = NULL;

    /**
     * @param array $settings as defined for cContentTypePifaForm
     *
     * @throws cException
     */
    public function __construct(array $settings = NULL) {
        $this->_settings = $settings;
        $this->_idform = cSecurity::toInteger($this->_settings['pifaform_idform']);
        $this->_tpl = cSmartyFrontend::getInstance(true);
    }

    /**
     * @return array
     */
    public function getSettings() {
        return $this->_settings;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getSetting($key) {
        return $this->_settings[$key];
    }

    /**
     * @param array $_settings
     */
    public function setSettings(array $_settings) {
        $this->_settings = $_settings;
    }

    /**
     * @return int
     */
    public function getIdform() {
        return $this->_idform;
    }

    /**
     * @param int $_idform
     */
    public function setIdform($_idform) {
        $this->_idform = $_idform;
    }

    /**
     * @return string
     */
    public function getTemplateName() {
        return $this->_templateName;
    }

    /**
     * @param string $_templateName
     */
    public function setTemplateName($_templateName) {
        $this->_templateName = $_templateName;
    }

    /**
     * @return cSmartyWrapper
     */
    public function getTpl() {
        return $this->_tpl;
    }

    /**
     * @param cSmartyWrapper $_tpl
     */
    public function setTpl(cSmartyWrapper $_tpl) {
        $this->_tpl = $_tpl;
    }

    /**
     * Helper method to determine the current request method.
     * The request method is returned as uppercase string.
     *
     * @return string
     */
    protected function _getRequestMethod() {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestMethod = cString::toUpperCase($requestMethod);

        return $requestMethod;
    }

    /**
     * @param bool $return
     *
     * @return mixed|string
     *
     * @throws PifaException if request method is unknown
     */
    public function render($return = false) {

        // dispatch request method
        switch ($this->_getRequestMethod()) {
            case self::GET:

                $this->doGet();
                break;

            case self::POST:

                // always handle POST method in backend edit mode as GET action
                if (cRegistry::isBackendEditMode()) {
                    $this->doGet();
                    break;
                }

                // execute POST only if current form has been submitted
                // and just GET form if POST has another reason (other form etc.)
                if (isset($_POST['idform']) && $_POST['idform'] != $this->getSetting('pifaform_idform')) {
                    $this->doGet();
                    break;
                }

                // handle form as if it were posted
                $this->doPost();
                break;

            default:
                $msg = Pifa::i18n('UNKNOWN_REQUEST_METHOD');
                throw new PifaException($msg);
        }

        // fetch || display template
        $clientConfig = cRegistry::getClientConfig(cRegistry::getClientId());
        $path = $clientConfig['template']['path'];
        if (true === $return) {
            return $this->_tpl->fetch($path . $this->getTemplateName());
        } else {
            $this->_tpl->display($path . $this->getTemplateName());
        }
    }

    /**
     * Handle GET request.
     *
     * @param array $values
     * @param array $errors
     */
    abstract protected function doGet(array $values = [], array $errors = []);

    /**
     * Handle POST request.
     */
    abstract protected function doPost();
}
