<?php

/**
 * This file contains the menu frame (overview) backend page for template management.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Jan Lengowski
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
 * @var cDb $db
 * @var array $cfg
 * @var string $area
 */

// Display critical error if client does not exist
$client = cSecurity::toInteger(cRegistry::getClientId());
if ($client < 1 || !cRegistry::getClient()->isLoaded()) {
    $oPage = new cGuiPage("tpl_overview");
    $oPage->displayCriticalError(i18n('No Client selected'));
    $oPage->render();
    return;
}

$requestIdTpl = cSecurity::toInteger($_REQUEST['idtpl'] ?? '0');

$hasCommonTplRights = null;

$templateColl = new cApiTemplateCollection();
$templateColl->select("`idclient` = " . $client, '', '`name` ASC');

$tpl->reset();

$menu = new cGuiMenu('tpl_overview_list');

$showLink = new cHTMLLink();
$showLink->setClass('show_item')
    ->setLink('javascript:void(0)')
    ->setAttribute('data-action', 'show_template');

$deleteLink = new cHTMLLink();
$deleteLink = $deleteLink->setClass('con_img_button')
    ->setLink('javascript:void(0)')
    ->setAttribute('data-action', 'delete_template')
    ->setContent(cHTMLImage::img($cfg['path']['images'] . 'delete.gif', i18n('Delete template')));

$inUseLink = new cHTMLLink();
$inUseLink = $inUseLink->setClass('con_img_button')
    ->setLink('javascript:void(0)')
    ->setAttribute('data-action', 'inused_template')
    ->setContent(cHTMLImage::img($cfg['path']['images'] . 'exclamation.gif', i18n('Click for more information about usage')));

$copyLink = new cHTMLLink();
$copyLink = $copyLink->setClass('con_img_button')
    ->setLink('javascript:void(0)')
    ->setAttribute('data-action', 'duplicate_template')
    ->setContent(cHTMLImage::img($cfg['path']['images'] . 'but_copy.gif', i18n('Duplicate template')));

while (($template = $templateColl->next()) !== false) {
    if (is_null($hasCommonTplRights)) {
        $hasCommonTplRights = (
            $perm->have_perm_area_action('tpl', 'tpl_delete') ||
            $perm->have_perm_area_action('tpl', 'tpl_duplicate') ||
            $perm->have_perm_area_action('tpl_edit', 'tpl_edit') ||
            $perm->have_perm_area_action('tpl_edit', 'tpl_new') ||
            $perm->have_perm_area_action('tpl_visual', 'tpl_visedit')
        );
    }

    $idtpl = cSecurity::toInteger($template->getId());

    if ($perm->have_perm_item($area, $idtpl) || $hasCommonTplRights) {
        $name = $template->get('name');
        $name = (cString::getStringLength(trim($name)) > 0) ? $name : i18n('-- New template --');
        $name = conHtmlSpecialChars(stripslashes($name));
        $description = conHtmlSpecialChars(stripslashes($template->get('description') ?? ''));

        $menu->setId($idtpl, $idtpl);
        $menu->setTooltip($idtpl, $description);

        if ($perm->have_perm_area_action_item('tpl_edit', 'tpl_edit', $idtpl)) {
            $menu->setLink($idtpl, $showLink);
        } else {
            $menu->setLink($idtpl, new cHTMLSpan());
        }

        if ($template->get('defaulttemplate') == 1) {
            $menu->setTitle($idtpl, '<b>' . $name . '</b>');
        } else {
            $menu->setTitle($idtpl, $name);
        }

        // Check if template is in use
        $inUse = tplIsTemplateInUse($idtpl);

        if (!$inUse && ($perm->have_perm_area_action_item('tpl', 'tpl_delete', $idtpl))) {
            $deleteLinkStr = $deleteLink->render();
            $inUseLinkStr = cHTMLImage::img($cfg['path']['images'] . 'spacer.gif', '', ['class' => 'con_img_button_off']);
        } else {
            $delTitle = i18n('Template in use, cannot delete');
            $deleteLinkStr = cHTMLImage::img($cfg['path']['images'] . 'delete_inact.gif', $delTitle, ['class' => 'con_img_button_off']);
            $inUseLinkStr = $inUseLink->render();
        }

        if ($perm->have_perm_area_action_item('tpl', 'tpl_dup', $idtpl)) {
            $copyLinkStr = $copyLink->render();
        } else {
            $copyLinkStr = cHTMLImage::img($cfg['path']['images'] . 'spacer.gif', '', ['class' => 'con_img_button_off']);
        }

        $menu->setActions($idtpl, 'inuse', $inUseLinkStr);
        $menu->setActions($idtpl, 'copy', $copyLinkStr);
        $menu->setActions($idtpl, 'delete', $deleteLinkStr);

        if ($requestIdTpl === $idtpl) {
            $menu->setMarked($idtpl);
        }
    }
}

$tpl->set('s', 'GENERIC_MENU', $menu->render(false));

// Data for show of used info per ajax
$tpl->set('s', 'AREA', $area);
$tpl->set('s', 'AJAX_URL', cRegistry::getBackendUrl() . 'ajaxmain.php');
$tpl->set('s', 'BOX_TITLE', i18n("The template '%s' is used for following categories and articles") . ':');
$tpl->set('s', 'DELETE_MESSAGE', i18n('Do you really want to delete the following template:<br><br>%s<br>'));

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['tpl_overview']);
