<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Class for structure information and management
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
 *   $Id: class.structure.php 409 2008-06-30 11:16:17Z frederic.schneider $: 
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

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
                    idlang = '".Contenido_Security::toInteger($idlang)."' AND
                    idcat = '".Contenido_Security::toInteger($structure)."'";

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
                    idcatart = '".Contenido_Security::toInteger($idcatart)."'";
        $db->query($sql);
        $db->next_record();

        return ($db->f("idcat"));

    } // End function

} // end class

?>