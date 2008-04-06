<?php
/**
 * Plugin Systemtools
 *
 * @file systemfunctions.php
 * @project Contenido
 *
 * @version	1.0.3
 * @author Willi Man
 * @copyright four for business AG <www.4fb.de>
 * @created 26.08.2005
 * @modified 19.10.2005
 * @modified 27.02.2006
 * @modified 14.03.2006
 * @modified 07.11.2006 Rudi Bieller Changed methods tool_CreateArticleLanguage and tool_CreateCategoryByGivenCategoryId (typecasting to int)
 * @modified 09.11.2006 Rudi Bieller Added addslashes + check for non-existing idlang in methods tool_CreateArticleLanguage and tool_CreateCategoryByGivenCategoryId
 * @modified 06.04.2008 Holger Librenz, direct mysql_* function calls replaced with DB_Contenido::* methods
 *
 * @todo Correct these validations: if (!is_int((int)$iClientId))
 *
 */

include_once('LogFile.php');


if (!function_exists('human_readable_size'))
{
	function human_readable_size ( $number )
	{
		$base = 1024;
		$suffixes = array( " B", " KB", " MB", " GB", " TB", " PB", " EB" );

		$usesuf = 0;
		$n = (float) $number; //Appears to be necessary to avoid rounding
		while( $n >= $base )
		{
			$n /= (float) $base;
			$usesuf++;
		}

		$places = 2 - floor( log10( $n ) );
		$places = max( $places, 0 );
		$retval = number_format( $n, $places, ".", "" ) . $suffixes[$usesuf];
		return $retval;
	}
}

/**
 * Delete all entries in table con_code
 * @return boolean
 */
function deleteFromConCode(&$db, &$cfg, $bDebug = false)
{
	$sQuery = "DELETE FROM ".$cfg['tab']['code']." ";
	if ($bDebug) { print "<pre>".$sQuery."</pre>"; }
	$db->query($sQuery);
	if ($db->Errno > 0)
	{
		return false;
	}else
	{
		return true;
	}
}

/**
 * Delete all entries in table con_inuse
 * @return boolean
 */
function deleteFromConInuse(&$db, &$cfg, $bDebug = false)
{
	$sQuery = "DELETE FROM ".$cfg['tab']['inuse']." ";
	if ($bDebug) { print "<pre>".$sQuery."</pre>"; }
	$db->query($sQuery);
	if ($db->Errno > 0)
	{
		return false;
	}else
	{
		return true;
	}
}

/**
 * Delete all entries in table con_phplib_active_sessions
 * @return boolean
 */
function deleteFromConPhplibActiveSessions(&$db, &$cfg, $bDebug = false)
{
	$sQuery = "DELETE FROM ".$cfg['tab']['phplib_active_sessions']." ";
	if ($bDebug) { print "<pre>".$sQuery."</pre>"; }
	$db->query($sQuery);
	if ($db->Errno > 0)
	{
		return false;
	}else
	{
		return true;
	}
}

/**
 * Get number of entries in table con_phplib_active_sessions
 * @return int
 */
function getNumberOfActiveSessions(&$db, &$cfg, $bDebug = false)
{
	$sQuery = "SELECT count(*) AS num_of_sessions FROM ".$cfg['tab']['phplib_active_sessions']." ";
	if ($bDebug) { print "<pre>".$sQuery."</pre>"; }
	$db->query($sQuery);
	if ($db->Errno > 0)
	{
		return -1;
	}else
	{
		$db->next_record();
		return $db->f('num_of_sessions');
	}
}
/**
 * Clear client cache directory
 * @return boolean
 */
function clearCacheDirectory($iClient, &$cfgClient)
{
	if (!isset($iClient) OR !is_int((int)$iClient) OR $iClient <= 0 OR !is_array($cfgClient) OR !isset($cfgClient[$iClient]["path"]["frontend"])) { return false;}

	if($DirectoryResource = opendir ($cfgClient[$iClient]["path"]["frontend"].'cache/'))
	{
	    while ($sFile = readdir ($DirectoryResource))
	    {
	        if ($sFile != ".." AND $sFile != "." AND !is_dir ($sFile))
	        {
	        	unlink($cfgClient[$iClient]["path"]["frontend"].'cache/'.$sFile);
	        }
	    }
	    return true;
	}else
	{
		return false;
	}
}

/**
 * Get table status information
 * @return array
 */
function getTableStatus(&$db, $bDebug = false)
{
	$sQuery = "SHOW TABLE STATUS FROM ".$db->Database." ";

	if ($bDebug) { print "<pre>".$sQuery."</pre>"; }
	$db->query($sQuery);

	if ($db->Errno > 0)
	{
		;
	}else
	{
		$aResult = array();
		//while($oRow = mysql_fetch_object($db->Query_ID))
		while($oRow = $db->getResultObject())
		{
			$aResult[] = $oRow;
		}
		return $aResult;
	}
}

/**
 * Get sum of Data_length and Index_length from table status information
 * @param $aTableStatusInformation Array with objects of type
 * stdClass Object
 * (
 *     [Name] => con_actionlog
 *     [Type] => MyISAM
 *     [Row_format] => Dynamic
 *     [Rows] => 309
 *     [Avg_row_length] => 65
 *     [Data_length] => 20152
 *     [Max_data_length] => 4294967295
 *     [Index_length] => 1024
 *     [Data_free] => 0
 *     [Auto_increment] =>
 *     [Create_time] => 2005-08-30 17:11:17
 *     [Update_time] => 2005-08-31 17:04:26
 *     [Check_time] => 2005-08-31 11:08:53
 *     [Create_options] =>
 *     [Comment] =>
 * )
 * @return array
 */
function sumOfTableLengthRows(&$aTableStatusInformation)
{

	if (is_array($aTableStatusInformation) AND count($aTableStatusInformation) > 0)
	{
		$iSumOfRows = 0;
		$iSumOfLength = 0;

		for ($i = 0; $i < count($aTableStatusInformation); $i++)
		{
			$oRow = &$aTableStatusInformation[$i];

			$iSumOfRows += $oRow->Rows;
			$iDataIndexLength = $oRow->Data_length + $oRow->Index_length;
			$iSumOfLength += $iDataIndexLength;

		}

		return array(human_readable_size($iSumOfLength), $iSumOfRows);

	}else
	{
		return array('', '');
	}
}

/**
 * Backup Database
 * @return mixed (string, boolean)
 * @modified 14.03.2006, parse database host
 */
function backupDatabase ($sBackupPath, $aTables, $bExtended, &$cfg, &$cfgClient, $client, $lang, &$db, $bDebug = false)
{

	if (is_dir($sBackupPath) AND is_writable($sBackupPath) AND count($aTables) > 0)
	{
		$sTables = '';
		for ($i = 0; $i < count($aTables); $i++)
		{
			$sTables .= $aTables[$i].' ';
		}

		$sFileName = date('d_m_Y_His').'_'.$db->Database.'.sql';
		$sFilePath = $sBackupPath.$sFileName;

		$sExtended = '';
		if ($bExtended)
		{
			$sExtended = '-e';
		}

		$aDatabaseHost = array();
		$sCheck = shell_exec('mysqldump');
		if (eregi('mysqldump', $sCheck))
		{
			$aDatabaseHost = parse_url($db->Host);
			if (isset($aDatabaseHost['port']) AND isset($aDatabaseHost['host']))
			{
				$sPort = '--port='.$aDatabaseHost['port'];
				$sHost = $aDatabaseHost['host'];
			}else
			{
				$sPort = '';
				$sHost = $db->Host;
			}

			$sCmd = 'mysqldump -h '.$sHost.' '.$sPort.' -u '.$db->User.' --password='.$db->Password.' '.$db->Database.' '.$sExtended.' --quote-names --add-drop-table --tables '.$sTables.' > '.$sFilePath;
		}else
		{
			return false;
		}

		if ($bDebug) {echo "<pre>$sCmd</pre>";}

		exec($sCmd);

		return $sFileName;
	}else
	{
		false;
	}

}

/**
 * Get files
 * @return mixed (array, boolean)
 */
function getFiles($sPath)
{
	if (is_dir($sPath) AND is_readable($sPath))
	{
		if ($handle = opendir($sPath))
		{
		    $aFiles = array();
		    while ($sFile = readdir($handle))
		    {
		        if(is_readable($sPath.$sFile) AND is_file($sPath.$sFile))
		        {
		        	$iFileSize = filesize($sPath.$sFile);
		            $aFiles[] = array('file' => $sFile, 'filesize' => $iFileSize);
		        }
		    }
		    closedir($handle);

		   	return $aFiles;
		}
	}else
	{
		return false;
	}
}

/**
 * Get clients
 * @return array
 */
function tool_getClients(&$cfg, &$db, $bDebug = false)
{
    $sql = "SELECT * FROM ".$cfg["tab"]["clients"]." ";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}

    $db->query($sql);

    $aResult = array();
	//while($oRow = mysql_fetch_object($db->Query_ID))
	while($oRow = $db->getResultObject())
	{
		$aResult[] = $oRow;
	}
	return $aResult;
}

/**
 * Set client
 * @return int
 */
function tool_setClient ($clientname, $frontendpath, $htmlpath, $errsite_cat = 0, $errsite_art = 0, &$cfg, &$db, $auth, $bDebug = false)
{

	if (strlen($clientname) == 0) { return -1;}

	$iNextId = $db->nextid($cfg["tab"]["clients"]);

	$sql = "
	INSERT INTO
    ".$cfg["tab"]["clients"]."
    SET
    name = '".$clientname."',
	author = '".$auth->auth["uname"]."',
	created = NOW(),
	lastmodified = NOW(),
    frontendpath = '".$frontendpath."',
    htmlpath = '". $htmlpath."',
    errsite_cat = '".$errsite_cat."',
    errsite_art = '".$errsite_art."',
    idclient = ".$iNextId;

    if ($bDebug) {echo "<pre>".$sql."</pre>";}

	$db->query($sql);

	return $iNextId;

}

/**
 * Get client name
 * @return string
 */
function tool_getClientName ($iClientId, &$cfg, &$db, $bDebug = false)
{

	if (!is_int((int)$iClientId) OR $iClientId < 0) { return '';}

	$sql = "SELECT * FROM ".$cfg["tab"]["clients"]." WHERE idclient = ".$iClientId." ";

    if ($bDebug) {echo "<pre>".$sql."</pre>";}

	$db->query($sql);

	//if($oRow = mysql_fetch_object($db->Query_ID))
	if($oRow = $db->getResultObject())
	{
		return $oRow->name;
	} else
	{
		return '';
	}

}
/**
 * Create new layout
 *
 * @modified 27.02.2006, replace set_magic_quotes_gpc by addslashes
 *
 * @param string $name Name of the Layout
 * @param string $description Description of the Layout
 * @param string $code Layout HTML Code
 * @return int
 */
function tool_createLayout($iClientId, $name, $description, $code, &$auth, &$cfg, &$db, $bDebug = false, $bLog = false, $sLogFile = '')
{
	if (!is_int((int)$iClientId) OR $iClientId <= 0 OR strlen($name) == 0) { return -1;}

	$oLog = new LogFile();

    $name = addslashes($name);
    $description = addslashes($description);
    $code = addslashes($code);

    $iNextId = $db->nextid($cfg["tab"]["lay"]);

    $sql = "INSERT INTO ".$cfg["tab"]["lay"]."
				(idlay, name, description, deletable, code, idclient, author, created, lastmodified)
			VALUES
				(".$iNextId.",'".$name."', '".$description."', '1', '".$code."', ".$iClientId.", '".$auth->auth["uname"]."', NOW(), NOW())";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}
	if ($bLog)
	{
		$oLog->logMessageByMode("DEBUG tool_createLayout ".date("d-m-Y H:i:s").":\n", $sLogFile, "read_write_end");
		$oLog->logMessageByMode($sql."\n", $sLogFile, "read_write_end");
	}

    $db->query($sql);

    return $iNextId;
}

/**
 * Get layouts by client
 * @return array
 */
function tool_getLayoutsByClient($iClientId, &$cfg, &$db, $bDebug = false)
{
	if (!is_int((int)$iClientId) OR $iClientId <= 0) { return array();}

    $sql = "SELECT * FROM ".$cfg["tab"]["lay"]." WHERE idclient = ".$iClientId." ";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}

    $db->query($sql);

    $aResult = array();
	//while($oRow = mysql_fetch_object($db->Query_ID))
	while($oRow = $db->getResultObject())
	{
		$aResult[] = $oRow;
	}
	return $aResult;
}

/**
 * Get layouts by client indexed by name
 * @return array
 */
function tool_getLayoutsByClientIndexedByName($iClientId, &$cfg, &$db, $bDebug = false)
{
	if (!is_int((int)$iClientId) OR $iClientId <= 0) { return array();}

    $sql = "SELECT * FROM ".$cfg["tab"]["lay"]." WHERE idclient = ".$iClientId." ";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}

    $db->query($sql);

    $aResult = array();
	//while($oRow = mysql_fetch_object($db->Query_ID))
	while($oRow = $db->getResultObject())
	{
		$aResult[$oRow->name] = $oRow;
	}
	return $aResult;
}

/**
 * Creeate module
 * @return int
 */
function tool_createModule($iClientId, $name, $description, $input, $output, $template = "", $type = "", &$db, &$auth, &$cfg, $bDebug = false, $bLog = false, $sLogFile = '')
{

	if (!is_int((int)$iClientId) OR $iClientId <= 0 OR strlen($name) == 0) { return -1;}

	$oLog = new LogFile();

    $tmp_newid = $db->nextid($cfg["tab"]["mod"]);

    $sql = "INSERT INTO ".$cfg["tab"]["mod"]."
			(idmod, name, description, deletable, input, output, template, idclient, author, created, lastmodified, type)
			VALUES
			(".$tmp_newid.",'".addslashes($name)."', '".addslashes($description)."', 1, '".addslashes($input)."', '".addslashes($output)."', '".addslashes($template)."', ".$iClientId.", '".$auth->auth["uname"]."', NOW(), NOW(), '".$type."')";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}
	if ($bLog)
	{
		$oLog->logMessageByMode("DEBUG tool_createModule ".date("d-m-Y H:i:s").":\n", $sLogFile, "read_write_end");
		$oLog->logMessageByMode($sql."\n", $sLogFile, "read_write_end");
	}

    $db->query($sql);

    return $tmp_newid;

}

/**
 * Get modules by client
 * @return array
 */
function tool_getModulesByClient($iClientId, &$cfg, &$db, $bDebug = false)
{
	if (!is_int((int)$iClientId) OR $iClientId <= 0) { return array();}

    $sql = "SELECT * FROM ".$cfg["tab"]["mod"]." WHERE idclient = ".$iClientId." ";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}

    $db->query($sql);

    $aResult = array();
	//while($oRow = mysql_fetch_object($db->Query_ID))
	while($oRow = $db->getResultObject())
	{
		$aResult[] = $oRow;
	}
	return $aResult;
}

/**
 * Get modules by client indexed by name
 * @return array
 */
function tool_getModulesByClientIndexedByName($iClientId, &$cfg, &$db, $bDebug = false)
{
	if (!is_int((int)$iClientId) OR $iClientId <= 0) { return array();}

    $sql = "SELECT * FROM ".$cfg["tab"]["mod"]." WHERE idclient = ".$iClientId." ";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}

    $db->query($sql);

    $aResult = array();
//	while($oRow = mysql_fetch_object($db->Query_ID))
	while($oRow = $db->getResultObject())
	{
		$aResult[$oRow->name] = $oRow;
	}
	return $aResult;
}

/**
 * Get templates by client
 * @return array
 */
function tool_getTemplatesByClient($iClientId, &$cfg, &$db, $bDebug = false)
{
	if (!is_int((int)$iClientId) OR $iClientId <= 0) { return array();}

    $sql = "SELECT * FROM ".$cfg["tab"]["tpl"]." WHERE idclient = ".$iClientId." ";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}

    $db->query($sql);

    $aResult = array();
	//while($oRow = mysql_fetch_object($db->Query_ID))
	while($oRow = $db->getResultObject())
	{
		$aResult[] = $oRow;
	}
	return $aResult;
}

/**
 * Create template
 *
 * modified 27.02.2006, replace set_magic_quotes_gpc by addslashes
 *
 * @return int
 */
function tool_createTemplate($iClientId, $iLayoutId, $sName, $sDescription, $iDefaultTemplate, &$db, &$auth, &$cfg, $bDebug = false, $bLog = false, $sLogFile = '')
{
	$sName = addslashes($sName);
    $sDescription = addslashes($sDescription);

    if (!is_int((int)$iClientId) OR $iClientId <= 0 OR !is_int((int)$iLayoutId) OR $iLayoutId <= 0 OR strlen($sName) == 0) { return -1;}

	$oLog = new LogFile();

    $iNewTemplateId = $db->nextid($cfg["tab"]["tpl"]);
    $iNewTemplateCfgId = $db->nextid($cfg["tab"]["tpl_conf"]);

    $sql = "INSERT INTO ".$cfg["tab"]["tpl"]."
            (idtpl, idtplcfg, name, description, defaulttemplate, deletable, status, idlay, idclient, author, created, lastmodified)
			VALUES
            ('".$iNewTemplateId."', '".$iNewTemplateCfgId."', '".$sName."', '".$sDescription."', '".$iDefaultTemplate."', 1, 0, '".$iLayoutId."', '".$iClientId."', '".$auth->auth["uname"]."', '".date('YmdHis')."', NOW())";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}
	if ($bLog)
	{
		$oLog->logMessageByMode("DEBUG tool_createTemplate ".date("d-m-Y H:i:s").":\n", $sLogFile, "read_write_end");
		$oLog->logMessageByMode($sql."\n", $sLogFile, "read_write_end");
	}

    $db->query($sql);

    $sql = "INSERT INTO ".$cfg["tab"]["tpl_conf"]."
            (idtplcfg, idtpl, status, author, created, lastmodified)
            VALUES
           (".$iNewTemplateCfgId.", ".$iNewTemplateId.", 0, '".$auth->auth["uname"]."', '".date('YmdHis')."', NOW())";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}
	if ($bLog)
	{
		$oLog->logMessageByMode("DEBUG tool_createTemplate ".date("d-m-Y H:i:s").":\n", $sLogFile, "read_write_end");
		$oLog->logMessageByMode($sql."\n", $sLogFile, "read_write_end");
	}

    $db->query($sql);

    return $iNewTemplateId;

}

/**
 * Get container module mapping by template
 * @return array
 */
function tool_getContainerModuleMappingByTemplate($iTemplateId, &$cfg, &$db, $bDebug = false)
{
	if (!is_int((int)$iTemplateId) OR $iTemplateId <= 0) { return array();}

    $sql = "SELECT * FROM ".$cfg["tab"]["container"]."
            WHERE idtpl = ".$iTemplateId." ";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}

    $db->query($sql);

    $aResult = array();
//	while($oRow = mysql_fetch_object($db->Query_ID))
	while($oRow = $db->getResultObject())
	{
		$aResult[] = $oRow;
	}
	return $aResult;
}

/**
 * Place module into container by template
 * @return void
 */
function tool_placeModuleToContainerByTemplate($iTemplateId, $iContainerId, $iModuleId, &$cfg, &$db, $bDebug = false)
{
    if (!is_int((int)$iTemplateId) OR $iTemplateId < 0 OR !is_int((int)$iContainerId) OR $iContainerId < 0) { return NULL;}

	$sql = "INSERT INTO ".$cfg["tab"]["container"]." (idcontainer, idtpl, number, idmod) VALUES ";
	$sql .= "(";
	$sql .= $db->nextid($cfg["tab"]["container"]).", ";
	$sql .= $iTemplateId.", ";
	$sql .= $iContainerId.", ";
	$sql .= "'".$iModuleId."'";
	$sql .= ") ";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}

	$db->query($sql);

}

/**
 * Create category by given category id
 * @return void
 */
function tool_CreateCategoryByGivenCategoryId($iCategoryId, $iClientId, $iParentCategoryId, $iPreId, $iPostId, &$cfg, &$db, &$auth, $bDebug = false)
{
	$iPreId = (int)$iPreId; // missing or NULL values will be converted to 0
	if (!is_int((int)$iCategoryId) OR $iCategoryId < 0 OR !is_int((int)$iClientId) OR $iClientId < 0 OR !is_int((int)$iParentCategoryId) OR $iParentCategoryId < 0 OR !is_int((int)$iPreId) OR $iPreId < 0 OR !is_int((int)$iPostId) OR $iPostId < 0) { return NULL;}

    $sql = "INSERT INTO ".$cfg["tab"]["cat"]."
			(idcat, idclient, parentid, preid, postid, status, author, created, lastmodified)
			VALUES
			(".$iCategoryId.", ".$iClientId.", ".$iParentCategoryId.", ".$iPreId.", ".$iPostId.", 0, '".$auth->auth["uname"]."', NOW(), NOW())";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}

    $db->query($sql);

}

/**
 * Get categories by client
 * @return array
 */
function tool_getCategoriesByClient($iClientId, &$cfg, &$db, $bDebug = false)
{
	if (!is_int((int)$iClientId) OR $iClientId <= 0) { return array();}

    $sql = "SELECT * FROM ".$cfg["tab"]["cat"]." WHERE idclient = ".$iClientId." ";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}

    $db->query($sql);

    $aResult = array();
//	while($oRow = mysql_fetch_object($db->Query_ID))
	while($oRow = $db->getResultObject())
	{
		$aResult[] = $oRow;
	}
	return $aResult;
}

/**
 * Get articles by client
 * @return array
 */
function tool_getArticlesByClient($iClientId, &$cfg, &$db, $bDebug = false)
{
	if (!is_int((int)$iClientId) OR $iClientId <= 0) { return array();}

    $sql = "SELECT * FROM ".$cfg["tab"]["art"]." WHERE idclient = ".$iClientId." ";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}

    $db->query($sql);

    $aResult = array();
//	while($oRow = mysql_fetch_object($db->Query_ID))
	while($oRow = $db->getResultObject())
	{
		$aResult[] = $oRow;
	}
	return $aResult;
}

/**
 * Create article
 * @return int
 */
function tool_CreateArticle($iClientId, &$cfg, &$db, &$auth, $bDebug = false)
{
	if (!is_int((int)$iClientId) OR $iClientId < 0) { return -1;}

	$iNewArticleId = $db->nextid($cfg["tab"]["art"]);

    $sql = "INSERT INTO ".$cfg["tab"]["art"]."
			(idart, idclient)
			VALUES
			(".$iNewArticleId.", ".$iClientId.") ";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}

    $db->query($sql);

    return $iNewArticleId;

}

/**
 * Get article-language relation by client
 * @return array
 */
function tool_getArticleLanguageByClient($iClientId, &$cfg, &$db, $bDebug = false)
{
	if (!is_int((int)$iClientId) OR $iClientId <= 0) { return array();}

    $sql = "SELECT * FROM ".$cfg["tab"]["art"]." AS A, ".$cfg["tab"]["art_lang"]." AS B WHERE A.idclient = ".$iClientId." AND A.idart = B.idart ";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}

    $db->query($sql);

    $aResult = array();
//	while($oRow = mysql_fetch_object($db->Query_ID))
	while($oRow = $db->getResultObject())
	{
		$aResult[] = $oRow;
	}
	return $aResult;
}

/**
 * Create article-language relation
 * @return int
 */
function tool_CreateArticleLanguage($iArticleId, $iLanguageId, $iTemplateConfigurationId, $sTitle, $sPageTitle, $sSummary, $iArticleSpecification, $iOnline, $iRedirect, $sRedirectUrl, $iSortSequence, $iTimeControl, $sTimeControlStart, $sTimeControlEnd, &$cfg, &$db, &$auth, $bDebug = false)
{
	if (intval($iLanguageId) == 0) { return -1; } // if for some strange reason there are entries with a non-existing idlang, NULL or String.Empty will be passed
	$iTimeControl = (int)$iTimeControl; // missing or NULL values will be converted to 0
	if (!is_int((int)$iArticleId) OR $iArticleId < 0 OR !is_int((int)$iLanguageId) OR $iLanguageId < 0 OR !is_int((int)$iTemplateConfigurationId) OR $iTemplateConfigurationId < 0 OR strlen($sTitle) == 0  OR !is_int((int)$iArticleSpecification) OR $iArticleSpecification < 0 OR !is_int((int)$iOnline) OR !in_array($iOnline, array(0, 1)) OR !is_int((int)$iRedirect) OR !in_array($iRedirect, array(0, 1)) OR !is_int((int)$iSortSequence) OR $iSortSequence < 0) { return -1;}

	$iNewArticleLanguageId = $db->nextid($cfg["tab"]["art_lang"]);

    $sql = "INSERT INTO ".$cfg["tab"]["art_lang"]."
			(idartlang, idart, idlang, idtplcfg, title, pagetitle, summary, artspec, created, lastmodified, author, modifiedby, online, redirect, redirect_url, artsort, timemgmt, datestart, dateend, free_use_01, free_use_02, free_use_03, time_move_cat, time_target_cat, time_online_move)
			VALUES
			(".$iNewArticleLanguageId.", ".$iArticleId.", ".$iLanguageId.", ".$iTemplateConfigurationId.", '".addslashes($sTitle)."', '".addslashes($sPageTitle)."', '".$sSummary."', ".$iArticleSpecification.", NOW(), NOW(), '".$auth->auth["uname"]."', '".$auth->auth["uname"]."', ".$iOnline.", ".$iRedirect.", '".$sRedirectUrl."', ".$iSortSequence.", ".$iTimeControl.", '".$sTimeControlStart."', '".$sTimeControlEnd."', 0, 0, 0, 0, 0, 0) ";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}

    $db->query($sql);

    return $iNewArticleLanguageId;

}

/**
 * Create category-article relation
 * @return int
 */
function tool_CreateCategoryArticle($iCategoryId, $iArticleId, $iIsStart, $iStatus, $iCreateCode, &$cfg, &$db, &$auth, $bDebug = false)
{
	if (!is_int((int)$iCategoryId) OR $iCategoryId < 0 OR !is_int((int)$iArticleId) OR $iArticleId < 0) { return -1;}

	$iNewCategorArticleId = $db->nextid($cfg["tab"]["cat_art"]);

    $sql = "INSERT INTO ".$cfg["tab"]["cat_art"]."
			(idcatart, idcat, idart, is_start, status, createcode, author, created, lastmodified)
			VALUES
			(".$iNewCategorArticleId.", ".$iCategoryId.", ".$iArticleId.", ".$iIsStart.", ".$iStatus.", ".$iCreateCode.", '".$auth->auth["uname"]."', NOW(), NOW()) ";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}

    $db->query($sql);

    return $iNewCategorArticleId;

}

/**
 * Get category-articles by client
 * @return array
 */
function tool_getCategoryArticlesByClient($iClientId, &$cfg, &$db, $bDebug = false)
{
	if (!is_int((int)$iClientId) OR $iClientId <= 0) { return array();}

    $sql = "SELECT * FROM ".$cfg["tab"]["cat"]." AS A, ".$cfg["tab"]["cat_art"]." AS B WHERE A.idclient = ".$iClientId." AND A.idcat = B.idcat ";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}

    $db->query($sql);

    $aResult = array();
//	while($oRow = mysql_fetch_object($db->Query_ID))
	while($oRow = $db->getResultObject())
	{
		$aResult[] = $oRow;
	}
	return $aResult;
}

/**
 * Get languages by client
 * @return array
 */
function tool_getLanguagesByClient($iClientId, &$cfg, &$db, $bDebug = false)
{
	if (!is_int((int)$iClientId) OR $iClientId <= 0) { return array();}

    $sql = "SELECT * FROM ".$cfg["tab"]["lang"]." AS A, ".$cfg["tab"]["clients_lang"]." AS B WHERE B.idclient = ".$iClientId." AND A.idlang = B.idlang";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}

    $db->query($sql);

    $aResult = array();
//	while($oRow = mysql_fetch_object($db->Query_ID))
	while($oRow = $db->getResultObject())
	{
		$aResult[] = $oRow;
	}
	return $aResult;
}

/**
 * Create language
 * @return int
 */
function tool_CreateLanguage($iClientId, $sName, $iActive, $sEncoding, $sDirection, &$cfg, &$db, &$auth, $bDebug = false)
{

	if (!is_int((int)$iClientId) OR $iClientId < 0 OR strlen($sName) == 0 OR !is_int((int)$iActive) OR !in_array($iActive, array(0, 1)) OR strlen($sEncoding) == 0 OR strlen($sDirection) == 0) { return -1;}

	$iNewLanguageId  = $db->nextid($cfg["tab"]["lang"]);

	$sql = "INSERT INTO ".$cfg["tab"]["lang"]."
			(idlang, name, active, encoding, direction, author, created, lastmodified)
			VALUES
			(".$iNewLanguageId.", '".$sName."', ".$iActive.", '".$sEncoding."', '".$sDirection."', '".$auth->auth["uid"]."', NOW(), NOW())";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}

	$db->query($sql);

	$sql = "INSERT INTO ".$cfg["tab"]["clients_lang"]."
			(idclientslang, idclient, idlang)
			VALUES
			(".$db->nextid($cfg["tab"]["clients_lang"]).", ".$iClientId.", ".$iNewLanguageId.")";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}

	$db->query($sql);

    return $iNewLanguageId;

}

/**
 * Get category-language by client
 * @return array
 */
function tool_getCategoryLanguageByClient($iClientId, &$cfg, &$db, $bDebug = false)
{
	if (!is_int((int)$iClientId) OR $iClientId <= 0) { return array();}

    $sql = "SELECT * FROM ".$cfg["tab"]["cat"]." AS A, ".$cfg["tab"]["cat_lang"]." AS B WHERE A.idclient = ".$iClientId." AND A.idcat = B.idcat ";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}

    $db->query($sql);

    $aResult = array();
//	while($oRow = mysql_fetch_object($db->Query_ID))
	while($oRow = $db->getResultObject())
	{
		$aResult[] = $oRow;
	}
	return $aResult;
}

/**
 * Create category-language
 * @return int
 */
function tool_CreateCategoryLanguage($iCatgoryId, $iLanguageId, $iTemplateConfigurationId, $sName, $iVisible, $iPublic, $iStatus, $iStartArticleLanguageId, $sUrlName, &$cfg, &$db, &$auth, $bDebug = false)
{
	if (intval($iLanguageId) == 0) { return -1; } // if for some strange reason there are entries with a non-existing idlang, NULL or String.Empty will be passed
	if (!is_int((int)$iCatgoryId) OR $iCatgoryId < 0 OR !is_int((int)$iLanguageId) OR $iLanguageId < 0 OR !is_int((int)$iTemplateConfigurationId) OR $iTemplateConfigurationId < 0 OR strlen($sName) == 0 OR !is_int((int)$iVisible) OR !in_array($iVisible, array(0, 1)) OR !is_int((int)$iPublic) OR !in_array($iPublic, array(0, 1)) OR !is_int((int)$iStartArticleLanguageId) OR $iStartArticleLanguageId < 0) { return -1;}

	$iNewCategoryLanguageId  = $db->nextid($cfg["tab"]["cat_lang"]);

	$sql = "INSERT INTO ".$cfg["tab"]["cat_lang"]."
			(idcatlang, idcat, idlang, idtplcfg, name, visible, public, status, startidartlang, urlname, author, created, lastmodified)
			VALUES
			(".$iNewCategoryLanguageId.", ".$iCatgoryId.", ".$iLanguageId.", ".$iTemplateConfigurationId.", '".$sName."', ".$iVisible.", ".$iPublic.", ".$iStatus.", ".$iStartArticleLanguageId.", '".$sUrlName."', '".$auth->auth["uid"]."', NOW(), NOW())";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}

	$db->query($sql);

    return $iNewCategoryLanguageId;

}

/**
 * Set template configuration id
 */
function tool_setTemplateConfiguration($iTemplateId, &$cfg, &$db, &$auth, $bDebug = false)
{
	if (!is_int((int)$iTemplateId) OR $iTemplateId < 0) { return 0;}

    $idtplcfg = $db->nextid($cfg["tab"]["tpl_conf"]);

    $sql = "INSERT INTO ".$cfg["tab"]["tpl_conf"]."
            (idtplcfg, idtpl, status, author, created, lastmodified) VALUES
            (".$idtplcfg.", ".$iTemplateId.", 0, '".$auth->auth["uname"]."', '".date("YmdHis")."', NOW())";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}

    $db->query($sql);

    return $idtplcfg;

}

/**
 * Get template by template-configuration-id
 */
function tool_getTemplateByTemplateConfiguration($iTemplateConfigurationId, &$cfg, &$db, $bDebug = false)
{
	if (!is_int((int)$iTemplateConfigurationId) OR $iTemplateConfigurationId <= 0) { return -1;}

	$sql = "SELECT * FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg = ".$iTemplateConfigurationId." ";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}

    $db->query($sql);

//	if($oRow = mysql_fetch_object($db->Query_ID))
	if($oRow = $db->getResultObject())
	{
		return $oRow->idtpl;
	}else
	{
		return 0;
	}

}

/**
 * Get content by article-language
 * @return array
 */
function tool_getContentByArticleLanguageId($iArticleLanguageId, &$cfg, &$db, $bDebug = false)
{
	if (!is_int((int)$iArticleLanguageId) OR $iArticleLanguageId < 0) { return array();}

    $sql = "SELECT * FROM ".$cfg["tab"]["content"]." WHERE idartlang = ".$iArticleLanguageId." ";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}

    $db->query($sql);

    $aResult = array();
//	while($oRow = mysql_fetch_object($db->Query_ID))
	while($oRow = $db->getResultObject())
	{
		$aResult[] = $oRow;
	}
	return $aResult;
}

/**
 * Set content by article-language
 */
function tool_setContent($iArticleLanguageId, $iContentType, $iContentTypeNumber, $sContent, &$cfg, &$db, &$auth, $bDebug = false)
{
	if (!is_int((int)$iArticleLanguageId) OR $iArticleLanguageId < 0 OR !is_int((int)$iContentType) OR $iContentType < 0 OR !is_int((int)$iContentTypeNumber) OR $iContentTypeNumber < 0) { return 0;}

    $iNewContentId = $db->nextid($cfg["tab"]["content"]);

    $sql = "INSERT INTO ".$cfg["tab"]["content"]."
            (idcontent, idartlang, idtype, typeid, value, version, author, created, lastmodified) VALUES
            (".$iNewContentId.", ".$iArticleLanguageId.", ".$iContentType.", ".$iContentTypeNumber.", '".$sContent."', '', '".$auth->auth["uname"]."', '".date("YmdHis")."', NOW())";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}

    $db->query($sql);

    return $iNewContentId;

}

/**
 * Get upload elements by client
 * @return array
 */
function tool_getUploadElementsByClient ($iClientId, &$cfg, &$db, $bDebug = false)
{
	if (!is_int((int)$iClientId) OR $iClientId <= 0) { return array();}

    $sql = "SELECT * FROM ".$cfg["tab"]["upl"]." WHERE idclient = ".$iClientId." ";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}

    $db->query($sql);

    $aResult = array();
//	while($oRow = mysql_fetch_object($db->Query_ID))
	while($oRow = $db->getResultObject())
	{
		$aResult[] = $oRow;
	}
	return $aResult;
}

/**
 * Set upload element
 */
function tool_setUploadElement ($iClientId, $sFilename, $sDirname, $sFiletype, $sSize, $sDescription, &$cfg, &$db, &$auth, $bDebug = false)
{
	if (!is_int((int)$iClientId) OR $iClientId <= 0 OR strlen($sFilename) == 0 OR strlen($sDirname) == 0) { return 0;}

    $iNewUploadId = $db->nextid($cfg["tab"]["upl"]);

    $sql = "INSERT INTO ".$cfg["tab"]["upl"]."
            (idupl, idclient, filename, dirname, filetype, size, description, author, created, lastmodified, modifiedby) VALUES
            (".$iNewUploadId.", ".$iClientId.", '".$sFilename."', '".$sDirname."', '".$sFiletype."', '".$sSize."', '".$sDescription."', '".$auth->auth["uid"]."', NOW(), NOW(), '".$auth->auth["uid"]."')";

	if ($bDebug) {echo "<pre>".$sql."</pre>";}

    $db->query($sql);

    return $iNewUploadId;

}


?>