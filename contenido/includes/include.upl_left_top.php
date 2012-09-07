<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Left top
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.1
 * @author     Olaf Niemann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-04-01
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id: include.upl_left_top.php 726 2008-08-25 15:07:18Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "class.ui.php");
cInclude("classes", "class.htmlelements.php");
cInclude("classes", "class.properties.php");
cInclude("classes", "class.upload.php");
cInclude("classes", "widgets/class.widgets.treeview.php");
cInclude("includes", "functions.con.php");
cInclude("includes", "functions.str.php");
cInclude("includes", "functions.upl.php");

$tpl->set('s', 'FORMACTION', '');
$sDisplayPath = '';
if (isset($_REQUEST['path'])) {
    $sDisplayPath = $_REQUEST['path'];
} else { 
    $sDisplayPath = $sCurrentPathInfo;
}

$sDisplayPath = generateDisplayFilePath($sDisplayPath, 35);
$tpl->set('s', 'CAPTION2', $sDisplayPath);

#display notification, if there is no client
if ((int) $client == 0) {
    $sNoClientNotification = '<div style="height: 2.5em;line-height: 2.5em;border: 1px solid #B3B3B3;padding-left:15px;">'.i18n('No Client selected').'</div>';
    $tpl->set('s', 'NOTIFICATION', $sNoClientNotification);
} else {
    $tpl->set('s', 'NOTIFICATION', '');
}

#####################
# Form for 'Search'
#####################
if ($appendparameters != 'filebrowser' && (int) $client > 0) {
    $search = new cHTMLTextbox("searchfor", $_REQUEST['searchfor'], 26);
    $sSearch->setStyle = "width:170px;";
    $sSearch = $search->render();

    $form = new UI_Form("search");
    $form->add("search", '<table border="0" cellspacing="0" cellpadding="0"><tr><td>'.$sSearch.'</td><td><input style="margin-left: 5px;" type="image" src="images/submit.gif"></td></tr></table>');
    $form->setVar("area", $area);
    $form->setVar("frame", $frame);
    $form->setVar("contenido", $sess->id);
    $form->setVar("appendparameters", $appendparameters);
    $tpl->set('s', 'SEARCHFORM', $form->render());
    $tpl->set('s', 'SEARCHTITLE', i18n("Search for"));
    $tpl->set('s', 'DISPLAY_SEARCH', 'block');
} else {
    $tpl->set('s', 'SEARCHFORM', '');
    $tpl->set('s', 'SEARCHTITLE', '');
    $tpl->set('s', 'DISPLAY_SEARCH', 'none');
}
    
if ($perm->have_perm_area_action("upl", "upl_mkdir") && (int) $client > 0)
{		
    $sCurrentPathInfo = "";
    if ($sess->is_registered("upl_last_path") && !isset($path))
    {
        $path = $upl_last_path; 
    }

    if ($path == "" || is_dbfs($path))
    {
        $sCurrentPathInfo = $path;
    } 
    else 
    {
        $sCurrentPathInfo = str_replace($cfgClient[$client]['upl']['path'], "", $path);
    }

	###########################
	# Form for 'New Directory'
	###########################
  $inputfield = '<input type="hidden" name="path" value="'.$path.'">
                 <input type="hidden" name="contenido" value="'.$sess->id.'">
                 <input type="hidden" name="frame" value="1">
                 <input type="hidden" name="area" value="'.$area.'">
                 <input class="text_small" style="vertical-align:middle; width:170px;" type="text" name="foldername" onChange="document.newdir.submit();">';
  $tpl->set('s', 'ACTION', $inputfield);
  $sessURL = $sess->url("main.php?area=upl_mkdir&frame=2&appendparameters=$appendparameters");
  $tpl->set('s', 'TARGET',	'onsubmit="parent.frames[2].location.href=\''.$sess->url("main.php?area=upl&action=upl_mkdir&frame=2&appendparameters=$appendparameters").
                            '&path=\'+document.newdir.path.value+\'&foldername=\'+document.newdir.foldername.value;"');
  $tpl->set('s', 'SUBMIT',	'<input type="image" src="'.$cfg["path"]["htmlpath"].'images/submit.gif" style="vertical-align:middle;">');
  $tpl->set('s', 'CAPTION', i18n("Create directory in"));
  $tpl->set('s', 'DEBUG', '<script>console.log(document.newdir.path.value)</script>');
  $tpl->set('s', 'DISPLAY_DIR',	'block');
} 
// No permission with current rights
else 
{
  $tpl->set('s', 'CAPTION',	'');
  $tpl->set('s', 'CAPTION2',	'');
  $inputfield = '';
  $tpl->set('s', 'TARGET',	'');
  $tpl->set('s', 'SUBMIT',	'');
  $tpl->set('s', 'ACTION',	'');
  $tpl->set('s', 'DISPLAY_DIR',	'none');
}

#############
# Searching
#############
if ($searchfor != "")
{
	$items = uplSearch($searchfor);

    $tmp_mstr = 'conMultiLink(\'%s\', \'%s\', \'%s\', \'%s\')';
    $mstr = sprintf($tmp_mstr, 
      'right_bottom', 
      $sess->url("main.php?area=upl_search_results&frame=4&searchfor=$searchfor&appendparameters=$appendparameters"), 
      'right_top', 
      $sess->url("main.php?area=$area&frame=3&appendparameters=$appendparameters"));
    $refreshMenu = "\n".'if (top.content.left.left_bottom) top.content.left.left_bottom.refreshMenu()';
    $tpl->set('s', 'RESULT', $mstr.$refreshMenu);
}
else
{
  $tpl->set('s', 'RESULT', '');
}

# create javascript multilink
$tmp_mstr = '<a href="javascript:conMultiLink(\'%s\', \'%s\',\'%s\', \'%s\')">%s</a>';
$mstr = sprintf($tmp_mstr, 'right_top', $sess->url("main.php?area=$area&frame=3&path=$pathstring&appendparameters=$appendparameters"), 'right_bottom', $sess->url("main.php?area=$area&frame=4&path=$pathstring&appendparameters=$appendparameters"), '<img src="images/ordner_oben.gif" align="middle" alt="" border="0"><img align="middle" src="images/spacer.gif" width="5" border="0">'.$file);

$tpl->set('d', 'PATH', $pathstring);
$tpl->set('d', 'BGCOLOR', $bgcolor);
$tpl->set('d', 'INDENT', 3);
$tpl->set('d', 'DIRNAME', $mstr);
$tpl->set('d', 'EDITBUTTON', '');
$tpl->set('d', 'DELETEBUTTON', '<img style="margin-left: 5px;" src="images/delete_inact.gif">');
$tpl->set('d', 'COLLAPSE', '');
$tpl->next();
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['upl_left_top']);

?>