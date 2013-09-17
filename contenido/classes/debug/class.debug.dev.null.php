<?php
/**
 * This file contains the dev null debug class.
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
        if (self::$_instance == null) {
            self::$_instance = new cDebugDevNull();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
    }

    /**
     * (non-PHPdoc)
     *
     * @see cDebugInterface::out()
     */
    public function out($msg) {
    }

    /**
     * Outputs contents of passed variable to /dev/null
     *
     * @param mixed $mVariable The variable to be displayed
     * @param string $sVariableDescription The variable's name or description
     * @param boolean $bExit If set to true, your app will NOT die() after
     *        output of current var
     */
    public function show($mVariable, $sVariableDescription = '', $bExit = false) {
    }

    /**
     * Interface implementation
     *
     * @param mixed $mVariable
     * @param string $sVariableDescription
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