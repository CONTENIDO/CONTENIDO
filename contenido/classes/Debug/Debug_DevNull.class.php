<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Debug object to not output info at all.
 * Note: Be careful when using $bExit = true as this will NOT cause a die() in this object!
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.1.2
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created 2008-05-07
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

include_once('IDebug.php');

class Debug_DevNull implements IDebug
{

    static private $_instance;

    /**
     * Constructor
     */
    private function __construct()
    {
    }


    public function out($msg)
    {
        #do nothing
    }

    /**
     * static
     */
    static public function getInstance()
    {
        if (self::$_instance == null) {
            self::$_instance = new Debug_DevNull();
        }
        return self::$_instance;
    }

    /**
     * Outputs contents of passed variable to /dev/null
     * @param mixed $mVariable The variable to be displayed
     * @param string $sVariableDescription The variable's name or description
     * @param boolean $bExit If set to true, your app will NOT die() after output of current var
     * @return void
     */
    public function show($mVariable, $sVariableDescription='', $bExit = false)
    {
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