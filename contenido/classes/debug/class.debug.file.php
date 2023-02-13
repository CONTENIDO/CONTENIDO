<?php

/**
 * This file contains the file debug class.
 *
 * @package Core
 * @subpackage Debug
 *
 * @author Rudi Bieller
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Debug object to write info to a file.
 * In case you cannot output directly to screen when debugging a live system,
 * this object writes
 * the info to a file located in /data/logs/debug.log.
 *
 * @package Core
 * @subpackage Debug
 */
class cDebugFile implements cDebugInterface {

    /**
     * Singleton instance
     *
     * @var cDebugFile
     */
    private static $_instance;

    /**
     *
     * @var string
     */
    private $_sPathToLogs;

    /**
     *
     * @var string
     */
    private $_sFileName;

    /**
     *
     * @var string
     */
    private $_sPathToFile;

    /**
     * Return singleton instance.
     *
     * @return cDebugFile
     */
    public static function getInstance() {
        if (self::$_instance == NULL) {
            self::$_instance = new cDebugFile();
        }
        return self::$_instance;
    }

    /**
     * Constructor to create an instance of this class.
     *
     * Opens filehandle for debug logfile.
     */
    private function __construct() {
        $cfg = cRegistry::getConfig();
        $this->_sPathToLogs = $cfg['path']['contenido_logs'];
        $this->_sFileName = 'debug.log';
        $this->_sPathToFile = $this->_sPathToLogs . $this->_sFileName;
    }

    /**
     * Writes a line.
     *
     * @see cDebugInterface::out()
     *
     * @param string $msg
     *
     * @throws cInvalidArgumentException
     */
    public function out($msg) {
        $sDate = date('Y-m-d H:i:s');
        $msg = $this->_indentLines($msg);
        cFileHandler::write($this->_sPathToFile, $sDate . ": " . $msg . "\n", true);
    }

    /**
     * Outputs contents of passed variable in a preformatted, readable way
     *
     * @param mixed  $mVariable
     *                                     The variable to be displayed
     * @param string $sVariableDescription [optional]
     *                                     The variable's name or description
     * @param bool   $bExit                [optional]
     *                                     If set to true, your app will die() after output of current var
     * @throws cInvalidArgumentException
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
     * @param string $sVariableDescription [optional]
     */
    public function add($mVariable, $sVariableDescription = '') {
    }

    /**
     * Interface implementation
     */
    public function reset() {
    }

    /**
     * Interface implementation
     */
    public function showAll() {
    }

    /**
     * Indents each line of the message by the defined spaces.
     *
     * @param mixed $message
     * @param int $spaces
     * @return string The indented message
     */
    protected function _indentLines($message, $spaces = 4) {
        if (is_string($message) && !empty($message)) {
            $prefix = str_pad(' ', $spaces);
            $lines = explode("\n", $message);
            $lines = array_map(function ($item) use ($prefix) {
                return $prefix . $item;
            }, $lines);
            $message = implode("\n", $lines);
        }
        return trim($message);
    }

}
