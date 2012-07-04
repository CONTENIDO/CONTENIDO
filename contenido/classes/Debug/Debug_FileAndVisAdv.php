<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Debug object to write info to a file and to show info on screen.
 * In case you cannot output directly to screen when debugging a live system, this object writes
 * the info to a file located in /data/logs/debug.log.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.0.1
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2007-01-01
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

include_once("Debug_VisibleAdv.class.php");

class Debug_FileAndVisAdv extends Debug_VisibleAdv
{

    static protected $_instance;
    private $_aItems;
    private $_buffer;
    private $_filePathName;

    private function __construct()
    {
        global $cfg;
        $this->_aItems = array();
        $this->_filePathName = $cfg['path']['contenido_logs'] . 'debug.log';
    }

    static public function getInstance()
    {
        if (self::$_instance == null) {
            self::$_instance = new Debug_FileAndVisAdv();
        }
        return self::$_instance;
    }

    public function out($msg)
    {
        parent::out($msg);

        if (is_writeable($this->_filePathName)) {
            $sDate = date('Y-m-d H:i:s');
            cFileHandler::write($this->_filePathName, $sDate . ": " . $msg . "\n", true);
        }
    }

    public function show($mVariable, $sVariableDescription = '', $bExit = false)
    {
        parent::show($mVariable, $sVariableDescription, $bExit);

        if (is_writeable($this->_filePathName)) {
            $sDate = date('Y-m-d H:i:s');
            $sContent = '#################### ' . $sDate . ' ####################' . "\n"
                      . $sVariableDescription . "\n"
                      . print_r($mVariable, true) . "\n"
                      . '#################### /' . $sDate . ' ###################' . "\n\n";
            cFileHandler::write($file, $sContent, true);
        }
    }
}
