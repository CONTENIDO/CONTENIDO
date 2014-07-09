<?php
/**
 * This file contains the menu frame (overview) backend page for layout management.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Olaf Niemann
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$oLayouts = new cApiLayoutCollection();
$oLayouts->select("idclient = " . (int) $client, '', 'name ASC');

$tpl->reset();

$requestIdlay = (isset($_REQUEST['idlay'])) ? (int) $_REQUEST['idlay'] : 0;

$darkrow = false;
while (($layout = $oLayouts->next()) !== false) {

    if (!$perm->have_perm_area_action_item('lay_edit', 'lay_edit', $layout->get('idlay'))) {
        continue;
    }

    $name  = conHtmlSpecialChars(cString::stripSlashes($layout->get('name')));
    $descr = conHtmlSpecialChars(nl2br($layout->get('description')));
    $idlay = $layout->get('idlay');

    if (strlen($descr) > 64) {
        $descr = substr($descr, 0, 64) . ' ..';
    }

    $tmp_mstr = '<a href="javascript:Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>';
    $area = 'lay';
    $mstr = sprintf(
        $tmp_mstr,
        'right_top', $sess->url("main.php?area=$area&frame=3&idlay=$idlay"),
        'right_bottom', $sess->url("main.php?area=lay_edit&frame=4&idlay=$idlay"),
        $name
    );

    $tpl->set("d", "DESCRIPTION", ($descr == "") ? '' : $descr);
    $tpl->set('d', 'NAME', $mstr);

    $oLay = new cApiLayout($idlay);
    $inUse = $oLay->isInUse($idlay);

    if (!$perm->have_perm_area_action_item('lay', 'lay_delete', $idlay) ||
        !$perm->have_perm_area_action('lay', 'lay_delete')) {
        $delDescr = i18n("No permission");
    }

    if ($inUse) {
        $delDescr   = i18n("Layout is in use, cannot delete");
        $inUseDescr = i18n("Click for more information about usage");
        $inUseLink = '<a href="javascript:;" rel="' . $idlay . '" class="in_used_lay">'
                   . '<img class="vAlignMiddle" src="'.$cfg['path']['images'].'exclamation.gif" border="0" title="'.$inUseDescr.'" alt="'.$inUseDescr.'"></a>';
        $tpl->set('d', 'INUSE', $inUseLink);
    } else {
        $tpl->set('d', 'INUSE', '');
    }

    if ($perm->have_perm_area_action_item('lay', 'lay_delete', $idlay) && !$inUse) {
        $delTitle = i18n("Delete layout");
        $delDescr = sprintf(i18n("Do you really want to delete the following layout:<br><br>%s<br>"), conHtmlentities(conHtmlSpecialChars($name)));
        if(getEffectiveSetting('client', 'readonly', 'false') == 'true') {
        	$delLink  = '<img class="vAlignMiddle" src="'.$cfg['path']['images'].'delete_inact.gif" border="0" title="'.i18n('This area is read only! The administrator disabled edits!').'" alt="'.i18n('This area is read only! The administrator disabled edits!').'">';
        } else {
        	$delLink  = '<a title="'.$delTitle.'" href="javascript://" onclick="Con.showConfirmation(&quot;' . $delDescr . '&quot;, function() { deleteLayout(' . $idlay . '); });return false;">'
                      . '<img class="vAlignMiddle" src="'.$cfg['path']['images'].'delete.gif" border="0" title="'.$delTitle.'" alt="'.$delTitle.'"></a>';
        }
        $tpl->set('d', 'DELETE', $delLink);
    } else {
        $tpl->set('d', 'DELETE', '<img class="vAlignMiddle" src="'.$cfg['path']['images'].'delete_inact.gif" border="0" title="'.$delDescr.'" alt="'.$delDescr.'">');
    }

    $todo = new TODOLink('idlay', $idlay, i18n("Layout") . ': ' . $name, '');

    $tpl->set('d', 'TODO', $todo->render());

    $marked = ($requestIdlay == $idlay) ? 'marked' : '';
    $tpl->set('d', 'ID', $marked);

    $tpl->next();
}

//datas for show of used info per ajax
$tpl->set('s', 'AREA', $area);
$tpl->set('s', 'AJAXURL',  cRegistry::getBackendUrl() . 'ajaxmain.php');
$tpl->set('s', 'BOX_TITLE', i18n("The layout '%s' is used for following templates") . ":");

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['lay_overview']);
