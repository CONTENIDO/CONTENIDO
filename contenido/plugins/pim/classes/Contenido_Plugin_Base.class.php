<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Base class providing some globally needed functionality
 *
 * @package    Contenido Backend plugins
 * @version    1.0.0
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release 4.8.7
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

class Contenido_Plugin_Base {

    protected $_db;

    protected $_dbg;

    protected $_debug;

    protected $_cfg;

    protected $_cfgClient;

    protected $_lang;

    /**
     * Constructor
     * @access public
     * @param DB_Contenido $db
     * @return void
     */
    public function __construct(DB_Contenido $db, array $cfg, array $cfgClient, $lang = 0) {
        $this->_db = $db;
        $this->_debug = false;
        $this->_cfg = $cfg;
        $this->_cfgClient = $cfgClient;
        $this->_lang = $lang;
    }

    /**
     * Set internal property for debugging on/off and choose appropriate debug object
     * @access public
     * @param boolean $debug
     * @param string $debugMode
     * @return void
     * @author Rudi Bieller
     */
    public function setDebug($debug = true, $debugMode = 'visible') {
        if (!in_array($debugMode, array('visible', 'hidden', 'file'))) {
            $debugMode = 'hidden';
        }
        $this->_dbgMode = $debugMode;
        if ($debug === true) {
            $this->bDbg = true;
            $this->_dbg = DebuggerFactory::getDebugger($debugMode);
        } else {
            $this->bDbg = false;
            $this->_dbg = null;
        }
    }
}
