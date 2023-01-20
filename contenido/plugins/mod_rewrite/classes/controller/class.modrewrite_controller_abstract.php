<?php
/**
 * AMR abstract controller class
 *
 * @package     Plugin
 * @subpackage  ModRewrite
 * @id          $Id$:
 * @author      Murat Purc <murat@purc.de>
 * @copyright   four for business AG <www.4fb.de>
 * @license     http://www.contenido.org/license/LIZENZ.txt
 * @link        http://www.4fb.de
 * @link        http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Abstract controller for all concrete mod_rewrite controller implementations.
 *
 * @author      Murat Purc <murat@purc.de>
 * @package     Plugin
 * @subpackage  ModRewrite
 */
abstract class ModRewrite_ControllerAbstract {

    /**
     * View object, holds all view variables
     * @var  stdClass
     */
    protected $_oView;

    /**
     * Global CONTENIDO $cfg variable
     * @var  array
     */
    protected $_cfg;

    /**
     * Global CONTENIDO $client variable (client id)
     * @var  int
     */
    protected $_client;

    /**
     * Global CONTENIDO $area variable (area name/id)
     * @var  int|string
     */
    protected $_area;

    /**
     * Global CONTENIDO $action variable (send by request)
     * @var  string
     */
    protected $_action;

    /**
     * Global CONTENIDO $frame variable (current frame in backend)
     * @var  int
     */
    protected $_frame;

    /**
     * Global CONTENIDO $contenido variable (session id)
     * @var  string
     */
    protected $_contenido;

    /**
     * Plugin name
     * @var  string
     */
    protected $_pluginName;

    /**
     * Template file or template string to render
     * @var  string
     */
    protected $_template = NULL;

    /**
     * Additional properties list
     * @var  array
     */
    protected $_properties = [];

    /**
     * Debug flag
     * @var  bool
     */
    protected $_debug = false;

    /**
     * Constructor, sets some properties by assigning global variables to them.
     */
    public function __construct() {
        $this->_oView = new stdClass();
        $this->_cfg = cRegistry::getConfig();
        $this->_area = cRegistry::getArea();
        $this->_action = cRegistry::getAction();
        $this->_frame = cRegistry::getFrame();
        $this->_client = cRegistry::getClientId();
        $this->_contenido = cRegistry::getBackendSessionId();
        $this->_pluginName = $this->_cfg['pi_mod_rewrite']['pluginName'];
        $sess = cRegistry::getSession();

        $this->_oView->area = $this->_area;
        $this->_oView->frame = $this->_frame;
        $this->_oView->contenido = $this->_contenido;
        $this->_oView->sessid = $sess->id;
        $this->_oView->lng_more_informations = i18n('More informations', $this->_pluginName);

        $this->init();
    }

    /**
     * Initializer method, could be overwritten by children.
     * This method will be invoked in constructor of ModRewrite_ControllerAbstract.
     */
    public function init() {
    }

    /**
     * View property setter.
     * @param  object  $oView
     */
    public function setView($oView) {
        if (is_object($oView)) {
            $this->_oView = $oView;
        }
    }

    /**
     * View property getter.
     * @return  object
     */
    public function getView() {
        return $this->_oView;
    }

    /**
     * Property setter.
     * @param  string  $key
     * @param  mixed   $value
     */
    public function setProperty($key, $value) {
        $this->_properties[$key] = $value;
    }

    /**
     * Property getter.
     * @param   string  $key
     * @param   mixed   $default
     * @return  mixed
     */
    public function getProperty($key, $default = NULL) {
        return (isset($this->_properties[$key])) ? $this->_properties[$key] : $default;
    }

    /**
     * Template setter.
     * @param  string  $sTemplate  Either full path and name of template file or a template string.
     */
    public function setTemplate($sTemplate) {
        $this->_template = $sTemplate;
    }

    /**
     * Template getter.
     * @return  string
     */
    public function getTemplate() {
        return $this->_template;
    }

    /**
     * Renders template by replacing all view variables in template.
     * @param   string  $template Either full path and name of template file or a template string.
     *                  If not passed, previous set template will be used.
     * @throws cException if no template is set
     * @return  void
     */
    public function render($template = NULL) {
        if ($template == NULL) {
            $template = $this->_template;
        }

        if ($template == NULL) {
            throw new cException('Missing template to render.');
        }

        $oTpl = new cTemplate();
        foreach ($this->_oView as $k => $v) {
            $oTpl->set('s', cString::toUpperCase($k), $v);
        }
        $oTpl->generate($template, 0, 0);
    }

    /**
     * Returns  parameter from request, the order is:
     * - Return from $_GET, if found
     * - Return from $_POST, if found
     *
     * @param   string  $key
     * @param   mixed   $default  The default value
     * @return  mixed
     */
    protected function _getParam($key, $default = NULL) {
        if (isset($_GET[$key])) {
            return $_GET[$key];
        } elseif (isset($_POST[$key])) {
            return $_POST[$key];
        } else {
            return $default;
        }
    }

    /**
     * Returns rendered notification markup by using global $notification variable.
     * @param   string  $type  One of cGuiNotification::LEVEL_* constants
     * @param   string  $msg   The message to display
     * @return  string
     */
    protected function _notifyBox($type, $msg) {
        global $notification;
        return $notification->returnNotification($type, $msg) . '<br>';
    }

}
