<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.1.1
 * @author     Andreas Lindner, Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created
 *   modified 2008-08-06, Ingo van Peeren - replaced genericdb-code due to performance issues (ticket #)
 *
 *   $Id$: 
 * }}
 * 
 */
 
if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


class frontendlogic_category extends FrontendLogic
{
	function getFriendlyName ()
	{
		return i18n("Category", "frontendlogic_category");	
	}
	
	function listActions ()
	{
		$actions = array();
		$actions["access"] = i18n("Access category", "frontendlogic_category");
		
		return ($actions);	
	}
	
	function listItems ()
	{
		global $lang, $db, $cfg;
		
		if (!is_object($db)) {
            $db = new DB_Contenido;
        }
        
        $sSQL = "SELECT
                   b.idcatlang,
                   b.name,
                   c.level
                 FROM
                   ".$cfg['tab']['cat']." AS a,
                   ".$cfg['tab']['cat_lang']." AS b,
                   ".$cfg['tab']['cat_tree']." AS c
                 WHERE
                   a.idcat = b.idcat AND
                   a.idcat = c.idcat AND
                   b.idlang = ".$lang." AND
                   b.public = 0
                 ORDER BY c.idtree ASC";

        $db->query($sSQL);
        while ($db->next_record()) {
            $items[$db->f("idcatlang")] = 
				'<span style="padding-left: '.($db->f("level")*10).'px;">'.htmldecode($db->f("name")).'</span>';
			
        }
		
		return ($items);
	}
}
?>