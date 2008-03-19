<?php
/********************************************************************
 * Created on 08.06.2006
 * Author: Mario Diaz (4fb)
 * Modified: Frederic Schneider (4fb), 07.11.2007
 * Modified: Andreas Lindner (4fb), 08.02.2008, Performance enhancements  
 * 
 * Nachprüfen, ob ein Benutzer mit BE-Rechten Zugriff auf eine Cat
 * im FE hat
********************************************************************/

function cCatPerm($widcat, $db = null)
{
	global $cfg, $sess, $auth, $group_id, $_arrCatIDs_cCP;

	if (strpos($auth->auth['perm'], 'admin') !== FALSE) {
		return true;
	}
	
	if (is_null($db) || !is_object($db)) {
		$db = new DB_Contenido;
	}

	$group_ids = getGroupIDs($db);
	$group_ids[] = $auth->auth['uid'];

	if (!is_array($_arrCatIDs_cCP)) {
		$_arrCatIDs_cCP = array();

		$sql_inc = " user_id='";
		$sql_inc .= implode("' OR user_id='", $group_ids) . "' ";
		$sql = "SELECT idcat FROM ".$cfg['tab']['rights']."
				WHERE idarea=6 AND idaction=359 AND ($sql_inc)";

		$db->query($sql);
		
		while ($db->next_record()) {
			$_arrCatIDs_cCP[$db->f('idcat')] = ''; 
		}
	}
	
	return array_key_exists($widcat, $_arrCatIDs_cCP);
}

function getGroupIDs(&$db)
{
	global $cfg, $sess, $auth, $group_id, $_arrGroupIDs_gGI;

	if (is_array($_arrGroupIDs_gGI)) {
		return $_arrGroupIDs_gGI;
	}
	
	$sql = "SELECT group_id FROM ".$cfg["tab"]["groupmembers"]." WHERE user_id='".$auth->auth["uid"]."'";
	$db->query($sql);

	$_arrGroupIDs_gGI = array();

	while ($db->next_record())
		$_arrGroupIDs_gGI[] = $db->f('group_id');

	return $_arrGroupIDs_gGI;
}

?>
