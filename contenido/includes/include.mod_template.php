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
 * @version    1.5.1
 * @author     Willi Mann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * 
 * {@internal 
 *   created 2004-07-14
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2008-08-14, Timo Trautmann, Bilal Arslan - Functions for versionning and storing file meta data added
 *
 *   $Id: include.html_tpl_edit_form.php 713 2008-08-21 14:01:51Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "class.ui.php");
cInclude("classes", "widgets/class.widgets.page.php");
cInclude("classes", "class.htmlvalidator.php");
cInclude("external", "codemirror/class.codemirror.php");
cInclude("includes", "functions.file.php");
cInclude("classes", "class.notification.php");
cInclude("classes", "module/class.contenido.module.handler.php");
cInclude("classes", "module/class.contenido.modulTemplate.php");
$sFileType = "html";

$sActionCreate = 'htmltpl_create';
$sActionEdit = 'htmltpl_edit';

$fileRequest =    $_REQUEST['file'];
$TmpFileRequest = $_REQUEST['tmp_file'];


$page = new cPage;

$tpl->reset();



if(! is_object($notification))
    $notification = new Contenido_Notification();
    
$contenidoModulHandler = new Contenido_Module_Handler($idmod);

//$TmpFileRequest= $contenidoModulHandler->getTemplateFileName();//$contenidoModulHandler->getTemplateMainFile();
//$fileRequest =    $contenidoModulHandler->getTemplateFileName();// $contenidoModulHandler->getTemplateMainFile();
//$_REQUEST['action'] = $sActionEdit;




if (!$perm->have_perm_area_action($area, $action))
{
    $notification->displayNotification("error", i18n("Permission denied"));
} else if (!(int) $client > 0) {
  #if there is no client selected, display empty page
  $page->render();
} else {
   
    
    
    $contenidoModulTemplateHandler = new Contenido_Modul_Templates_Handler($idmod);
    $contenidoModulTemplateHandler->setAction($action);
    $contenidoModulTemplateHandler->setCode($_REQUEST['code']);
    $contenidoModulTemplateHandler->setFiles($_REQUEST['file'], $_REQUEST['tmp_file']);
    $contenidoModulTemplateHandler->setFrameIdmodArea($frame, $idmod, $area);
    $contenidoModulTemplateHandler->setNewDelete($_REQUEST['new'], $_REQUEST['delete']);
    $contenidoModulTemplateHandler->setSelectedFile($_REQUEST['selectedFile']);
    $contenidoModulTemplateHandler->setStatus($_REQUEST['status']);
    
    $contenidoModulTemplateHandler->display($perm, $notification, $belang);
    
    
       
}
?> 