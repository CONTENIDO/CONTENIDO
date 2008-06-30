<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Class for action information and management
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.1
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003
 *   modified 2008-06-30, Frederic Schneider, add security fix
 *
 *   $Id$;
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

class Action {

    /**
     * Constructor Function
     * @param
     */
    function Action() {
        // empty
    } // end function

    /**
     * getAvailableActions()
     * Returns all actions available in the system
     * @return array   Array with id and name entries
     */
    function getAvailableActions() {
        global $cfg;

        $db = new DB_Contenido;

        $sql = "SELECT
                    idaction,
                    name
                FROM
                ". $cfg["tab"]["actions"]." ORDER BY name;";

        $db->query($sql);

        $actions = array();
        
        while ($db->next_record())
        {
            
            $newentry["name"] = $db->f("name");

            $actions[$db->f("idaction")] = $newentry;

        }

        return ($actions);
    } // end function

    /**
     * getActionName()
     * Returns all users available in the system
     * @return array   Array with id and name entries
     */
    function getActionName( $action ) {
        global $cfg;

        $db = new DB_Contenido;

        $sql = "SELECT
                    name
                FROM
                ". $cfg["tab"]["actions"] ."
                WHERE
                    idaction = '".Contenido_Security::toInteger($action)."'";

        $db->query($sql);
        $db->next_record();

        return ($db->f("name"));

    } // end function

    /**
     * getAreaForAction()
     * Returns the area for the given action
     * @return int   Integer with the area ID for the given action
     */
    function getAreaForAction( $action ) {
        global $cfg;
        
        $db = new DB_Contenido;
        
        if (!is_numeric($action))
        {
        $sql = "SELECT
                    idarea
                FROM
                ". $cfg["tab"]["actions"] ."
                WHERE
                    name = '".Contenido_Security::escapeDB($action, $db)."'";
        } else {
        $sql = "SELECT
                    idarea
                FROM
                ". $cfg["tab"]["actions"] ."
                WHERE
                    idaction = '".Contenido_Security::toInteger($action)."'";
        }

        $db->query($sql);
        $db->next_record();

        return ($db->f("idarea"));

    } // end function
} // end class

?>