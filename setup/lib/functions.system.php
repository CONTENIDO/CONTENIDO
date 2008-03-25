<?php

//Fuction checks if a plugin is already installed
function checkExistingPlugin($db, $sPluginname) {
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

function updatePwRequest($db, $table) {
    $aStandardvalues = array ( 'enable' => 'true',
                               'mail_sender_name' => 'info%40contenido.org',
                               'mail_sender' => 'Contenido+Backend',
                               'mail_host' => 'localhost'
                                );
                                
    $sql = "SELECT name, value FROM %s WHERE type='pw_request'";
    $db->query(sprintf($sql, $table));
    
    $aExists = array();
    $aKeys = array_keys($aStandardvalues);
    
    while ($db->next_record()) {
        $sName = $db->f('name');
        $sValue = $db->f('value');

        array_push($aExists, $sName);
        
        if ($sValue == '' && in_array($sName, $aKeys)) {
            $sType = '';
            if ($sName == 'enable') {
                $sType = 'pw_request';
            } else {
                $sType = 'system';
            }
            $sql = "UPDATE %s SET value = '%s' WHERE type='%s' AND name='%s'";
            $db->query(sprintf($sql, $table, $aStandardvalues[$sName], $sType, $sName));
        }
    }
    
    foreach ($aStandardvalues as $key => $value) {
        if (!in_array($key, $aExists)) {
            $id = $db->nextid($table);
            $sType = '';
            if ($key == 'enable') {
                $sType = 'pw_request';
            } else {
                $sType = 'system';
            }
            $sql = "INSERT INTO %s SET idsystemprop = '%s', type='%s', name='%s', value='%s'";
            $db->query(sprintf($sql, $table, $id, $sType, $key, $value));
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