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
abstract class AbstractFormModule {

    /**
     *
     * @var string
     */
    const GET = 'GET';

    /**
     *
     * @var string
     */
    const POST = 'POST';

    /**
     *
     * @var array
     */
    protected $_settings = array();

    /**
     *
     * @var Smarty
     */
    protected $_tpl = NULL;

    /**
     *
     * @param array $settings
     */
    public function __construct(array $settings = NULL) {
        $this->_settings = $settings;
        $this->idform = cSecurity::toInteger($this->_settings['pifaform_idform']);
        $this->_tpl = Contenido_SmartyWrapper::getInstance(true);
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
     * (non-PHPdoc)
     *
     * @see AbstractModule::render()
     */
    public function render($return = false) {

        switch ($this->_getRequestMethod()) {
            case self::GET:
                $this->doGet();
                break;
            case self::POST:
                $this->doPost();
                break;
            default:
                // FIXME I18N
                throw new ModuleException('unknown request method');
        }

        $clientConfig = cRegistry::getClientConfig(cRegistry::getClientId());
        $path = $clientConfig['template']['path'];
        if (true === $return) {
            return $this->_tpl->fetch($path . $this->templateName);
        } else {
            $this->_tpl->display($path . $this->templateName);
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

?>