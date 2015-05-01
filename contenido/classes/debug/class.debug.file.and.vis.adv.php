<?php

/**
 * This file contains the file and vis adv debug class.
 *
 * @package Core
 * @subpackage Debug
 * @version SVN Revision $Rev:$
 *
 * @author Rudi Bieller
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Debug object to write info to a file and to show info on screen.
 * In case you cannot output directly to screen when debugging a live system,
 * this object writes
 * the info to a file located in /data/logs/debug.log.
 *
 * @package Core
 * @subpackage Debug
 */
class cDebugFileAndVisAdv extends cDebugVisibleAdv {

    /**
     * Singleton instance
     *
     * @var cDebugFileAndVisAdv
     * @todo should be private
     */
    protected static $_instance;

    /**
     *
     * @var array
     */
    private $_aItems;

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
    public static function getInstance() {
        if (self::$_instance == NULL) {
            self::$_instance = new cDebugFileAndVisAdv();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        global $cfg;
        $this->_aItems = array();
        $this->_filePathName = $cfg['path']['contenido_logs'] . 'debug.log';
    }

    /**
     * Writes a line.
     *
     * @see cDebugInterface::out()
     * @param string $msg
     */
    public function out($msg) {
        parent::out($msg);

        $sDate = date('Y-m-d H:i:s');
        cFileHandler::write($this->_filePathName, $sDate . ": " . $msg . "\n", true);
    }

    /**
     * Outputs contents of passed variable in a preformatted, readable way.
     *
     * @see cDebugVisibleAdv::show()
     * @param mixed $mVariable
     *         The variable to be displayed.
     * @param string $sVariableDescription
     *         The variable's name or description.
     * @param bool $bExit
     *         If set to true, your app will die() after output of current var.
     */
    public function show($mVariable, $sVariableDescription = '', $bExit = false) {
        parent::show($mVariable, $sVariableDescription, $bExit);

        if (is_writeable($this->_filePathName)) {
            $sDate = date('Y-m-d H:i:s');
            $sContent = '#################### ' . $sDate . ' ####################' . "\n" . $sVariableDescription . "\n" . print_r($mVariable, true) . "\n" . '#################### /' . $sDate . ' ###################' . "\n\n";
            cFileHandler::write($this->_filePathName, $sContent, true);
        }
    }
}
