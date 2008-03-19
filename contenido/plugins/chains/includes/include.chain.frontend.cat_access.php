<?php
cInclude("classes", "class.frontend.permissions.php");
cInclude("classes", "class.frontend.users.php");

function cecFrontendCategoryAccess ($idlang, $idcat, $user)
{
	global $cfg;
	
	$db = new DB_Contenido;
	
	$FrontendUser = new FrontendUser;
	$FrontendUser->loadByPrimaryKey($user);

	if ($FrontendUser->virgin)
	{
		return false;	
	}
	
	$groups = $FrontendUser->getGroupsForUser();

	$FrontendPermissionCollection = new FrontendPermissionCollection;
	
	$sql = "SELECT idcatlang FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat = $idcat AND idlang = $idlang";
	$db->query($sql);
	
	if ($db->next_record())
	{
		$idcatlang = $db->f("idcatlang");	
	} else {
		return false;	
	}
	
	foreach ($groups as $group)
	{
		$allow = $FrontendPermissionCollection->checkPerm($group, "category", "access", $idcatlang);
		
		if ($allow == true)
		{
			return true;	
		}
	}
	
	return false;
}
?>