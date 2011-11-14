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
 *
 *   $Id: include.style_edit_form.php 713 2008-08-21 14:01:51Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "class.ui.php");
cInclude("classes", "widgets/class.widgets.page.php");
cInclude("external", "edit_area/class.edit_area.php");
cInclude("includes", "functions.file.php");
cInclude("classes", "module/class.contenido.module.handler.php");

$contenidoModulHandler = new Contenido_Module_Handler($idmod);
$sFileType = "css";

$sActionCreate = 'style_create';
$sActionEdit = 'style_edit';


$tmp_file = $contenidoModulHandler->getCssFileName();
$file = $contenidoModulHandler->getCssFileName();
 

if(empty($action))
    $actionRequest = $sActionEdit;
    else
    $actionRequest = $action;
    
    
$page = new cPage;
$page->setEncoding(Contenido_Vars::getVar('encoding'));

$tpl->reset();
$premCreate = false;

if( !$contenidoModulHandler->existFile('css', $contenidoModulHandler->getCssFileName()))
    if (!$perm->have_perm_area_action('style', $sActionCreate)) 
        $premCreate = true;   
   


if (!$perm->have_perm_area_action('style', $actionRequest)|| $premCreate)
{
    $notification->displayNotification("error", i18n("Permission denied"));
} else if (!(int) $client > 0) {
  #if there is no client selected, display empty page
  $page->render();
} else {    
    $path =$contenidoModulHandler->getCssPath();// $cfgClient[$client]["css"]["path"];
     #make automatic a new css file
    $contenidoModulHandler->makeNewModuleFile('css');
    
    if (stripslashes($file)) {
        $sReloadScript = "<script type=\"text/javascript\">
                             var left_bottom = parent.parent.frames['left'].frames['left_bottom'];
                             if (left_bottom) {
                                 var href = left_bottom.location.href;
                                 href = href.replace(/&file.*/, '');
                                 left_bottom.location.href = href+'&file='+'".$file."';

                             }
                         </script>";
    } else {
        $sReloadScript = "";
    }

	$sTempFilename = stripslashes($tmp_file);
    $sOrigFileName = $sTempFilename;
	
	if (getFileType($file) != $sFileType AND strlen(stripslashes(trim($file))) > 0)
    {
    	$sFilename .= stripslashes($file).".$sFileType";
    }else
    {
    	$sFilename .= stripslashes($file);
    }
        
    if (stripslashes($file)) {
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
    if ( $actionRequest == $sActionCreate AND $_REQUEST['status'] == 'send')
    {
    	$sTempFilename = $sFilename;
    	createFile($sFilename, $path);
    	
    	$tempCode = iconv(Contenido_Vars::getVar('encoding'), Contenido_Vars::getVar('fileEncoding'), $_REQUEST['code']);
    	$bEdit = fileEdit($sFilename,$tempCode , $path);
    	
        updateFileInformation($client, $sFilename, 'css', $auth->auth['uid'], $_REQUEST['description'], $db);
        $sReloadScript .= "<script type=\"text/javascript\">
                 var right_top = top.content.right.right_top;
                 if (right_top) {
                     var href = '".$sess->url("main.php?area=$area&frame=3&file=$sTempFilename")."';
                     right_top.location.href = href;
                 }
                 </script>";
    }

	# edit selected file
    if ( $actionRequest == $sActionEdit AND $_REQUEST['status'] == 'send') 
    {
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
		
				
        updateFileInformation($client, $sOrigFileName, 'css', $auth->auth['uid'], $_REQUEST['description'], $db, $sFilename);
        
		
    	
    	
    	$tempCode = iconv(Contenido_Vars::getVar('encoding'), Contenido_Vars::getVar('fileEncoding'), $_REQUEST['code']);
    	$bEdit = fileEdit($sFilename,$tempCode , $path);
        
	}
	
	# generate edit form 
	if (isset($actionRequest))	{
		
		$sAction = ($bEdit) ? $sActionEdit : $actionRequest;
        
        if ($actionRequest == $sActionEdit)
		{
			$sCode = iconv(Contenido_Vars::getVar('fileEncoding'), Contenido_Vars::getVar('encoding'),getFileContent($sFilename, $path));
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
        $form->setVar("idmod", $idmod);
        $tb_name = new  cHTMLLabel($sFilename, '');//new cHTMLTextbox("file", $sFilename, 60);
        $ta_code = new cHTMLTextarea("code", htmlspecialchars($sCode), 100, 35, "code");
        //$descr	 = new cHTMLTextarea("description", htmlspecialchars($aFileInfo["description"]), 100, 5);

		
        $ta_code->setStyle("font-family: monospace;width: 100%;");
       // $descr->setStyle("font-family: monospace;width: 100%;");
        $ta_code->updateAttributes(array("wrap" => getEffectiveSetting('style_editor', 'wrap', 'off')));
        
        $form->add(i18n("Name"),$tb_name);
        //$form->add(i18n("Description"), $descr->render());
        $form->add(i18n("Code"),$ta_code);            
        
        $page->setContent($form->render());
         
        $oEditArea = new EditArea('code', 'css', substr(strtolower($belang), 0, 2), true, $cfg);
        $page->addScript('editarea', $oEditArea->renderScript());
        
        //$page->addScript('reload', $sReloadScript);
    	$page->render();  
    }
}

?> 