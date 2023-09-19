<?php

/**
 * This file contains the file debug class.
 *
 * @package    Core
 * @subpackage Debug
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Debug object to write info to a file.
 * In case you cannot output directly to screen when debugging a live system,
 * this object writes
 * the info to a file located in /data/logs/debug.log.
 *
 * @package    Core
 * @subpackage Debug
 */
class cDebugFile implements cDebugInterface
{

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
    public static function getInstance(): cDebugInterface
    {
        if (self::$_instance == NULL) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor to create an instance of this class.
     *
     * Opens file handle for debug logfile.
     */
    private function __construct()
    {
        $cfg = cRegistry::getConfig();
        $this->_sPathToLogs = $cfg['path']['contenido_logs'];
        $this->_sFileName = 'debug.log';
        $this->_sPathToFile = $this->_sPathToLogs . $this->_sFileName;
    }

    /**
     * Writes a line.
     *
     * @param string $sText
     *
     * @throws cInvalidArgumentException
     * @see cDebugInterface::out()
     */
    public function out($sText)
    {
        $sDate = date('Y-m-d H:i:s');
        $sText = $this->_indentLines($sText);
        cFileHandler::write($this->_sPathToFile, $sDate . ": " . $sText . "\n", true);
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
    public function show($mVariable, $sVariableDescription = '', $bExit = false)
    {
        if (cFileHandler::writeable($this->_sPathToFile)) {
            $sDate = date('Y-m-d H:i:s');
            $content = '#################### ' . $sDate . ' ####################' . "\n"
                . $sVariableDescription . "\n"
                . print_r($mVariable, true) . "\n"
                . '#################### /' . $sDate . ' ###################' . "\n\n";
            cFileHandler::write($this->_sPathToFile, $content, true);
        }
    }

    /**
     * Interface implementation
     *
     * @param mixed $mVariable
     * @param string $sVariableDescription [optional]
     */
    public function add($mVariable, $sVariableDescription = '')
    {
    }

    /**
     * Interface implementation
     */
    public function reset()
    {
    }

    /**
     * Interface implementation
     */
    public function showAll()
    {
    }

    /**
     * Indents each line of the message by the defined spaces.
     *
     * @param mixed $message
     * @param int $spaces
     * @return string The indented message
     */
    protected function _indentLines($message, int $spaces = 4): string
    {
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
