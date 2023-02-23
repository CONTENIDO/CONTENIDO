<?php

/**
 * This file contains the cDebugDevNull class.
 *
 * @package Core
 * @subpackage Debug
 *
 * @author Rudi Bieller
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Debug object to not output info at all.
 * Note: Be careful when using $bExit = true as this will NOT cause a die() in
 * this object!
 *
 * @package Core
 * @subpackage Debug
 */
class cDebugDevNull implements cDebugInterface {

    /**
     * Singleton instance
     *
     * @var cDebugDevNull
     */
    private static $_instance;

    /**
     * Return singleton instance.
     *
     * @return cDebugDevNull
     */
    public static function getInstance() {
        if (self::$_instance == NULL) {
            self::$_instance = new cDebugDevNull();
        }
        return self::$_instance;
    }

    /**
     * Constructor to create an instance of this class.
     */
    private function __construct() {
    }

    /**
     * Writes a line.
     * This method does nothing!
     *
     * @see cDebugInterface::out()
     * @param string $msg
     */
    public function out($msg) {
    }

    /**
     * Outputs contents of passed variable to /dev/null
     *
     * @param mixed $mVariable
     *         The variable to be displayed
     * @param string $sVariableDescription [optional]
     *         The variable's name or description
     * @param bool $bExit [optional]
     *         If set to true, your app will NOT die() after output of current var
     */
    public function show($mVariable, $sVariableDescription = '', $bExit = false) {
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
}
