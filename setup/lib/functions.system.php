<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * 
 * Requirements: 
 * @con_php_req 5
 * 
 *
 * @package    Contenido Backend <Area>
 * @version    0.2
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 * 
 * {@internal 
 *   created  unknown
 *   modified 2008-07-07, bilal arslan, added security fix
 *   modified 2008-07-08  Thorsten Granz, added option to disable menu hover effect. clicking is now possible again
 *
 *   $Id: functions.system.php 983 2009-02-05 11:13:41Z timo.trautmann $:
 * }}
 * 
 */
 if(!defined('CON_FRAMEWORK')) {
                die('Illegal call');
}



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
							   array('type' => 'update', 'name' => 'check_period', 'value' => '60'),
							   array('type' => 'system', 'name' => 'clickmenu', 'value' => 'false'),
                               array('type' => 'versioning', 'name' => 'activated', 'value' => 'true'),
                               array('type' => 'versioning', 'name' => 'prune_limit', 'value' => '0'),
                               array('type' => 'versioning', 'name' => 'path', 'value' => ''),
							   array('type' => 'system', 'name' => 'insight_editing_activated', 'value' => 'true')
                              );
 
    foreach ($aStandardvalues as $aData) {
    	$sql = "SELECT value FROM %s WHERE type='".$aData['type']."' AND name='".$aData['name']."'";
    	$db->query(sprintf($sql,  Contenido_Security::escapeDB($table, $db)));
    	if ($db->next_record()) {
			$sValue = $db->f('value');
			if ($sValue == '') {
				$sql = "UPDATE %s SET value = '%s' WHERE type='%s' AND name='%s'";
            	$db->query(sprintf($sql,  Contenido_Security::escapeDB($table, $db), $aData['value'], $aData['type'], $aData['name']));
			}
    	} else {
    		$id = $db->nextid($table);
    		$sql = "INSERT INTO %s SET idsystemprop = '%s', type='%s', name='%s', value='%s'";
            $db->query(sprintf($sql,  Contenido_Security::escapeDB($table, $db), $id, $aData['type'], $aData['name'], $aData['value']));
    	}
    }
}

function updateContenidoVersion ($db, $table, $version)
{
	$sql = "SELECT idsystemprop FROM %s WHERE type='system' AND name='version'";
	$db->query(sprintf($sql, Contenido_Security::escapeDB($table, $db)));
	
	if ($db->next_record())
	{
		$sql = "UPDATE %s SET value = '%s' WHERE type='system' AND name='version'";
		$db->query(sprintf($sql, Contenido_Security::escapeDB($table, $db), Contenido_Security::escapeDB($version, $db)));
	} else {
		$id = $db->nextid($table);
		$sql = "INSERT INTO %s SET idsystemprop = '%s', type='system', name='version', value='%s'";
		$db->query(sprintf($sql, Contenido_Security::escapeDB($table, $db), $id, Contenido_Security::escapeDB($version, $db)));
	}
}

function getContenidoVersion ($db, $table)
{
	$sql = "SELECT value FROM %s WHERE type='system' AND name='version'";
	$db->query(sprintf($sql, Contenido_Security::escapeDB($table, $db)));
	
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
	$db->query(sprintf($sql, Contenido_Security::escapeDB($table, $db)));

	if ($db->next_record())
	{
		$sql = "UPDATE %s SET password='%s' WHERE username='sysadmin'";
		$db->query(sprintf($sql, Contenido_Security::escapeDB($table, $db), md5($password)));
		return true;	
	} else {
		
		return false;	
	}
}

function listClients ($db, $table)
{
	$sql = "SELECT idclient, name, frontendpath, htmlpath FROM %s";
	
	$db->query(sprintf($sql, Contenido_Security::escapeDB($table, $db)));
	
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
	
	$db->query(sprintf($sql, Contenido_Security::escapeDB($table, $db), Contenido_Security::escapeDB($frontendpath, $db), Contenido_Security::escapeDB($htmlpath, $db), Contenido_Security::escapeDB($idclient, $db)));	
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

function htmldecode($string)
{
	$trans_tbl = conGetHtmlTranslationTable(HTML_ENTITIES);
	$trans_tbl = array_flip($trans_tbl);
	$ret = strtr($string, $trans_tbl);

	return $ret;
}


function rereadClients_Setup()
{
	global $cfgClient;
	global $errsite_idcat;
	global $errsite_idart;
	global $db;
	global $cfg;

	if (!is_object($db))
	{
		$db = new DB_Contenido;
	}

	$sql = "SELECT
	                idclient,
	                frontendpath,
	                htmlpath,
	                errsite_cat,
	                errsite_art
	            FROM
	            ".$_SESSION["dbprefix"].'_clients';

	$db->query($sql);

	while ($db->next_record())
	{
		$cfgClient["set"] = "set";
		$cfgClient[$db->f("idclient")]["path"]["frontend"] = $db->f("frontendpath");
		$cfgClient[$db->f("idclient")]["path"]["htmlpath"] = $db->f("htmlpath");
		$errsite_idcat[$db->f("idclient")] = $db->f("errsite_cat");
		$errsite_idart[$db->f("idclient")] = $db->f("errsite_art");

		$cfgClient[$db->f("idclient")]["images"] = $db->f("htmlpath")."images/";
		$cfgClient[$db->f("idclient")]["upload"] = "upload/";

		$cfgClient[$db->f("idclient")]["htmlpath"]["frontend"] = $cfgClient[$db->f("idclient")]["path"]["htmlpath"];
		$cfgClient[$db->f("idclient")]["upl"]["path"] = $cfgClient[$db->f("idclient")]["path"]["frontend"]."upload/";
		$cfgClient[$db->f("idclient")]["upl"]["htmlpath"] = $cfgClient[$db->f("idclient")]["htmlpath"]["frontend"]."upload/";
		$cfgClient[$db->f("idclient")]["upl"]["frontendpath"] = "upload/";
		$cfgClient[$db->f("idclient")]["css"]["path"] = $cfgClient[$db->f("idclient")]["path"]["frontend"]."css/";
		$cfgClient[$db->f("idclient")]["js"]["path"] = $cfgClient[$db->f("idclient")]["path"]["frontend"]."js/";
		$cfgClient[$db->f("idclient")]["tpl"]["path"] = $cfgClient[$db->f("idclient")]["path"]["frontend"]."templates/";
	}
}
?>