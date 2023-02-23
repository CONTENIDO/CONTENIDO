<?php

/**
 * This file contains the hidden debug class.
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
 * Debug object to show info hidden in HTML comment-blocks.
 *
 * @package Core
 * @subpackage Debug
 */
class cDebugHidden implements cDebugInterface {

    /**
     * Singleton instance
     *
     * @var cDebugHidden
     */
    private static $_instance;

    /**
     * Return singleton instance.
     *
     * @return cDebugHidden
     */
    static public function getInstance() {
        if (self::$_instance == NULL) {
            self::$_instance = new cDebugHidden();
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
     *
     * @see cDebugInterface::out()
     * @param string $msg
     */
    public function out($msg) {
        echo ("\n <!-- dbg\n");
        echo ($msg);
        echo ("\n-->");
    }

    /**
     * Outputs contents of passed variable in a preformatted, readable way
     *
     * @param mixed $mVariable
     *         The variable to be displayed
     * @param string $sVariableDescription [optional]
     *         The variable's name or description
     * @param bool $bExit [optional]
     *         If set to true, your app will die() after output of current var
     */
    public function show($mVariable, $sVariableDescription = '', $bExit = false) {
        echo "\n <!-- dbg";
        if ($sVariableDescription != '') {
            echo ' ' . strval($sVariableDescription);
        }
        echo " -->\n";
        echo '<!--' . "\n";
        if (is_array($mVariable)) {
            print_r($mVariable);
        } else {
            var_dump($mVariable);
        }
        echo "\n" . '//-->' . "\n";
        echo "\n <!-- /dbg -->\n";

        if ($bExit === true) {
            die();
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
}
