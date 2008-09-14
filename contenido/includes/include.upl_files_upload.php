<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Upload files
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.7.0
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-12-30
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "class.ui.php");
cInclude("classes", "class.htmlelements.php");
cInclude("classes", "class.properties.php");
cInclude("includes", "functions.upl.php");

$page = new UI_Page;

if ((is_writable($cfgClient[$client]["upl"]["path"].$path) || is_dbfs($path)) && (int) $client > 0)
{
    $form = new UI_Table_Form("properties");
    $form->setVar("frame", $frame);
    $form->setVar("area", "upl");
    $form->setVar("path", $path);
    $form->setVar("file", $file);
    $form->setVar("action", "upl_upload");
    $form->setVar("appendparameters", $_REQUEST["appendparameters"]);
    
    $form->addHeader(i18n("Upload"));
	
    if (is_dbfs($path))
		$mpath = $path."/";	
	else 
		$mpath = "upload/".$path;
		
    $sDisplayPath = generateDisplayFilePath($mpath, 85);
    $form->add(i18n("Path:"), $sDisplayPath);
	
    $uplelement = new cHTMLUpload("file[]",40);
    $num_upload_files = getEffectiveSetting('backend','num_upload_files',10);
    $form->add(i18n("Upload files"), str_repeat($uplelement->render()."<br>"	,$num_upload_files));
    
    $page->setContent($form->render());
} else {
	$page->setContent($notification->returnNotification("error", i18n("Directory not writable")));
}	
$page->render();
?>