<?php

/**
 * Class Structure
 * Class for structure information and management
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 1.0
 * @copyright four for business 2003
 */
class Structure {

    /**
     * Constructor Function
     * @param
     */
    function Structure() {
        // empty
    } // end function

    /**
     * getStructureName()
     * Returns a name for the given structure
     * @return string  Returns the name of the given structure
     */
    function getStructureName( $structure, $idlang) {
        global $cfg;

        $db = new DB_Contenido;

        $sql = "SELECT
                    name
                FROM
                ". $cfg["tab"]["cat_lang"] ."
                WHERE
                    idlang = '".$idlang."' AND
                    idcat = '".$structure."'";

        $db->query($sql);
        $db->next_record();

        return ($db->f("name"));

    } // end function

    /**
     * getStructureIDForCatArt()
     * Returns a name for the given structure
     * @return string  Returns the name of the given structure
     *
     */
    function getStructureIDForCatArt ( $idcatart)
    {
        global $cfg;

        $db = new DB_Contenido;

        $sql = "SELECT
                    idcat
                FROM
                ". $cfg["tab"]["cat_art"] ."
                WHERE
                    idcatart = '".$idcatart."'";
        $db->query($sql);
        $db->next_record();

        return ($db->f("idcat"));

    } // End function

} // end class

?>
