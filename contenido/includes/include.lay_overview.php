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

$requestIdLay = cSecurity::toInteger($_REQUEST['idlay'] ?? '0');

$client = cSecurity::toInteger(cRegistry::getClientId());

$readOnly = (getEffectiveSetting('client', 'readonly', 'false') === 'true');

$oLayouts = new cApiLayoutCollection();
$oLayouts->select("`idclient` = " . $client, '', '`name` ASC');

$tpl->reset();

$menu = new cGuiMenu('lay_overview_list');

$showLink = new cHTMLLink();
$showLink->setClass('show_item')
    ->setLink('javascript:void(0)')
    ->setAttribute('data-action', 'show_layout');

$deleteLink = new cHTMLLink();
$deleteLink = $deleteLink->setClass('con_img_button')
    ->setLink('javascript:void(0)')
    ->setAttribute('data-action', 'delete_layout')
    ->setContent(cHTMLImage::img($cfg['path']['images'] . 'delete.gif', i18n('Delete layout')));

$inUseLink = new cHTMLLink();
$inUseLink = $inUseLink->setClass('con_img_button')
    ->setLink('javascript:void(0)')
    ->setAttribute('data-action', 'inused_layout')
    ->setContent(cHTMLImage::img($cfg['path']['images'] . 'exclamation.gif', i18n('Click for more information about usage')));

while (($layout = $oLayouts->next()) !== false) {
    $idlay = cSecurity::toInteger($layout->getId());

    if (!$perm->have_perm_area_action_item('lay_edit', 'lay_edit', $idlay)) {
        continue;
    }

    $name  = conHtmlSpecialChars(cString::stripSlashes($layout->get('name')));
    $description = conHtmlSpecialChars(nl2br($layout->get('description') ?? ''));

    if (cString::getStringLength($description) > 64) {
        $description = cString::getPartOfString($description, 0, 64) . ' ..';
    }

    $menu->setId($idlay, $idlay);
    $menu->setLink($idlay, $showLink);
    $menu->setTitle($idlay, $name);
    $menu->setTooltip($idlay, $description);

    $inUse = $layout->isInUse();
    if ($inUse) {
        $menu->setActions($idlay, 'inuse', $inUseLink->render());
    }

    // To do link
    $todo = new TODOLink('idlay', $idlay, i18n("Layout") . ': ' . $name, '');
    $menu->setActions($idlay, 'todo', $todo->render());

    // Delete link
    $hasDeletePermission = $perm->have_perm_area_action_item('lay', 'lay_delete', $idlay);
    if ($hasDeletePermission && !$inUse) {
        if ($readOnly) {
            $delTitle = i18n("This area is read only! The administrator disabled edits!");
            $deleteLinkStr = cHTMLImage::img($cfg['path']['images'] . 'delete_inact.gif', $delTitle, ['class' => 'con_img_button_off']);
        } else {
            $delTitle = i18n("Delete layout");
            $deleteLinkStr = $deleteLink->render();
        }
    } elseif ($hasDeletePermission && $inUse) {
        $delTitle = i18n("Layout is in use, cannot delete");
        $deleteLinkStr = cHTMLImage::img($cfg['path']['images'] . 'delete_inact.gif', $delTitle, ['class' => 'con_img_button_off']);
    } else {
        $delTitle = i18n("No permission");
        $deleteLinkStr = cHTMLImage::img($cfg['path']['images'] . 'delete_inact.gif', $delTitle, ['class' => 'con_img_button_off']);
    }
    $menu->setActions($idlay, 'delete', $deleteLinkStr);

    if ($requestIdLay === $idlay) {
        $menu->setMarked($idlay);
    }
}

$tpl->set('s', 'GENERIC_MENU', $menu->render(false));

$tpl->set('s', 'AREA', $area);
$tpl->set('s', 'AJAX_URL',  cRegistry::getBackendUrl() . 'ajaxmain.php');
$tpl->set('s', 'BOX_TITLE', i18n("The layout '%s' is used for following templates") . ":");
$tpl->set('s', 'DELETE_MESSAGE', i18n("Do you really want to delete the following layout:<br><br>%s<br>"));

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['lay_overview']);
