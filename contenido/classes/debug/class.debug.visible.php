<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Debug object to show info on screen.
 * In case you cannot output directly to screen when debugging a live system,
 * this object writes
 * the info to a file located in /data/log/debug.log.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package CONTENIDO Backend Classes
 * @version 1.1.1
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
class cDebugVisible implements cDebugInterface {

    private static $_instance;

    /**
     * Constructor
     */
    private function __construct() {
    }

    /**
     * static
     */
    static public function getInstance() {
        if (self::$_instance == null) {
            self::$_instance = new cDebugVisible();
        }
        return self::$_instance;
    }

    public function out($msg) {
        // o nothing
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
        $bTextarea = false;
        $bPlainText = false;
        if (is_array($mVariable)) {
            if (sizeof($mVariable) > 10) {
                $bTextarea = true;
            } else {
                $bPlainText = true;
            }
        }
        if (is_object($mVariable)) {
            $bTextarea = true;
        }
        if (is_string($mVariable)) {
            if (preg_match('/<(.*)>/', $mVariable)) {
                if (strlen($mVariable) > 40) {
                    $bTextarea = true;
                } else {
                    $bPlainText = true;
                    $mVariable = conHtmlSpecialChars($mVariable);
                }
            } else {
                $bPlainText = true;
            }
        }

        $tpl = new cTemplate();
        $tpl->set("s", "VAR_DESCRIPTION", $sVariableDescription);
        $varText = "";
        if ($bTextarea === true) {
            $varText .= '<textarea rows="10" cols="100">';
        } elseif ($bPlainText === true) {
            $varText .= '<pre class="debug_output">';
        } else {
            $varText .= '<pre class="debug_output">';
        }

        if (is_array($mVariable)) {
            $varText .= print_r($mVariable, true);
        } else {
            $varText .= var_dump($mVariable, true);
        }

        if ($bTextarea === true) {
            $varText .= '</textarea>';
        } elseif ($bPlainText === true) {
            $varText .= '</pre>';
        } else {
            $varText .= '</pre>';
        }
        $tpl->set("s", "VAR_TEXT", $varText);

        $tpl->render($cfg["templates"]["debug_visible"]);
        if ($bExit === true) {
            die('<p class="debug_footer"><b>debugg\'ed</b></p>');
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
