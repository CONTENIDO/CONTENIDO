<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Upload files
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.7.1
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * 
 * {@internal 
 *   created 2003-12-30
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2010-09-20, Dominik Ziegler, added path to error message when directory is not writable - CON-319
 *	 modified 2012-01-13, Mischa Holz, added divs and scripts for drag and drop support
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


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
	
    $uplelement = new cHTMLUpload("file[]", 40);
    $num_upload_files = getEffectiveSetting('backend','num_upload_files',10);
    $form->add(i18n("Upload files"), str_repeat($uplelement->render()."<br>"	,$num_upload_files));
    
    $page->addScript("jq", "<script type='text/javascript' src='scripts/jquery/jquery.js'></script>");
    $page->addScript("dnd1", "<script type='text/javascript'>var contenido_id = '".$_REQUEST['contenido']."'; var upload_path = '".$path."';</script>");
	$page->addScript("dnd2", "<script type='text/javascript' src='scripts/dragAndDropUpload.js'></script>");
    
    $form->setWidth(500);
    
    $page->setContent("<div id='dropbox_area'>
			<div class='dropbox' id='dropbox'>" . i18n("Drop your files here") . "</div>
			<div class='shelf' id='shelf'></div>".$form->render()."</div>");
} else {
	$page->setContent($notification->returnNotification("error", i18n("Directory not writable") . ' (' . $cfgClient[$client]["upl"]["path"].$path . ')'));
}	
$page->render();
?>