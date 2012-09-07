<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Class for article information and management
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.0
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *
 *   $Id: class.art.php 528 2008-07-02 13:29:28Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

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
		$idlang 	= Contenido_Security::toInteger($idlang);
		$article 	= Contenido_Security::toInteger($article);

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
		
		$idcatart = Contenido_Security::toInteger($idcatart);

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
