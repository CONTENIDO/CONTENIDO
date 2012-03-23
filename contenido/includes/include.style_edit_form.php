<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Edit file
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.2
 * @author     Olaf Niemann, Willi Mann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * 
 * {@internal 
 *   created 2003-04-20
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2008-08-14, Timo Trautmann, Bilal Arslan - Functions for versionning and storing file meta data added
 *	 modified 2012-02-15, Rusmir Jusufovic , Show message for user 
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


cInclude("external", "codemirror/class.codemirror.php");

$sFileType = "css";

$sActionCreate = 'style_create';
$sActionEdit = 'style_edit';
$sActionDelete = 'style_delete';
$sFilename = '';
$page = new cPage;
$page->setEncoding("utf-8");

$tpl->reset();

if (!$perm->have_perm_area_action($area, $action))
{
    $notification->displayNotification("error", i18n("Permission denied"));
} else if (!(int) $client > 0) {
  #if there is no client selected, display empty page
  $page->render();
}elseif( $action == $sActionDelete ){

	$path = $cfgClient[$client]["css"]["path"];
	# delete file
	if (!strrchr($_REQUEST['delfile'], "/"))
	{
		if (file_exists($path.$_REQUEST['delfile']))
		{
			unlink($path.$_REQUEST['delfile']);
			removeFileInformation($client, $_REQUEST['delfile'], 'css', $db);
			$notification->displayNotification(Contenido_Notification::LEVEL_INFO, i18n("Deleted CSS file successfully!"));
		}
	}
	$sReloadScript = "<script type=\"text/javascript\">
		var left_bottom = parent.parent.frames['left'].frames['left_bottom'];
		if (left_bottom) {
		var href = left_bottom.location.href;
		href = href.replace(/&file.*/, '');
		left_bottom.location.href = href+'&file='+'".$_REQUEST['file']."';
	
		}
	</script>";
	$page->addScript('reload', $sReloadScript);
	$page->render();
} else {    
    $path = $cfgClient[$client]["css"]["path"];
    if (stripslashes($_REQUEST['file'])) {
        $sReloadScript = "<script type=\"text/javascript\">
                             var left_bottom = parent.parent.frames['left'].frames['left_bottom'];
                             if (left_bottom) {
                                 var href = left_bottom.location.href;
                                 href = href.replace(/&file.*/, '');
                                 left_bottom.location.href = href+'&file='+'".$_REQUEST['file']."';

                             }
                         </script>";
    } else {
        $sReloadScript = "";
    }

	$sTempFilename = stripslashes($_REQUEST['tmp_file']);
    $sOrigFileName = $sTempFilename;
	
	if (getFileType($_REQUEST['file']) != $sFileType AND strlen(stripslashes(trim($_REQUEST['file']))) > 0)
    {
    	$sFilename .= stripslashes($_REQUEST['file']).".$sFileType";
    }else
    {
    	$sFilename .= stripslashes($_REQUEST['file']);
    }
        
    if (stripslashes($_REQUEST['file'])) {
        $sReloadScript = "<script type=\"text/javascript\">
                             var left_bottom = parent.parent.frames['left'].frames['left_bottom'];
                             if (left_bottom) {
                                 var href = left_bottom.location.href;
                                 href = href.replace(/&file[^&]*/, '');
                                 left_bottom.location.href = href+'&file='+'".$sFilename."';

                             }
                         </script>";
    } else {
        $sReloadScript = "";
    }    
    
    // Content Type is css
	$sTypeContent = "css";
    $aFileInfo = getFileInformation ($client, $sTempFilename, $sTypeContent, $db);
    
	# create new file
    if ( $_REQUEST['action'] == $sActionCreate AND $_REQUEST['status'] == 'send')
    {
    	$sTempFilename = $sFilename;
    	createFile($sFilename, $path);
    	$bEdit = fileEdit($sFilename, $_REQUEST['code'], $path);
        updateFileInformation($client, $sFilename, 'css', $auth->auth['uid'], $_REQUEST['description'], $db);
        $sReloadScript .= "<script type=\"text/javascript\">
                 var right_top = top.content.right.right_top;
                 if (right_top) {
                     var href = '".$sess->url("main.php?area=$area&frame=3&file=$sTempFilename")."';
                     right_top.location.href = href;
                 }
                 </script>";
        if($bEdit) {
        	$notification->displayNotification(Contenido_Notification::LEVEL_INFO, i18n("Created new CSS file successfully!"));
        }
      }

	# edit selected file
    if ( $_REQUEST['action'] == $sActionEdit AND $_REQUEST['status'] == 'send') 
    {
    	$tempTemplate = $sTempFilename;
    	if ($sFilename != $sTempFilename)
    	{	
    		$sTempFilename = renameFile($sTempFilename, $sFilename, $path);
            $sReloadScript .= "<script type=\"text/javascript\">
                 var right_top = top.content.right.right_top;
                 if (right_top) {
                     var href = '".$sess->url("main.php?area=$area&frame=3&file=$sTempFilename")."';
                     right_top.location.href = href;
                 }
                 </script>";
    	}else
    	{	
    		$sTempFilename = $sFilename;
    	}
		
    	$bEdit = fileEdit($sFilename, $_REQUEST['code'], $path);
    	
    	//show message
    	if($sFilename != $tempTemplate && $bEdit) {
    		
    		$notification->displayNotification(Contenido_Notification::LEVEL_INFO, i18n("Renamed CSS file successfully!"));
    	}elseif($bEdit) {
    		
    		$notification->displayNotification(Contenido_Notification::LEVEL_INFO, i18n("Saved changes successfully!"));
    	}else {
    		$notification->displayNotification(Contenido_Notification::LEVEL_ERROR, i18n("Can't save file!"));
    	}
        updateFileInformation($client, $sOrigFileName, 'css', $auth->auth['uid'], $_REQUEST['description'], $db, $sFilename);
        
		/**
		* START TRACK VERSION
		**/
        // For read Fileinformation an get the id of current File
        cInclude("includes", "functions.file.php");
			
        if((count($aFileInfo) == 0) || ((int)$aFileInfo["idsfi"] == 0)) {
            $aFileInfo = getFileInformation ($client, $sTempFilename, $sTypeContent, $db); 
            $aFileInfo['description'] = '';
        }
        
        if((count($aFileInfo) == 0) || ($aFileInfo["idsfi"] !="")) {        
            $oVersion = new VersionFile($aFileInfo["idsfi"], $aFileInfo, $sFilename, $sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame, $sOrigFileName);				
            // Create new version
            $oVersion->createNewVersion();
        }

		/**
		* END TRACK VERSION
		**/
    	
    	
    	
        
	}
	
	# generate edit form 
	if (isset($_REQUEST['action']))	{
		
		$sAction = ($bEdit) ? $sActionEdit : $_REQUEST['action'];
        
        if ($_REQUEST['action'] == $sActionEdit)
		{
			$sCode = getFileContent($sFilename, $path);
		}else
		{
			$sCode = stripslashes($_REQUEST['code']); # stripslashes is required here in case of creating a new file
		}
        
        $aFileInfo = getFileInformation ($client, $sTempFilename, "css", $db);

		
        $form = new UI_Table_Form("file_editor");
        $form->addHeader(i18n("Edit file"));
        $form->setWidth("100%");
        $form->setVar("area", $area);
        $form->setVar("action", $sAction);
        $form->setVar("frame", $frame);
        $form->setVar("status", 'send');
        $form->setVar("tmp_file", $sTempFilename);
        
        $tb_name = new cHTMLTextbox("file", $sFilename, 60);
        $ta_code = new cHTMLTextarea("code", htmlspecialchars($sCode), 100, 35, "code");
        $descr	 = new cHTMLTextarea("description", htmlspecialchars($aFileInfo["description"]), 100, 5);

		
        $ta_code->setStyle("font-family: monospace;width: 100%;");
        $descr->setStyle("font-family: monospace;width: 100%;");
        $ta_code->updateAttributes(array("wrap" => getEffectiveSetting('style_editor', 'wrap', 'off')));
        
        $form->add(i18n("Name"),$tb_name);
        $form->add(i18n("Description"), $descr->render());
        $form->add(i18n("Code"),$ta_code);            
        
        $page->setContent($form->render());
        
        $oCodeMirror = new CodeMirror('code', 'css', substr(strtolower($belang), 0, 2), true, $cfg);
        $page->addScript('codemirror', $oCodeMirror->renderScript());
        
        $page->addScript('reload', $sReloadScript);
    	$page->render();  
    }
}

?> 