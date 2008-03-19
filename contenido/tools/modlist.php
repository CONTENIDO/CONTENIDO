<?php
die("Access denied");

include_once ('../includes/startup.php');
cInclude("classes", "contenido/class.module.php");
cInclude("includes", "functions.mod.php");
cInclude("includes", "functions.con.php");

switch ($_GET["action"])
{
	case "list" :
		if (!array_key_exists("idclient", $_GET))
		{
			die;
		}
		
		listModules($_GET["idclient"]);
		break;
	case "get":
		if (!array_key_exists("idmod", $_GET))
		{
			die;	
		}
		
		if (!array_key_exists("type", $_GET))
		{
			die;
		}
		
		getModule($_GET["idmod"], $_GET["type"]);
		break;
	case "put":
		putModule($_GET["idmod"], $_GET["type"], stripslashes($_POST["code"]));
		break;
}

function putModule ($idmod, $type, $code)
{
	
	$cApiModuleCollection = new cApiModuleCollection;
	$cApiModuleCollection->setWhere("idmod", $idmod);
	$cApiModuleCollection->query();
	
	if ($cApiModule = $cApiModuleCollection->next())
	{
		$cApiModule->set($type, addslashes($code));
		$cApiModule->store();
		
		global $client;
		$client = $cApiModule->get("idclient");
		
		conGenerateCodeForAllArtsUsingMod($idmod);
	}
}

function getModule ($idmod, $type)
{
	$cApiModuleCollection = new cApiModuleCollection;
	$cApiModuleCollection->setWhere("idmod", $idmod);
	$cApiModuleCollection->query();
	
	if ($cApiModule = $cApiModuleCollection->next())
	{
		$tree  = new XmlTree('1.0', 'ISO-8859-1');
		$root =& $tree->addRoot('module');
		$root->appendChild("code", htmlspecialchars($cApiModule->get($type)));
		
		$tree->dump(false);
	}
	
}

function listModules($idclient)
{
	$cApiModuleCollection = new cApiModuleCollection;
	$cApiModuleCollection->setWhere("idclient", $idclient);
	$cApiModuleCollection->query();
	
	$tree  = new XmlTree('1.0', 'ISO-8859-1');
	$root =& $tree->addRoot('modules');
	
	while ($cApiModule = $cApiModuleCollection->next())
	{
		$root->appendChild("module", false, array("id" => $cApiModule->get("idmod"), "name" => $cApiModule->get("name")));    		
	}
	
	$tree->dump(false);
}
?>