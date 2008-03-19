<?php

/**
 * Class Action
 * Class for action information and management
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 1.0
 * @copyright four for business 2003
 */
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
                    idaction = '".$action."'";

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
                    name = '".$action."'";
        } else {
        $sql = "SELECT
                    idarea
                FROM
                ". $cfg["tab"]["actions"] ."
                WHERE
                    idaction = '".$action."'";
        }

        $db->query($sql);
        $db->next_record();

        return ($db->f("idarea"));

    } // end function
} // end class

?>
