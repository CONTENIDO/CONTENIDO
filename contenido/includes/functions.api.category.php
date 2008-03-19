<?php

/*****************************************
* File      :   $RCSfile: functions.api.category.php,v $
* Project   :   Contenido
* Descr     :   Contenido Category API functions
*
* Author    :   Timo A. Hummel
*               
* Created   :   08.08.2003
* Modified  :   $Date: 2006/04/28 09:20:54 $
*
* © four for business AG, www.4fb.de
*
* $Id: functions.api.category.php,v 1.4 2006/04/28 09:20:54 timo.hummel Exp $
******************************************/

/* Info:
 * This file contains Contenido Category API functions.
 *
 * If you are planning to add a function, please make sure that:
 * 1.) The function is in the correct place
 * 2.) The function is documented
 * 3.) The function makes sense and is generically usable
 *
 */


/**
 * capiCatGetLevelNode: Seeks through the category
 * tree and returns the node on a specific level.
 *
 * Example:
 *
 * + Category A (15)
 * |-+ News (16)
 * | |- News A (17)
 * + Category B (18)
 * |-+ Internal (19)
 *
 * Given you are in the leaf "News A" (idcat 17),
 * and you want to get out in which "main" tree you
 * are, you can call the function like this:
 *
 * capi_cat_getlevelnode(17,1);
 * 
 * The example would return "Category A" (idcat 15).
 * If you specify an invalid level, the results are
 * undefined.
 *
 * @param $int idcat The category number
 * @param $minLevel int The level to extract 
 *
 * @return int The category node on a specific level
 */
function capi_cat_getlevelnode($idcat, $minLevel = 0)
{
    global $cfg, $client, $lang;

    $db = new DB_Contenido;
    
    $sql = "SELECT
                a.name AS name,
                a.idcat AS idcat,
                b.parentid AS parentid,
				c.level AS level
            FROM
                ".$cfg["tab"]["cat_lang"]." AS a,
                ".$cfg["tab"]["cat"]." AS b,
				".$cfg["tab"]["cat_tree"]." AS c
            WHERE
                a.idlang    = '".$lang."' AND
                b.idclient  = '".$client."' AND
                b.idcat     = '".$idcat."' AND
				c.idcat		= b.idcat AND
                a.idcat     = b.idcat";
                
    $db->query($sql);
    $db->next_record();

    $name       = $db->f("name");
    $parentid   = $db->f("parentid");
	$thislevel = $db->f("level");
	
    if ( $parentid != 0 && $thislevel >= $minLevel) {
        return capi_cat_getlevelnode($parentid, $minLevel);
    } else {
		return $idcat;
	}
}