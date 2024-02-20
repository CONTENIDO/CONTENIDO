<?php

/**
 * This file contains the menu frame (overview) backend page for language management.
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

global $notification, $tmp_notification, $tpl;

$cfg = cRegistry::getConfig();
$client = cSecurity::toInteger(cRegistry::getClientId());
$perm = cRegistry::getPerm();
$frame = cRegistry::getFrame();
$area = cRegistry::getArea();
$action = cRegistry::getAction();
$action = $action ?? '';

$requestTargetClient = cSecurity::toInteger($_REQUEST['targetclient'] ?? '0');
$requestIdLang = cSecurity::toInteger($_REQUEST['idlang'] ?? '0');

if ($requestTargetClient <= 0) {
    $requestTargetClient = $client;
}

$tpl->set('s', 'TARGETCLIENT', $requestTargetClient);

$menu = new cGuiMenu('lang_overview_list');

$showLink = new cHTMLLink();
$showLink->setClass('show_item')
    ->setLink('javascript:void(0)')
    ->setAttribute('data-action', 'show_lang');

$deleteLink = new cHTMLLink();
$deleteLink = $deleteLink->setClass('con_img_button')
    ->setLink('javascript:void(0)')
    ->setAttribute('data-action', 'delete_lang')
    ->setContent(cHTMLImage::img($cfg['path']['images'] . 'delete.gif', i18n("Delete language")));

$activateLink = new cHTMLLink();
$activateLink->setClass('con_img_button')
    ->setLink('javascript:void(0)')
    ->setAttribute('data-action', 'activate_lang')
    ->setContent(cHTMLImage::img($cfg['path']['images'] . 'offline.gif', i18n("Activate language")));

$deactivateLink = new cHTMLLink();
$deactivateLink->setClass('con_img_button')
    ->setLink('javascript:void(0)')
    ->setAttribute('data-action', 'deactivate_lang')
    ->setContent(cHTMLImage::img($cfg['path']['images'] . 'online.gif', i18n("Deactivate language")));

// Notification
if ($tmp_notification) {
    $menu->setId('-1', '-1');
    $menu->setLink('-1', new cHTMLSpan());
    $menu->setTitle('-1', new cHTMLSpan($tmp_notification));
}

$clientLanguageColl = new cApiClientLanguageCollection();
$allClientLanguages = $clientLanguageColl->getAllLanguagesByClient($client);

$iLangCount = count($allClientLanguages);
foreach ($allClientLanguages as $clientLanguage) {
    $idlang = cSecurity::toInteger($clientLanguage["idlang"]);
    $LangName = '<span>' . conHtmlSpecialChars($clientLanguage["name"]) . '</span>&nbsp;(' . $idlang . ')';

    $menu->setId($idlang, $idlang);
    $menu->setLink($idlang, $showLink);
    $menu->setTitle($idlang, $LangName);

    // Activate link
    if ($clientLanguage["active"] == 0) {
        // Activate
        if ($perm->have_perm_area_action($area, "lang_activatelanguage")) {
            $link = $activateLink->render();
        } else {
            $link = cHTMLImage::img(
                $cfg['path']['images'] . 'offline.gif', i18n("Language offline"),
                ['class' => 'con_img_button_off']
            );
        }
        $menu->setActions($idlang, 'activate', $link);
    } else {
        // Deactivate
        $message = i18n("Deactivate language");
        if ($perm->have_perm_area_action($area, "lang_deactivatelanguage")) {
            $link = $deactivateLink->render();
        } else {
            $link = cHTMLImage::img(
                $cfg['path']['images'] . 'online.gif', i18n("Language online"),
                ['class' => 'con_img_button_off']
            );
        }
        $menu->setActions($idlang, 'deactivate', $link);
    }

    // Delete link
    $deleteAct = i18n("Delete language");
    if ($perm->have_perm_area_action("lang_edit", "lang_deletelanguage")) {
        $menu->setActions($idlang, 'delete', $deleteLink->render());
    }

    if ($requestIdLang === $idlang) {
        $menu->setMarked($idlang);
    }
}

$tpl->set('s', 'GENERIC_MENU', $menu->render(false));

$deleteMsg = i18n("Do you really want to delete the language %s?");
$tpl->set('s', 'DELETE_MESSAGE', $deleteMsg);
$tpl->set('s', 'LANG_COUNT', $iLangCount);

if ($action == 'lang_deactivatelanguage' || $action == 'lang_activatelanguage') {
    $sReloadScript = <<<JS
<script type="text/javascript">
(function(Con, $) {
    Con.multiLink(
        'right_bottom', Con.UtilUrl.build('main.php', {area: 'lang_edit', frame: 4, targetclient: $client, idlang: $requestIdLang})
    );
})(Con, Con.$);
</script>
JS;
} else {
    $sReloadScript = "";
}

$tpl->set('s', 'RELOAD_SCRIPT', $sReloadScript);

// Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['lang_overview']);
