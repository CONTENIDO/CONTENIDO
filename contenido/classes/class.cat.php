<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Class for category information and management
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
 *   $Id: class.cat.php 528 2008-07-02 13:29:28Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

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
		$idlang 	= Contenido_Security::toInteger($idlang);
		$category 	= Contenido_Security::toInteger($category);

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
