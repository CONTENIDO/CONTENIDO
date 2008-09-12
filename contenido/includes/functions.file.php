<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Functions to edit files. Included in Area style,
 * js, htmltpl in Frame right_bottom.
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.2
 * @author     Willi Man
 * @copyright  four for business AG <info@contenido.org>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release >= 4.6
 * 
 * {@internal 
 *   created 2004-07-13
 *   modified 2008-06-26, Frederic Schneider, add security fix
 *   modified 2008-08-14, Timo.Trautmann added file_information functions for storing file meta indormations
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

/**
 * Function removes file meta information from database (used when a file is deleted)
 *
 * @author Timo Trautmann
 * @param integer $iIdClient - id of client which contains this file
 * @param string  $sFilename - name of corresponding file
 * @param string  $sType - type of file (css, js or templates)
 * @param object  $oDb - contenido database object
 * 
 * @return void
 */
function removeFileInformation($iIdClient, $sFilename, $sType, $oDb) {
    global $cfg;

    if (!isset($oDb) || !is_object($oDb)) {
        $oDb = new DB_Contenido();
    }
    
    $iIdClient = Contenido_Security::toInteger($iIdClient);    
    $sFilename = Contenido_Security::filter((string) $sFilename, $oDb);
    $sType = Contenido_Security::filter((string) $sType, $oDb);
    
    $sSql = "DELETE FROM `".$cfg["tab"]["file_information"]."` WHERE idclient=$iIdClient AND 
                                                            filename='$sFilename' AND 
                                                            type='$sType';"; 
    $oDb->query($sSql);
    $oDb->free();
}

/**
 * Function returns file meta information from database (used when files were versionned or description is displayed)
 *
 * @author Timo Trautmann
 * @param integer $iIdClient - id of client which contains this file
 * @param string  $sFilename - name of corresponding file
 * @param string  $sType - type of file (css, js or templates)
 * @param object  $oDb - contenido database object
 *
 * @return array   Indexes:
 *                           idsfi - Primary key of database record
 *                           created - Datetime when file was created
 *                           lastmodified - Datetime when file was last modified
 *                           author - Author of file (Contenido Backend User)
 *                           modifiedby - Last modifier of file (Contenido Backend User)
 *                           description - Description which was inserted for this file
 *                           
 */
function getFileInformation ($iIdClient, $sFilename, $sType, $oDb) {
    global $cfg;
    
    if (!isset($oDb) || !is_object($oDb)) {
        $oDb = new DB_Contenido();
    }
    
    $iIdClient = Contenido_Security::toInteger($iIdClient);
    $sFilename = Contenido_Security::filter((string) $sFilename, $oDb);
    $sType = Contenido_Security::filter((string) $sType, $oDb);
    
    $aFileInformation = array();
    $sSql = "SELECT * FROM `".$cfg["tab"]["file_information"]."` WHERE idclient=$iIdClient AND 
                                                            filename='$sFilename' AND 
                                                            type='$sType';"; 
    $oDb->query($sSql);
    if ($oDb->num_rows() > 0) {
        $oDb->next_record();
        $aFileInformation['idsfi'] = $oDb->f('idsfi');
        $aFileInformation['created'] = $oDb->f('created');
        $aFileInformation['lastmodified'] = $oDb->f('lastmodified');
        $aFileInformation['author'] = Contenido_Security::unFilter($oDb->f('author'));
        $aFileInformation['modifiedby'] = $oDb->f('modifiedby');
        $aFileInformation['description'] = Contenido_Security::unFilter($oDb->f('description'));
    }
    $oDb->free();
    
    return $aFileInformation;
}

/**
 * Function updates file meta information (used when files were created or edited).
 * It creates new database record for file meta informations if database record does
 * not exist. Otherwise, existing record will be updated
 *
 * @author Timo Trautmann
 * @param integer $iIdClient - id of client which contains this file
 * @param string  $sFilename - name of corresponding file
 * @param string  $sType - type of file (css, js or templates)
 * @param string  $sAuthor - author of file
 * @param string  $sDescription - description of file
 * @param object  $oDb - contenido database object
 * @param string  $sFilenameNew - new filename if filename was changed (optional)
 * 
 * @return void                          
 */
function updateFileInformation($iIdClient, $sFilename, $sType, $sAuthor, $sDescription, $oDb, $sFilenameNew = '') {
    global $cfg;
    
    if (!isset($oDb) || !is_object($oDb)) {
        $oDb = new DB_Contenido();
    }

    if ($sFilenameNew == '') {
        $sFilenameNew = $sFilename;
    }

    $iIdClient = Contenido_Security::toInteger($iIdClient);
    $sFilename = Contenido_Security::filter((string) $sFilename, $oDb);
    $sType = Contenido_Security::filter((string) $sType, $oDb);
    $sDescription = Contenido_Security::filter((string) stripslashes($sDescription), $oDb);
    $sAuthor = Contenido_Security::filter((string) $sAuthor, $oDb);
    
    $sSql = "SELECT * from `".$cfg["tab"]["file_information"]."` WHERE idclient=$iIdClient AND 
                                                            filename='$sFilename' AND 
                                                            type='$sType';";   
    $oDb->query($sSql);
    if ($oDb->num_rows() == 0) {
        $iNextId = $oDb->nextid('con_style_file_information');
        $sSql = "INSERT INTO `".$cfg["tab"]["file_information"]."` ( `idsfi` , 
                                                            `idclient` , 
                                                            `type` , 
                                                            `filename` , 
                                                            `created` ,            
                                                            `lastmodified` , 
                                                            `author` , 
                                                            `modifiedby` , 
                                                            `description` )
                                                        VALUES (
                                                            $iNextId , 
                                                            $iIdClient, 
                                                            '$sType', 
                                                            '$sFilenameNew', 
                                                            NOW(), 
                                                            '0000-00-00 00:00:00', 
                                                            '$sAuthor', 
                                                            '', 
                                                            '$sDescription'
                                                        );";
    } else {
        $sSql = "UPDATE `".$cfg["tab"]["file_information"]."` SET `lastmodified` = NOW(),
                                                         `modifiedby` = '$sAuthor',
                                                         `description` = '$sDescription',
                                                         `filename` = '$sFilenameNew' 
                                                         
                                                         WHERE idclient=$iIdClient AND 
                                                               filename='$sFilename' AND 
                                                               type='$sType';";
    }

    $oDb->free();
    $oDb->query($sSql);
    $oDb->free();
}

function fileEdit($filename, $sCode, $path) 
{
    global $notification;

   	if (strlen(trim($filename)) == 0)
    {
    	$notification->displayNotification("error", i18n("Please insert filename."));
    	return false;
    }
    if (ereg("[^a-zA-Z0-9._-]", $filename))
    {
		$notification->displayNotification("error", i18n("Wrong filename."));
    	exit;
    }
        
	if (is_writable($path.$filename)) 
	{
		if (strlen(stripslashes(trim($sCode))) > 0)
		{
            # open file
            if (!$handle = fopen($path.$filename, "wb+")) 
            {
            	$notification->displayNotification("error", sprintf(i18n("Could not open file %s"), $path.$filename));
            	exit;
            }
            # write file
            if (!fwrite($handle, stripslashes($sCode))) 
            {
            	$notification->displayNotification("error", sprintf(i18n("Could not write file %s"), $path.$filename));
            	exit;
            }
            
            fclose($handle);
            return true;
        
        }else 
        {
        	return false;
        }
	}else 
    {
    	$notification->displayNotification("error", sprintf(i18n("%s is not writable"), $path.$filename));
    	exit;
    }
		      
    
    
}

function getFileContent($filename, $path)
{
   global $notification;

   if (!$handle = fopen($path.$filename, "rb"))
   {
      $notification->displayNotification("error", sprintf(i18n("Can not open file%s "), $path.$filename));
      exit;
   }

    do {
        $_data = fread($handle, 4096);
        if (strlen($_data) == 0) {
            break;
        }
        $sFileContents .= $_data;
    } while(true);

   fclose($handle);
   return $sFileContents;

}

function getFileType($filename)
{
  	$aFileName = explode(".", $filename);
  	return $aFileName[count($aFileName) - 1];
}

function createFile($filename, $path)
{
	global $notification;
	
	if (ereg("[^a-zA-Z0-9._-]", $filename))
    {
		$notification->displayNotification("error", i18n("Wrong filename."));
    	exit;
    }
  	
	# create the file
    if(touch($path.$filename))
    {
    	# change file access permission
        if(chmod ($path.$filename, 0777))
        {
        	return true;
        }else
        {
        	$notification->displayNotification("error", $path.$filename." ".i18n("Unable to change file access permission."));
    		exit;
        }
    	
    }else
    {
    	$notification->displayNotification("error", sprintf(i18n("Unable to create file %s"), $path.$filename));
		exit;
    }
    
}

function renameFile($sOldFile, $sNewFile, $path)
{
	global $notification;
	
	if (ereg("[^a-zA-Z0-9._-]", $sNewFile))
    {
		$notification->displayNotification("error", i18n("Wrong filename."));
    	exit;
    }
  	
  	if (is_writable($path.$sOldFile)) 
	{
        # rename file
    	if (rename($path.$sOldFile, $path.$sNewFile))
    	{
    		return $sNewFile;
    	}else
    	{
    		$notification->displayNotification("error", sprintf(i18n("Can not rename file %s"),$path.$sOldFile));
    		exit;
    	}
	}else
	{
		$notification->displayNotification("error", sprintf(i18n("%s is not writable"), $path.$sOldFile));
    	exit;
	}
    
}

?>