<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Abstract controller
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend plugins
 * @version    0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since Contenido release 4.8.15
 *
 * {@internal
 *   created  2011-04-11
 *
 *   $Id: $:
 * }}
 *
 */


defined('CON_FRAMEWORK') or die('Illegal call');


abstract class ModRewrite_ControllerAbstract
{

    protected $_oView;

    protected $_cfg;

    protected $_client;

    protected $_area;

    protected $_action;

    protected $_frame;

    protected $_contenido;

    protected $_template = null;

    protected $_properties = array();

    protected $_debug = false;

    public function __construct()
    {
        global $cfg, $client, $area, $action, $frame, $contenido, $sess;

        $this->_oView = new stdClass();
        $this->_cfg = $cfg;
        $this->_area = $area;
        $this->_action = $action;
        $this->_frame = $frame;
        $this->_client = $client;
        $this->_contenido = $contenido;

        $this->_oView->area      = $this->_area;
        $this->_oView->frame     = $this->_frame;
        $this->_oView->contenido = $this->_contenido;
        $this->_oView->sessid    = $sess->id;

        $this->init();
    }

    public function init()
    {
    }

    public function setView($oView)
    {
        if (is_object($oView)) {
            $this->_oView = $oView;
        }
    }

    public function getView()
    {
        return $this->_oView;
    }

    public function setProperty($key, $value)
    {
        $this->_properties[$key] = $value;
    }

    public function getProperty($key, $default = null)
    {
        return (isset($this->_properties[$key])) ? $this->_properties[$key] : $default;
    }

    public function setTemplate($sTemplate)
    {
        $this->_template = $sTemplate;
    }

    public function getTemplate()
    {
        return $this->_template;
    }

    public function _getParam($key, $default = null)
    {
        if (isset($_GET[$key])) {
            return $_GET[$key];
        } elseif (isset($_POST[$key])) {
            return $_POST[$key];
        } else {
            return $default;
        }
    }

    public function render($template = null)
    {
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

    protected function _notifyBox($type, $msg)
    {
        global $notification;
        return $notification->returnNotification($type, $msg) . '<br>';
    }

}
