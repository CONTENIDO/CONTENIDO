<?php

//Fuction checks if a plugin is already installed
function checkExistingPlugin($db, $sPluginname) {
    #new install: all plugins are checked
    if ($_SESSION["setuptype"] == "setup") {
        return true;
    }
    
    $sPluginname = (string)$sPluginname;
    $sTable = $_SESSION["dbprefix"]."_nav_sub";
    $sSql = "";

    switch ($sPluginname) {
        case 'plugin_conman':
            $sSql = "SELECT * FROM %s WHERE idnavs='900'";
            break;
            
        case 'plugin_content_allocation':
            $sSql = "SELECT * FROM %s WHERE idnavs='800'";
           break;
           
        case 'plugin_newsletter':
           $sSql = "SELECT * FROM %s WHERE idnavs='610'";
           break;
        
        default:
            $sSql = "";
            break;
    }
    
    if ($sSql) {
        $db->query(sprintf($sSql, $sTable));
        if ($db->next_record()) {
            return true;
        }
    }
    
    return false;
}

function updateSystemProperties($db, $table) {
    $aStandardvalues = array ( array('type' => 'pw_request', 'name' => 'enable', 'value' => 'true'),
                               array('type' => 'system', 'name' => 'mail_sender_name', 'value' => 'info%40contenido.org'),
                               array('type' => 'system', 'name' => 'mail_sender', 'value' => 'Contenido+Backend'),
                               array('type' => 'system', 'name' => 'mail_host', 'value' => 'localhost'),
                               array('type' => 'maintenance', 'name' => 'mode', 'value' => 'disabled'),
                               array('type' => 'edit_area', 'name' => 'activated', 'value' => 'true'),
							   array('type' => 'update', 'name' => 'check', 'value' => 'false'),
                               array('type' => 'update', 'name' => 'news_feed', 'value' => 'false'),
							   array('type' => 'update', 'name' => 'check_period', 'value' => '60')
                              );
 
    foreach ($aStandardvalues as $aData) {
    	$sql = "SELECT value FROM %s WHERE type='".$aData['type']."' AND name='".$aData['name']."'";
    	$db->query(sprintf($sql, $table));
    	if ($db->next_record()) {
			$sValue = $db->f('value');
			if ($sValue == '') {
				$sql = "UPDATE %s SET value = '%s' WHERE type='%s' AND name='%s'";
            	$db->query(sprintf($sql, $table, $aData['value'], $aData['type'], $aData['name']));
			}
    	} else {
    		$id = $db->nextid($table);
    		$sql = "INSERT INTO %s SET idsystemprop = '%s', type='%s', name='%s', value='%s'";
            $db->query(sprintf($sql, $table, $id, $aData['type'], $aData['name'], $aData['value']));
    	}
    }
}

function updateContenidoVersion ($db, $table, $version)
{
	$sql = "SELECT idsystemprop FROM %s WHERE type='system' AND name='version'";
	$db->query(sprintf($sql, $table));
	
	if ($db->next_record())
	{
		$sql = "UPDATE %s SET value = '%s' WHERE type='system' AND name='version'";
		$db->query(sprintf($sql, $table, addslashes($version)));
	} else {
		$id = $db->nextid($table);
		$sql = "INSERT INTO %s SET idsystemprop = '%s', type='system', name='version', value='%s'";
		$db->query(sprintf($sql, $table, $id, addslashes($version)));
	}
}

function getContenidoVersion ($db, $table)
{
	$sql = "SELECT value FROM %s WHERE type='system' AND name='version'";
	$db->query(sprintf($sql, $table));
	
	if ($db->next_record())
	{
		return $db->f("value");
	} else {
		return false;	
	}
}

function updateSysadminPassword ($db, $table, $password)
{
	$sql = "SELECT password FROM %s WHERE username='sysadmin'";
	$db->query(sprintf($sql, $table));

	if ($db->next_record())
	{
		$sql = "UPDATE %s SET password='%s' WHERE username='sysadmin'";
		$db->query(sprintf($sql, $table, md5($password)));
		return true;	
	} else {
		
		return false;	
	}
}

function listClients ($db, $table)
{
	$sql = "SELECT idclient, name, frontendpath, htmlpath FROM %s";
	
	$db->query(sprintf($sql, $table));
	
	$clients = array();
	
	while ($db->next_record())
	{
		$clients[$db->f("idclient")] = array("name" => $db->f("name"), "frontendpath" => $db->f("frontendpath"), "htmlpath" => $db->f("htmlpath"));	
	}
	
	return $clients;
}

function updateClientPath ($db, $table, $idclient, $frontendpath, $htmlpath)
{
	$sql = "UPDATE %s SET frontendpath='%s', htmlpath='%s' WHERE idclient='%s'";
	
	$db->query(sprintf($sql, $table, addslashes($frontendpath), addslashes($htmlpath), $idclient));	
}

function stripLastSlash ($sInput)
{
	    if (substr($sInput, strlen($sInput)-1,1) == "/")
        {
          $sInput = substr($sInput, 0, strlen($sInput)-1);
        }	
        
        return $sInput;
}

function getSystemDirectories ($bOriginalPath = false)
{
		
		$root_path = __FILE__;

		$root_path = str_replace("\\", "/", $root_path);
		
		$root_path = dirname(dirname(dirname($root_path)));
		$root_http_path = dirname(dirname($_SERVER["PHP_SELF"]));
		
		$root_path = str_replace("\\", "/", $root_path);
		$root_http_path = str_replace("\\", "/", $root_http_path);
		
		$port = "";
		$protocol = "http://";
		
		if ($_SERVER["SERVER_PORT"] != 80)
		{
			if ($_SERVER["SERVER_PORT"] == 443)
			{
				$protocol = "https://";
			} else {
				$port = ":".$_SERVER["SERVER_PORT"];
						
			}
		} 
		
		$root_http_path = $protocol . $_SERVER["SERVER_NAME"].$port . $root_http_path;
		
        if (substr($root_http_path, strlen($root_http_path)-1,1) == "/")
        {
          $root_http_path = substr($root_http_path, 0, strlen($root_http_path)-1);
        }
        
        if ($bOriginalPath == true)
        {
        	return array($root_path, $root_http_path);	
        }
        
        if (isset($_SESSION["override_root_path"]))
        {
        	$root_path = $_SESSION["override_root_path"];
        }
        
        if (isset($_SESSION["override_root_http_path"]))
        {
        	$root_http_path = $_SESSION["override_root_http_path"];
        }
        
        $root_path = stripLastSlash($root_path);
        $root_http_path = stripLastSlash($root_http_path);
                
        return array($root_path, $root_http_path);
}

function findSimilarText ($string1, $string2)
{
	for ($i=0;$i<strlen($string1);$i++)
	{
		if (substr($string1, 0, $i) != substr($string2, 0, $i))
		{
			return $i - 1;
		}	
	}
	
	return $i - 1;
}
?>