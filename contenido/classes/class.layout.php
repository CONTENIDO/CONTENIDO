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
 * @version    1.0.2
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
 *   modified 2008-07-02, Frederic Schneider, change sql-escapes
 *   modified 2009-10-27, OliverL, replace toInteger() to escapeString() in function getLayoutID()
 *
 *	 modified 2010-08-17, Munkh-Ulzii Balidar,
 *		- changed the code compatible to php5 
 *		- added new property aUsedTemplates and saved the information of used templates 
 *		- added new method getUsedTemplates
 *									
 *   $Id: class.layout.php 1199 2010-08-24 14:31:44Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

class Layout {

	private $aUsedTemplates = array();
	
    /**
     * Constructor Function
     * @param
     */
    public function __construct() 
    {
        ;// empty
    } // end function

    /**
     * getAvailableLayouts()
     * Returns all layouts available in the system
     * @return array   Array with id and name entries
     */
    public function getAvailableLayouts() 
    {
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
    public function getLayoutName($layout) 
    {
        global $cfg;

        $db = new DB_Contenido;

        $sql = "SELECT
                    name
                FROM
                ". $cfg["tab"]["lay"] ."
                WHERE
                    idlay = '".Contenido_Security::toInteger($layout)."'";
        $db->query($sql);
        $db->next_record();

        return ($db->f("name"));

    } // end function

    /**
     * getLayoutID()
     * Returns the idlayout for a given layout name
	 * @param $layout String with the Layoutname
     * @return int     Integer with the ID for the layout
     */
    public function getLayoutID($layout) 
    {
        global $cfg;

        $db = new DB_Contenido;

        $sql = "SELECT
                    idlay
                FROM
                ". $cfg["tab"]["lay"] ."
                WHERE
                    name = '".Contenido_Security::escapeString($layout)."'";

        $db->query($sql);
        $db->next_record();

        return ($db->f("idlay"));

    } // end function


    /**
     * layoutInUse()
     * Checks if the layout is in use
     * @return bool    Specifies if the layout is in use
     */
    public function layoutInUse($layout, $bSetData = false) 
    {
        global $cfg;

        if (!is_numeric($layout)) 
        {
            $layout = $this->getLayoutID($layout);
        }
        
        $db = new DB_Contenido;

        $sql = "SELECT
                    idtpl, name 
                FROM
                ". $cfg["tab"]["tpl"] ."
                WHERE
                    idlay = '".Contenido_Security::toInteger($layout)."'";

        $db->query($sql);

        if ($db->nf() == 0) {
            return false;
        } else {
        	$i = 0;
        	// save the datas of used templates in array
        	if ($bSetData === true) {
	        	while ($db->next_record()) {
	        		$this->aUsedTemplates[$i]['tpl_name'] = $db->f('name');
	        		$this->aUsedTemplates[$i]['tpl_id'] = (int)$db->f('idtpl');
	        		$i++;
	        	}
        	}
        	
            return true;
        }
    } // end function  
    
    /**
     * Get the informations of used templates
     * @return array template data
     */
    public function getUsedTemplates()
    {
    	return $this->aUsedTemplates;
    }
    
} // end class

?>
