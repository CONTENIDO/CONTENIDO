<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Class for action information and management
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Backend classes
 * @version    1.0.2
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * 
 * {@internal 
 *   created 2003
 *   modified 2008-06-30, Frederic Schneider, add security fix
 *   modified 2009-10-15, Dominik Ziegler, getAvailableActions() now also returns the areaname
 *   modified 2010-07-03, Ortwin Pinke, CON-318, only return actions marked as relevant in getAvailableActions()
 *                        also fixed doc-comment for getActionName()
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
                    action.idaction,
                    action.name,
					area.name AS areaname
                FROM
                ". $cfg["tab"]["actions"]." AS action
				LEFT JOIN
				". $cfg["tab"]["area"]." AS area
				ON 
					area.idarea = action.idarea
    WHERE action.relevant = '1'
				ORDER BY 
					action.name;";

        $db->query($sql);

        $actions = array();
        
        while ($db->next_record())
        {
            
            $newentry["name"] = $db->f("name");
			$newentry["areaname"] = $db->f("areaname");

            $actions[$db->f("idaction")] = $newentry;

        }

        return ($actions);
    } // end function

    /**
     * getActionName()
     *
     * @return string name of given action
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