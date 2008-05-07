<?php
/******************************************
* File      :   $RCSfile: include.js_edit_form.php,v $
* Project   :   Contenido
* Descr     :   Edit file
* Frame		: 	right_bottom
* Area		:	js
*
* Author    :   Willi Man
* Created   :   14.07.2004
* Modified  :   $Date: 2005/09/22 11:44:19 $
*
* � four for business AG, www.4fb.de
* $Id: include.js_edit_form.php,v 1.10 2005/09/22 11:44:19 willi.man Exp $
*****************************************/

cInclude("classes", "class.ui.php");
cInclude("classes", "widgets/class.widgets.page.php");
cInclude("external", "edit_area/class.edit_area.php");

$sFileType = "js";

$sActionCreate = 'js_create';
$sActionEdit = 'js_edit';

$page = new cPage;

$tpl->reset();

if (!$perm->have_perm_area_action($area, $action))
{
    $notification->displayNotification("error", i18n("Permission denied"));
} else if (!(int) $client > 0) {
  #if there is no client selected, display empty page
  $page->render();
} else {
    $path = $cfgClient[$client]["js"]["path"];
	$sTempFilename = stripslashes($_REQUEST['tmp_file']);
	
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
                                 href = href.replace(/&file.*/, '');
                                 left_bottom.location.href = href+'&file='+'".$sFilename."';

                             }
                         </script>";
    } else {
        $sReloadScript = "";
    }
        
	# create new file
    if ( $_REQUEST['action'] == $sActionCreate AND $_REQUEST['status'] == 'send')
    {
    	$sTempFilename = $sFilename;
    	createFile($sFilename, $path);
    	$bEdit = fileEdit($sFilename, $_REQUEST['code'], $path);
    }

	# edit selected file
    if ( $_REQUEST['action'] == $sActionEdit AND $_REQUEST['status'] == 'send') 
    {
    	if ($sFilename != $sTempFilename)
    	{	
    		$sTempFilename = renameFile($sTempFilename, $sFilename, $path);
    	}else
    	{	
    		$sTempFilename = $sFilename;
    	}
    	
    	$bEdit = fileEdit($sFilename, $_REQUEST['code'], $path);
	}
	
	# generate edit form 
	if (isset($_REQUEST['action']))
	{
		
		$sAction = ($bEdit) ? $sActionEdit : $_REQUEST['action'];
        
        if ($_REQUEST['action'] == $sActionEdit)
		{
			$sCode = getFileContent($sFilename, $path);
		}else
		{
			$sCode = stripslashes($_REQUEST['code']); # stripslashes is required here in case of creating a new file
		}
		
        $form = new UI_Table_Form("file_editor");
        $form->addHeader(i18n("Edit file"));
        $form->setWidth("100%");
        $form->setVar("area", $area);
        $form->setVar("action", $sAction);
        $form->setVar("frame", $frame);
        $form->setVar("status", 'send');
        $form->setVar("tmp_file", $sTempFilename);
        
        $tb_name = new cHTMLTextbox("file", $sFilename, 60);
        $ta_code = new cHTMLTextarea("code", htmlspecialchars($sCode), 100, 40, "code");
        $ta_code->setStyle("font-family: monospace;width: 100%;");
        $ta_code->updateAttributes(array("wrap" => getEffectiveSetting('script_editor', 'wrap', 'off')));
        
        $form->add(i18n("Name"),$tb_name);
        $form->add(i18n("Code"),$ta_code);            
        
        $page->setContent($form->render());
        
        $oEditArea = new EditArea('code', 'js', substr(strtolower($belang), 0, 2), true, $cfg);
        $page->addScript('editarea', $oEditArea->renderScript());
        
        $page->addScript('reload', $sReloadScript);
    	$page->render();  
    	  
    }
	
}

?> 