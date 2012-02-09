<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Class for category information and management
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.0.0
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * @deprecated [2011-11-15] Class Cat is nowhere used in CONTENIDO core. If Cat is still used in your modules/plugins, please switch to cApiCategoryLanguage.
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

class Cat {
    function Cat() {
       cDeprecated("Use class cApiCategoryLanguage instead.");
    }

    function getCatName($category, $idlang) {
        global $cfg;

        cDeprecated("Use class cApiCategoryLanguage instead.");

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
    }
}
?>