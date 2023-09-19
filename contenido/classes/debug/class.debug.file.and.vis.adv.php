<?php

/**
 * This file contains the file and vis adv debug class.
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
 * Debug object to write info to a file and to show info on screen.
 * In case you cannot output directly to screen when debugging a live system,
 * this object writes the info to a file located in /data/logs/debug.log.
 *
 * @package    Core
 * @subpackage Debug
 */
class cDebugFileAndVisAdv extends cDebugVisibleAdv
{

    /**
     * Singleton instance
     *
     * @var cDebugFileAndVisAdv
     */
    private static $_instance;

    /**
     *
     * @var string
     */
    private $_filePathName;

    /**
     * Return singleton instance.
     *
     * @return cDebugFileAndVisAdv
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
     */
    private function __construct()
    {
        $this->_aItems = [];
        $this->_buffer = '';

        $cfg = cRegistry::getConfig();
        $this->_filePathName = $cfg['path']['contenido_logs'] . 'debug.log';
    }

    /**
     * Writes a line.
     *
     * @see cDebugInterface::out()
     *
     * @param string $sText
     *
     * @throws cInvalidArgumentException
     */
    public function out($sText)
    {
        parent::out($sText);

        $sDate = date('Y-m-d H:i:s');
        cFileHandler::write($this->_filePathName, $sDate . ": " . $sText . "\n", true);
    }

    /**
     * Outputs contents of passed variable in a preformatted, readable way.
     *
     * @see cDebugVisibleAdv::show()
     * @param mixed  $mVariable
     *                                     The variable to be displayed.
     * @param string $sVariableDescription [optional]
     *                                     The variable's name or description.
     * @param bool   $bExit                [optional]
     *                                     If set to true, your app will die() after output of current var.
     * @throws cInvalidArgumentException
     */
    public function show($mVariable, $sVariableDescription = '', $bExit = false)
    {
        parent::show($mVariable, $sVariableDescription, $bExit);

        if (is_writeable($this->_filePathName)) {
            $sDate = date('Y-m-d H:i:s');
            $sContent = '#################### ' . $sDate . ' ####################' . "\n"
                . $sVariableDescription . "\n"
                . print_r($mVariable, true) . "\n"
                . '#################### /' . $sDate . ' ###################' . "\n\n";
            cFileHandler::write($this->_filePathName, $sContent, true);
        }
    }

}
