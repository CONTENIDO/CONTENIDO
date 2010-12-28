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
 * @version    1.0
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 
 *   modified 2008-07-04, bilal arslan, added security fix
 *   modified 2008-09-14, Murat Purc, CON-206: fixed backend access logic
 *   modified 2008-10-30, Andreas Lindner, also consider group memnership of backend users    
 *   $Id$: 
 * }}
 * 
 */
 
if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


function cecFrontendCategoryAccess_Backend($idlang, $idcat, $user)
{
	global $cfg, $perm;

	if ($perm->have_perm()) {
        // sysadmin or client admin can always access to protected areas
        return true;
    }
    
	$db2 = new DB_Contenido;
	
	$arrSearchFor = array("'".Contenido_Security::escapeDB($user, $db2)."'");

	$sql = "SELECT * FROM ".$cfg['tab']['groupmembers']." WHERE user_id = '".Contenido_Security::escapeDB($user, $db2)."'";

	$db2->query($sql);
	
	while ($db2->next_record()) {
		$arrSearchFor[] = "'".Contenido_Security::escapeDB($db2->f('group_id'), $db2)."'";
	}

	$sSearchFor = implode(",", $arrSearchFor);
	
	$sql = "SELECT idright
					FROM ".$cfg["tab"]["rights"]." AS A,
						 ".$cfg["tab"]["actions"]." AS B,
						 ".$cfg["tab"]["area"]." AS C
					 WHERE B.name = 'front_allow' AND C.name = 'str' AND A.user_id IN (". $sSearchFor .") AND A.idcat = '".Contenido_Security::toInteger($idcat)."'
							AND A.idarea = C.idarea AND B.idaction = A.idaction AND A.idlang = '".Contenido_Security::toInteger($idlang)."'";

	$db2->query($sql);

	if (!$db2->next_record()) {
		return false;
	} else {
		return true;
	}
}?>
