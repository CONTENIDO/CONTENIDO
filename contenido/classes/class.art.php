<?php

/**
 * Class Art
 * Class for article information and management
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 1.0
 * @copyright four for business 2003
 */
class Art {

    /**
     * Constructor Function
     * @param
     */
    function Art() {
        // empty
    } // end function

    /**
     * getArtName()
     * Returns a name for the given article
     * @return string  Returns the name of the given article
     */
    function getArtName($article, $idlang) {
        global $cfg;

        $db = new DB_Contenido;

        $sql = "SELECT
                    title
                FROM
                ". $cfg["tab"]["art_lang"] ."
                WHERE
                    idlang = '".$idlang."' AND
                    idart = '".$article."'";

        $db->query($sql);
        $db->next_record();

        return ($db->f("title"));

    } // end function

    /**
     * getArtIDForCatArt()
     * Returns a name for the given article
     * @return string  Returns the name of the given article
     *
     */
    function getArtIDForCatArt ( $idcatart)
    {
        global $cfg;

        $db = new DB_Contenido;

        $sql = "SELECT
                    idart
                FROM
                ". $cfg["tab"]["cat_art"] ."
                WHERE
                    idcatart = '".$idcatart."'";
        $db->query($sql);
        $db->next_record();

        return ($db->f("idart"));

    } // End function

} // end class

?>
