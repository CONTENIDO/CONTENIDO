<?php
/**********************************************************************************
* File      :   $RCSfile$
* Project   :   Contenido
* Descr     :   Start Article upgrade from Contenido 4.4.x or 4.3.x to 4.5 or later
*
* Author    :   $Author$
*               
* Created   :   26.11.2003
* Modified  :   $Date$
*
* © four for business AG, www.4fb.de
*
* This file is part of the Contenido Content Management System. 
*
* $Id$
***********************************************************************************/

include_once ('../includes/startup.php');

cInclude ("includes", 'functions.general.php');

cInclude ("includes", 'functions.i18n.php');
cInclude ("includes", 'functions.api.php');
cInclude ("includes", 'functions.general.php');
cInclude ("includes", 'functions.forms.php');

cInclude ("includes", 'cfg_sql.inc.php');


cInclude ("classes", 'class.xml.php');
cInclude ("classes", 'class.navigation.php');
cInclude ("classes", 'class.template.php');
cInclude ("classes", 'class.backend.php');
cInclude ("classes", 'class.notification.php');
cInclude ("classes", 'class.area.php');
cInclude ("classes", 'class.action.php');
cInclude ("classes", 'contenido/class.module.php');
cInclude ("classes", 'class.layout.php');
cInclude ("classes", 'class.treeitem.php');
cInclude ("classes", 'class.user.php');
cInclude ("classes", 'class.group.php');
cInclude ("classes", 'class.cat.php');
cInclude ("classes", 'class.client.php');
cInclude ("classes", 'class.inuse.php');
cInclude ("classes", 'class.table.php');


i18nInit($cfg["path"]["contenido"].$cfg["path"]["locale"], $belang);
cInclude ("includes", 'cfg_language_de.inc.php');


# Create Contenido classes
$db = new DB_Contenido;

$sql = "SELECT * FROM ".$cfg["tab"]["lang"];
$db->query($sql);

echo "Fetching available languages...";
while ($db->next_record())
{
	$langs[] = $db->f("idlang");	
	echo $db->f("idlang"). " ";
}

echo "done.<br>";
echo "Fetching all start articles:<br>";
$sql = "SELECT * FROM ".$cfg["tab"]["cat_art"]." WHERE is_start='1'";
$db->query($sql);

$db2 = new DB_Contenido;

while ($db->next_record())
{
	$startidart = $db->f("idart");
	$idcat = $db->f("idcat");
	
	echo "  Upgrading idcat $idcat with article $startidart<br>";
	
	foreach ($langs as $vlang)
	{
		$sql = "SELECT idartlang FROM ".$cfg["tab"]["art_lang"]." WHERE idart='$startidart' AND idlang='$vlang'";
		$db2->query($sql);
		if ($db2->next_record())
		{
			$idartlang = $db2->f("idartlang");
			
			echo "      Setting idcat $idcat for language $vlang to startidartlang $idartlang<br>";
			
			$sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET startidartlang='$idartlang' WHERE idcat='$idcat' AND idlang='$vlang'";
			$db2->query($sql);
		}
		
	}
	
}

echo "Removing all old start article markers<br>";
$sql = "UPDATE ".$cfg["tab"]["cat_art"]." SET is_start='0'";
$db->query($sql);

echo "<br>All done. Please remove this script.<br>";

?>