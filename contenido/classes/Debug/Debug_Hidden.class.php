<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Debug object to show info hidden in HTML comment-blocks.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.1.1
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created 2007-01-01
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

include_once('IDebug.php');

class Debug_Hidden implements IDebug
{

    static private $_instance;

    /**
     * Constructor
     */
    private function __construct()
    {
    }

    /**
     * static
     */
    static public function getInstance()
    {
        if (self::$_instance == null) {
            self::$_instance = new Debug_Hidden();
        }
        return self::$_instance;
    }

    public function out($msg)
    {
        echo("\n <!-- dbg\n");
        echo($msg);
        echo("\n-->");
    }

    /**
     * Outputs contents of passed variable in a preformatted, readable way
     * @param mixed $mVariable The variable to be displayed
     * @param string $sVariableDescription The variable's name or description
     * @param boolean $bExit If set to true, your app will die() after output of current var
     * @return void
     */
    public function show($mVariable, $sVariableDescription='', $bExit = false)
    {
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
     * @param mixed $mVariable
     * @param string $sVariableDescription
     * @return void
     */
    public function add($mVariable, $sVariableDescription = '')
    {
    }

    /**
     * Interface implementation
     * @return void
     */
    public function reset()
    {
    }

    /**
     * Interface implementation
     * @return string Here an empty string
     */
    public function showAll()
    {
    }

}

?>