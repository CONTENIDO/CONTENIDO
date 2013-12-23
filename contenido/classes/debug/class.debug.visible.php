<?php
/**
 * This file contains the visible debug class.
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
 * Debug object to show info on screen.
 * In case you cannot output directly to screen when debugging a live system,
 * this object writes
 * the info to a file located in /data/log/debug.log.
 *
 * @package Core
 * @subpackage Debug
 */
class cDebugVisible implements cDebugInterface {

    /**
     * Singleton instance
     *
     * @var cDebugVisible
     */
    private static $_instance;

    /**
     * Return singleton instance.
     *
     * @return cDebugVisible
     */
    static public function getInstance() {
        if (self::$_instance == NULL) {
            self::$_instance = new cDebugVisible();
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
     * Outputs contents of passed variable in a preformatted, readable way
     *
     * @param mixed $mVariable The variable to be displayed
     * @param string $sVariableDescription The variable's name or description
     * @param bool $bExit If set to true, your app will die() after output of
     *        current var
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

        global $cfg;

        $tpl->generate($cfg["templates"]["debug_visible"]);
        if ($bExit === true) {
            die('<p class="debug_footer"><b>debugg\'ed</b></p>');
        }
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
