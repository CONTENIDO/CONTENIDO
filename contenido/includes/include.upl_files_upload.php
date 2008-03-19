<?php
/*****************************************
* File      :   $RCSfile: include.upl_files_upload.php,v $
* Project   :   Contenido
* Descr     :   Directory overview
*
* Author    :   Timo A. Hummel
*               
* Created   :   30.12.2003
* Modified  :   $Date: 2006/06/12 17:29:07 $
*
* © four for business AG, www.4fb.de
*
* $Id: include.upl_files_upload.php,v 1.7 2006/06/12 17:29:07 bjoern.behrens Exp $
******************************************/

cInclude("classes", "class.ui.php");
cInclude("classes", "class.htmlelements.php");
cInclude("classes", "class.properties.php");
cInclude("includes", "functions.upl.php");

$page = new UI_Page;

if (is_writable($cfgClient[$client]["upl"]["path"].$path) || is_dbfs($path))
{
    $form = new UI_Table_Form("properties");
    $form->setVar("frame", $frame);
    $form->setVar("area", "upl");
    $form->setVar("path", $path);
    $form->setVar("file", $file);
    $form->setVar("action", "upl_upload");
    $form->setVar("appendparameters", $_REQUEST["appendparameters"]);
    
    $form->addHeader(i18n("Upload"));
    $uplelement = new cHTMLUpload("file[]",40);
    $num_upload_files = getEffectiveSetting('backend','num_upload_files',10);
    $form->add(i18n("Upload files"), str_repeat($uplelement->render()."<br>"	,$num_upload_files));
    
    $page->setContent($form->render());
} else {
	$page->setContent($notification->returnNotification("error", i18n("Directory not writable")));
}	
$page->render();
?>