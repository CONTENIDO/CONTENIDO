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
 * @version    1.0.1
 * @author     Willi Man
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2004-07-13
 *   modified 2008-06-26, Frederic Schneider, add security fix
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
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