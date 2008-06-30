<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Class for layout information and management
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
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

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
		$layout = Contenido_Security::toInteger($layout);

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
		$layout = Contenido_Security::toInteger($layout);

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
