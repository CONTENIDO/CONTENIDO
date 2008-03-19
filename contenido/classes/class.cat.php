<?php

/**
 * Class Cat
 * Class for category information and management
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 1.0
 * @copyright four for business 2003
 */
class Cat {

    /**
     * Constructor Function
     * @param
     */
    function Cat() {
        // empty
    } // end function

    /**
     * getCatName()
     * Returns a name for the given category
     * @return string  Returns the name of the given category
     */
    function getCatName($category, $idlang) {
        global $cfg;

        $db = new DB_Contenido;

        $sql = "SELECT
                    name
                FROM
                ". $cfg["tab"]["cat_lang"] ."
                WHERE
                    idlang = '".$idlang."' AND
                    idcat = '".$category."'";

        $db->query($sql);
        $db->next_record();

        return ($db->f("name"));

    } // end function

} // end class

?>
