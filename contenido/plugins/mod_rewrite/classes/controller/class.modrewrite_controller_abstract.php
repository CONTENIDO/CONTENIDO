<?php
/**
 * AMR abstract controller class
 *
 * @package     plugin
 * @subpackage  Mod Rewrite
 * @version     SVN Revision $Rev:$
 * @id          $Id$:
 * @author      Murat Purc <murat@purc.de>
 * @copyright   four for business AG <www.4fb.de>
 * @license     http://www.contenido.org/license/LIZENZ.txt
 * @link        http://www.4fb.de
 * @link        http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Abstract controller for all concrete mod_rewrite controller implementations.
 *
 * @author      Murat Purc <murat@purc.de>
 * @package     plugin
 * @subpackage  Mod Rewrite
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
     * Template file or template string to render
     * @var  string
     */
    protected $_template = null;

    /**
     * Additional properties list
     * @var  array
     */
    protected $_properties = array();

    /**
     * Debug flag
     * @var  bool
     */
    protected $_debug = false;

    /**
     * Constructor, sets some properties by assigning global variables to them.
     */
    public function __construct() {
        global $cfg, $client, $area, $action, $frame, $contenido, $sess;

        $this->_oView = new stdClass();
        $this->_cfg = $cfg;
        $this->_area = $area;
        $this->_action = $action;
        $this->_frame = $frame;
        $this->_client = $client;
        $this->_contenido = $contenido;

        $this->_oView->area = $this->_area;
        $this->_oView->frame = $this->_frame;
        $this->_oView->contenido = $this->_contenido;
        $this->_oView->sessid = $sess->id;
        $this->_oView->lng_more_informations = i18n("More informations", "mod_rewrite");

        $this->init();
    }

    /**
     * Initializer method, could be overwritten by childs.
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
    public function getProperty($key, $default = null) {
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
     * @param   string  Either full path and name of template file or a template string.
     *                  If not passed, previous set template will be used.
     * @throws Exception if no template is set
     * @return  string
     */
    public function render($template = null) {
        if ($template == null) {
            $template = $this->_template;
        }

        if ($template == null) {
            throw new Exception('Missing template to render.');
        }

        $oTpl = new Template();
        foreach ($this->_oView as $k => $v) {
            $oTpl->set('s', strtoupper($k), $v);
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
    protected function _getParam($key, $default = null) {
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
