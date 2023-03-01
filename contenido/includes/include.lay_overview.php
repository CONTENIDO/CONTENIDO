<?php

/**
 * This file contains the menu frame (overview) backend page for layout management.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Olaf Niemann
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var cPermission $perm
 * @var cSession $sess
 * @var cTemplate $tpl
 * @var array $cfg
 * @var string $area
 */

global $lay;

$oClient = cRegistry::getClient();

// Display critical error if client does not exist
if (!$oClient->isLoaded()) {
    $oPage = new cGuiPage("lay_new");
    $oPage->displayCriticalError(i18n('No Client selected'));
    $oPage->render();
    return;
}

$client = cSecurity::toInteger(cRegistry::getClientId());

$oLayouts = new cApiLayoutCollection();
$oLayouts->select("idclient = " . $client, '', 'name ASC');

$tpl->reset();

$requestIdLay = cSecurity::toInteger($_REQUEST['idlay'] ?? '0');

while (($layout = $oLayouts->next()) !== false) {
    if (!$perm->have_perm_area_action_item('lay_edit', 'lay_edit', $layout->get('idlay'))) {
        continue;
    }

    $name  = conHtmlSpecialChars(cString::stripSlashes($layout->get('name')));
    $descr = conHtmlSpecialChars(nl2br($layout->get('description') ?? ''));
    $idlay = $layout->get('idlay');

    if (cString::getStringLength($descr) > 64) {
        $descr = cString::getPartOfString($descr, 0, 64) . ' ..';
    }

    $marked = ($requestIdLay == $idlay) ? 'marked' : 'lay' . $tpl->dyn_cnt;
    $tpl->set('d', 'ID', $marked);
    $tpl->set('d', 'DATA_ID', $idlay);
    $tpl->set('d', 'DESCRIPTION', ($descr == '') ? '' : $descr);
    $tpl->set('d', 'NAME', '<a class="show_item" href="javascript:void(0)" data-action="show_layout">' . $name . '</a>');

    $inUse = $layout->isInUse();
    $hasDeletePermission = $perm->have_perm_area_action_item('lay', 'lay_delete', $idlay);

    // In use link
    if ($inUse) {
        $inUseDescr = i18n("Click for more information about usage");
        $inUseLink = '<a class="con_img_button" href="javascript:void(0)" title="'.$inUseDescr.'" data-action="inused_layout">'
                    . cHTMLImage::img($cfg['path']['images'].'exclamation.gif', $inUseDescr)
                    . '</a>';
    } else {
        $inUseLink = '';
    }
    $tpl->set('d', 'INUSE', $inUseLink);

    // Delete link
    if ($hasDeletePermission && !$inUse) {
        if (getEffectiveSetting('client', 'readonly', 'false') == 'true') {
            $delTitle = i18n("This area is read only! The administrator disabled edits!");
            $delLink = cHTMLImage::img($cfg['path']['images'].'delete_inact.gif', $delTitle, ['class' => 'con_img_button_off']);
        } else {
            $delTitle = i18n("Delete layout");
            $delLink  = '<a class="con_img_button" href="javascript:void(0)" data-action="delete_layout" title="'.$delTitle.'">'
                      . cHTMLImage::img($cfg['path']['images'].'delete.gif', $delTitle)
                      . '</a>';
        }
    } elseif ($hasDeletePermission && $inUse) {
        $delTitle = i18n("Layout is in use, cannot delete");
        $delLink = cHTMLImage::img($cfg['path']['images'].'delete_inact.gif', $delTitle, ['class' => 'con_img_button_off']);
    } else {
        $delTitle = i18n("No permission");
        $delLink = cHTMLImage::img($cfg['path']['images'].'delete_inact.gif', $delTitle, ['class' => 'con_img_button_off']);
    }
    $tpl->set('d', 'DELETE', $delLink);

    // To do link
    $todo = new TODOLink('idlay', $idlay, i18n("Layout") . ': ' . $name, '');
    $tpl->set('d', 'TODO', $todo->render());

    $tpl->next();
}

$tpl->set('s', 'AREA', $area);
$tpl->set('s', 'AJAX_URL',  cRegistry::getBackendUrl() . 'ajaxmain.php');
$tpl->set('s', 'BOX_TITLE', i18n("The layout '%s' is used for following templates") . ":");
$tpl->set('s', 'DELETE_MESSAGE', i18n("Do you really want to delete the following layout:<br><br>%s<br>"));

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['lay_overview']);
