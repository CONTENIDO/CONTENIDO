<?php

/**
 * Class Area
 * Class for user information and management
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 1.0
 * @copyright four for business 2003
 */
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
                    idarea = '".$area."'";
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
                    name = '".$area."'";

        $db->query($sql);
        $db->next_record();

        return ($db->f("idarea"));

    } // end function
    

} // end class

?>
