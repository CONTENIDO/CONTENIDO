<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Class for user information and management
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
 *   $Id: class.area.php 415 2008-06-30 12:17:50Z frederic.schneider $;
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

class Area {

    /**
     * Constructor Function
     * @param
     */
    function Area() {
        // empty
    } // end function

    /**
     * getAvailableAreas()
     * Returns all areas available in the system
     * @return array   Array with id and name entries
     */
    function getAvailableAreas() {
        global $cfg;

        $db = new DB_Contenido;

        $sql = "SELECT
                    idarea,
                    name
                FROM
                ". $cfg["tab"]["area"];

        $db->query($sql);

        $areas = array();
        
        while ($db->next_record())
        {
            $newentry["name"] = $db->f("name");
            $areas[$db->f("idarea")] = $newentry;
        }

        return ($areas);
    } // end function

    /**
     * getAreaName()
     * Returns the name for a given areaid
     * @return string   String with the name for the area
     */
    function getAreaName( $area ) {
        global $cfg;

        $db = new DB_Contenido;

        $sql = "SELECT
                    name
                FROM
                ". $cfg["tab"]["area"] ."
                WHERE
                    idarea = '".Contenido_Security::escapeDB($area, $db)."'";
        $db->query($sql);
        $db->next_record();

        return ($db->f("name"));

    } // end function

    /**
     * getAreaID()
     * Returns the idarea for a given area name
     * @return int     Integer with the ID for the area
     */
    function getAreaID( $area ) {
        global $cfg;

        $db = new DB_Contenido;

        $sql = "SELECT
                    idarea
                FROM
                ". $cfg["tab"]["area"] ."
                WHERE
                    name = '".Contenido_Security::escapeDB($area, $db)."'";

        $db->query($sql);
        $db->next_record();

        return ($db->f("idarea"));

    } // end function
    

} // end class

?>