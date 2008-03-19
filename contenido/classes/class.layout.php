<?php

/**
 * Class Layout
 * Class for layout information and management
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 1.0
 * @copyright four for business 2003
 */
class Layout {

    /**
     * Constructor Function
     * @param
     */
    function Layout() {
        // empty
    } // end function

    /**
     * getAvailableLayouts()
     * Returns all layouts available in the system
     * @return array   Array with id and name entries
     */
    function getAvailableLayouts() {
        global $cfg;

        $db = new DB_Contenido;

        $sql = "SELECT
                    idlay,
                    name
                FROM
                ". $cfg["tab"]["lay"];

        $db->query($sql);

        $layouts = array();
        
        while ($db->next_record())
        {
            
            $newentry["name"] = $db->f("name");

            $layouts[$db->f("idlay")] = $newentry;

        }

        return ($layouts);
    } // end function

    /**
     * getLayoutName()
     * Returns the name for a given layoutid
     * @return string   String with the name for the layout
     */
    function getLayoutName( $layout ) {
        global $cfg;

        $db = new DB_Contenido;

        $sql = "SELECT
                    name
                FROM
                ". $cfg["tab"]["lay"] ."
                WHERE
                    idlay = '".$layout."'";
        $db->query($sql);
        $db->next_record();

        return ($db->f("name"));

    } // end function

    /**
     * getLayoutID()
     * Returns the idlayout for a given layout name
     * @return int     Integer with the ID for the layout
     */
    function getLayoutID( $layout ) {
        global $cfg;

        $db = new DB_Contenido;

        $sql = "SELECT
                    idlay
                FROM
                ". $cfg["tab"]["lay"] ."
                WHERE
                    name = '".$layout."'";

        $db->query($sql);
        $db->next_record();

        return ($db->f("idlay"));

    } // end function


    /**
     * layoutInUse()
     * Checks if the layout is in use
     * @return bool    Specifies if the layout is in use
     */
    function layoutInUse( $layout ) {
        global $cfg;


        if (!is_numeric($layout))
        {
            $layout = $this->getLayoutID($layout);
        }
        
        $db = new DB_Contenido;

        $sql = "SELECT
                    idtpl
                FROM
                ". $cfg["tab"]["tpl"] ."
                WHERE
                    idlay = '".$layout."'";

        $db->query($sql);

        if ($db->nf() == 0)
        {
            return false;
        } else {
            return true;
        }


    } // end function  
    

} // end class

?>
