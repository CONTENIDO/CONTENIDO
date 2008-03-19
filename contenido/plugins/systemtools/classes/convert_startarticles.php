<?php
/**********************************************************************************
* File      :   $RCSfile: convert_startarticles.php,v $
* Project   :   Contenido
* Descr     :   Start Article upgrade from Contenido 4.4.x or 4.3.x to 4.5 or later
*
* Author    :   $Author: willi.man $
*               
* Created   :   26.11.2003
* Modified  :   $Date: 2006/11/07 09:56:50 $
* Modified by:  Willi Man
*
* © four for business AG, www.4fb.de
*
* This file is part of the Contenido Content Management System. 
*
* $Id: convert_startarticles.php,v 1.1 2006/11/07 09:56:50 willi.man Exp $
***********************************************************************************/

function convertStartarticles(&$db, &$cfg)
{

	$sql = "SELECT * FROM ".$cfg["tab"]["lang"];
	$db->query($sql);
	
	# Fetching available languages
	while ($db->next_record())
	{
		$langs[] = $db->f("idlang");	
	}
	
	# Fetching all start articles
	$sql = "SELECT * FROM ".$cfg["tab"]["cat_art"]." WHERE is_start='1'";
	$db->query($sql);
	
	$db2 = new DB_Contenido;
	$sNotification = '';
	while ($db->next_record())
	{
		$startidart = $db->f("idart");
		$idcat = $db->f("idcat");
		
		# Upgrading idcat $idcat with article $startidart
		
		foreach ($langs as $vlang)
		{
			$sql = "SELECT idartlang FROM ".$cfg["tab"]["art_lang"]." WHERE idart='".$startidart."' AND idlang='".$vlang."'";
			$db2->query($sql);
			if ($db2->next_record())
			{
				$idartlang = $db2->f("idartlang");
				
				$sNotification .= "Setting startidartlang ".$idartlang." for category ".$idcat." in language ".$vlang.".<br>";
				
				$sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET startidartlang='".$idartlang."' WHERE idcat='".$idcat."' AND idlang='".$vlang."'";
				$db2->query($sql);
			}
		}
		
	}
	
	# Removing all old start article markers
	$sql = "UPDATE ".$cfg["tab"]["cat_art"]." SET is_start='0'";
	$db->query($sql);

	return $sNotification;

}
?>