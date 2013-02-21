<?php

/**
 * Abstract base class for all classes that are used as PIFA form module.
 *
 * In order for an extension class to be displayed in the CMS_PIFAFORM's editor
 * as module class it has to extend this class and implement its abstract
 * methods doGet() & doPost().
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

/**
 *
 * @author marcus.gnass
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
    protected $_settings = array();

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
     *
     * @var Smarty
     */
    private $_tpl = NULL;

    /**
     *
     * @param array $settings as defined for cContentTypePifaForm
     */
    public function __construct(array $settings = NULL) {
        $this->_settings = $settings;
        $this->_idform = cSecurity::toInteger($this->_settings['pifaform_idform']);
        $this->_tpl = Contenido_SmartyWrapper::getInstance(true);
    }

    /**
     *
     * @return the $_settings
     */
    public function getSettings() {
        return $this->_settings;
    }

    /**
     *
     * @return the $_settings
     */
    public function getSetting($key) {
        return $this->_settings[$key];
    }

    /**
     *
     * @param multitype: $_settings
     */
    public function setSettings($_settings) {
        $this->_settings = $_settings;
    }

    /**
     *
     * @return the $_idform
     */
    public function getIdform() {
        return $this->_idform;
    }

    /**
     *
     * @param number $_idform
     */
    public function setIdform($_idform) {
        $this->_idform = $_idform;
    }

    /**
     *
     * @return the $_templateName
     */
    public function getTemplateName() {
        return $this->_templateName;
    }

    /**
     *
     * @param string $_templateName
     */
    public function setTemplateName($_templateName) {
        $this->_templateName = $_templateName;
    }

    /**
     *
     * @return the $_tpl
     */
    public function getTpl() {
        return $this->_tpl;
    }

    /**
     *
     * @param Smarty $_tpl
     */
    public function setTpl($_tpl) {
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
        $requestMethod = strtoupper($requestMethod);

        return $requestMethod;

    }

    /**
     */
    public function render($return = false) {

        // determine request method
        $requestMethod = $this->_getRequestMethod();

        // always handle POST method in backend edit mode as GET action
        if (cRegistry::isBackendEditMode()) {
            $requestMethod = self::GET;
        }

        // dispatch request method
        switch ($requestMethod) {
            case self::GET:
                $this->doGet();
                break;
            case self::POST:
                $this->doPost();
                break;
            default:
                throw new PifaException('unknown request method');
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
     *
     * @param array $values
     * @param array $errors
     */
    abstract protected function doGet(array $values = array(), array $errors = array());

    /**
     */
    abstract protected function doPost();

}
