<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Debug object to write info to a file.
 * In case you cannot output directly to screen when debugging a live system,
 * this object writes
 * the info to a file located in /data/logs/debug.log.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package CONTENIDO Backend Classes
 * @version 1.1.2
 * @author Rudi Bieller
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

include_once ('interface.debug.php');
class cDebugFile implements cDebugInterface {

    private static $_instance;

    private $_sPathToLogs;

    private $_sFileName;

    private $_sPathToFile;

    /**
     * Constructor
     * Opens filehandle for debug-logfile
     *
     * @return void
     */
    private function __construct() {
        global $cfg; // omfg, I know... TODO
        $this->_sPathToLogs = $cfg['path']['contenido_logs'];
        $this->_sFileName = 'debug.log';
        $this->_sPathToFile = $this->_sPathToLogs . $this->_sFileName;
    }

    /**
     * static
     *
     * @return void
     */
    static public function getInstance() {
        if (self::$_instance == null) {
            self::$_instance = new cDebugFile();
        }
        return self::$_instance;
    }

    public function out($msg) {
        if (cFileHandler::writeable($this->_sPathToFile)) {
            $sDate = date('Y-m-d H:i:s');
            cFileHandler::write($this->_sPathToFile, $sDate . ": " . $msg . "\n", true);
        }
    }

    /**
     * Outputs contents of passed variable in a preformatted, readable way
     *
     * @param mixed $mVariable The variable to be displayed
     * @param string $sVariableDescription The variable's name or description
     * @param boolean $bExit If set to true, your app will die() after output of
     *        current var
     * @return void
     */
    public function show($mVariable, $sVariableDescription = '', $bExit = false) {
        if (cFileHandler::writeable($this->_sPathToFile)) {
            $sDate = date('Y-m-d H:i:s');
            cFileHandler::write($this->_sPathToFile, '#################### ' . $sDate . ' ####################' . "\n", true);
            cFileHandler::write($this->_sPathToFile, $sVariableDescription . "\n", true);
            cFileHandler::write($this->_sPathToFile, print_r($mVariable, true) . "\n", true);
            cFileHandler::write($this->_sPathToFile, '#################### /' . $sDate . ' ###################' . "\n\n", true);
        }
    }

    /**
     * Interface implementation
     *
     * @param mixed $mVariable
     * @param string $sVariableDescription
     * @return void
     */
    public function add($mVariable, $sVariableDescription = '') {
    }

    /**
     * Interface implementation
     *
     * @return void
     */
    public function reset() {
    }

    /**
     * Interface implementation
     *
     * @return string Here an empty string
     */
    public function showAll() {
    }

}

class Debug_File extends cDebugFile {

    /**
     * @deprecated Class was renamed to cDebugFile
     */
    private function __construct() {
        global $cfg; // omfg, I know... TODO
        cDeprecated('Class was renamed to cDebugFile');
        $this->_sPathToLogs = $cfg['path']['contenido_logs'];
        $this->_sFileName = 'debug.log';
        $this->_sPathToFile = $this->_sPathToLogs . $this->_sFileName;
    }

}