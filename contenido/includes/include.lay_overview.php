<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * List layouts in database
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
 *   created 2003-03-27
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2010-08-18, Munkh-Ulzii Balidar, add a functionality to show the used info
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


$oLayouts = new cApiLayoutCollection();
$oLayouts->select("idclient = '$client'", '', 'name ASC');

$tpl->reset();

$tpl->set('s', 'SID', $sess->id);

$darkrow = false;
while ($layout = $oLayouts->next()) {

    if (!$perm->have_perm_area_action_item('lay_edit', 'lay_edit', $layout->get('idlay'))) {
        continue;
    }

    $name  = $layout->get('name');
    $descr = $layout->get('description');
    $idlay = $layout->get('idlay');

    if (strlen($descr) > 64) {
        $descr = substr($descr, 0, 64);
        $descr .= ' ..';
    }

    $tmp_mstr = '<a href="javascript:conMultiLink(\'%s\', \'%s\', \'%s\', \'%s\')" title="%s" alt="%s">%s</a>';
    $area = 'lay';
    $mstr = sprintf($tmp_mstr, 'right_top',
                               $sess->url("main.php?area=$area&frame=3&idlay=$idlay"),
                               'right_bottom',
                               $sess->url("main.php?area=lay_edit&frame=4&idlay=$idlay"),
                               $descr, $descr, $name);

    $tpl->set('d', 'NAME', $mstr);

    $inUse = $classlayout->layoutInUse($idlay);

    $bgColor = ($darkrow) ? $cfg['color']['table_dark'] : $cfg['color']['table_light'];
    $darkrow = !$darkrow;
    $tpl->set('d', 'BGCOLOR', $bgColor);

    if ((!$perm->have_perm_area_action_item('lay', 'lay_delete', $idlay)) && 
        (!$perm->have_perm_area_action('lay', 'lay_delete'))) {
        $delDescr = i18n("No permission");
    }

    if ($inUse) {
        $delDescr   = i18n("Layout is in use, cannot delete");
        $inUseDescr = i18n("Click for more information about usage");
        $inUseLink = '<a href="javascript:;" rel="' . $idlay . '" class="in_used_lay">'
                   . '<img src="'.$cfg['path']['images'].'exclamation.gif" border="0" title="'.$inUseDescr.'" alt="'.$inUseDescr.'"></a>';
        $tpl->set('d', 'INUSE', $inUseLink);
    } else {
        $tpl->set('d', 'INUSE', '');
    }

    if ($perm->have_perm_area_action_item('lay', 'lay_delete', $idlay) && !$inUse) {
        $delTitle = i18n("Delete layout");
        $delDescr = sprintf(i18n("Do you really want to delete the following layout:<br><br>%s<br>"), htmlspecialchars($name));
        $delLink  = '<a title="'.$delTitle.'" href="javascript://" onclick="box.confirm(\''.$delTitle.'\', \''.$delDescr.'\', \'deleteLayout('.$idlay.')\')">'
                  . '<img src="'.$cfg['path']['images'].'delete.gif" border="0" title="'.$delTitle.'" alt="'.$delTitle.'"></a>';
        $tpl->set('d', 'DELETE', $delLink);
    } else {
        $tpl->set('d', 'DELETE','<img src="'.$cfg['path']['images'].'delete_inact.gif" border="0" title="'.$delDescr.'" alt="'.$delDescr.'">');
    }

    $todo = new TODOLink('idlay', $idlay, i18n("Layout") . ': ' . $name, '');

    $tpl->set('d', 'TODO', $todo->render());

    if (stripslashes($_REQUEST['idlay']) == $idlay) {
        $tpl->set('d', 'ID', 'marked');
    } else {
        $tpl->set('d', 'ID', '');
    }

    $tpl->next();
}

//datas for show of used info per ajax
$tpl->set('s', 'AREA', $area);
$tpl->set('s', 'SESSION', $contenido);
$tpl->set('s', 'AJAXURL', $cfg['path']['contenido_fullhtml'].'ajaxmain.php');
$tpl->set('s', 'BOX_TITLE', i18n("The layout '%s' is used for following templates") . ":");

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['lay_overview']);
?>