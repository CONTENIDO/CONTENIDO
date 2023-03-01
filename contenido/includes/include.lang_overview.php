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

global $notification, $tmp_notification, $targetclient, $idlang, $tpl;

$cfg = cRegistry::getConfig();
$client = cRegistry::getClientId();
$db = cRegistry::getDb();
$perm = cRegistry::getPerm();
$frame = cRegistry::getFrame();
$sess = cRegistry::getSession();


$area = 'lang';

if (!isset($action)) {
    $action = '';
}

if (!is_numeric($targetclient)) {
    $targetclient = $client;
}

$iGetIdlang = $idlang;
$clientId = cRegistry::getClientId();

$sql = "SELECT *
        FROM " . $cfg["tab"]["lang"] . " AS A, " . $cfg["tab"]["clients_lang"] . " AS B
        WHERE A.idlang = B.idlang AND B.idclient = " . cSecurity::toInteger($targetclient) . "
        ORDER BY A.idlang";

$db->query($sql);

$tpl->set('s', 'TARGETCLIENT', $targetclient);

$iLangCount = 0;
while ($db->nextRecord()) {
    $iLangCount++;

    $idlang = $db->f("idlang");

    // Show link
    $showLink = '<a class="show_item" href="javascript:void(0)" data-action="show_lang"><span>' . conHtmlSpecialChars($db->f("name")) . '</span>&nbsp;(' . $idlang . ')</a>';
    $tpl->set('d', 'LANGUAGE', $showLink);

    // Activate link
    if ($db->f("active") == 0) {
        // activate
        $message = i18n("Activate language");
        if ($perm->have_perm_area_action($area, "lang_activatelanguage")) {
            $activeLink = '<a class="con_img_button" href="javascript:void(0)" data-action="activate_lang" title="' . $message . '">'
                        . cHTMLImage::img($cfg['path']['images'] . 'offline.gif', $message)
                        . '</a>';
        } else {
            $activeLink = cHTMLImage::img($cfg['path']['images'] . 'offline.gif', i18n("Language offline"), ['class' => 'con_img_button_off']);
        }
    } else {
        // deactivate
        $message = i18n("Deactivate language");
        if ($perm->have_perm_area_action($area, "lang_deactivatelanguage")) {
            $activeLink = '<a class="con_img_button" href="javascript:void(0)" data-action="deactivate_lang" title="' . $message . '">'
                . cHTMLImage::img($cfg['path']['images'] . 'online.gif', $message)
                . '</a>';
        } else {
            $activeLink = cHTMLImage::img($cfg['path']['images'] . 'online.gif', i18n("Language online"), ['class' => 'con_img_button_off']);
        }
    }

    // Delete link
    $deleteMsg = sprintf(i18n("Do you really want to delete the language %s?"), conHtmlSpecialChars($db->f("name")));
    $deleteAct = i18n("Delete language");
    if ($perm->have_perm_area_action("lang_edit", "lang_deletelanguage")) {
        $deleteLink = '<a class="con_img_button" href="javascript:void(0)" data-action="delete_lang" title="' . $deleteAct . '">'
                    . cHTMLImage::img($cfg['path']['images'] . 'delete.gif', $deleteAct)
                    . '</a>';
    } else {
        $deleteLink = '';
    }
    $tpl->set("d", "ACTIONS", $activeLink . ' ' . $deleteLink);

    if ($iGetIdlang == $idlang) {
        $tpl->set('d', 'MARKED', ' id="marked" data-id="' . $idlang . '"');
    } else {
        $tpl->set('d', 'MARKED', ' data-id="' . $idlang . '"');
    }

    $tpl->next();
}

$deleteMsg = i18n("Do you really want to delete the language %s?");
$tpl->set('s', 'DELETE_MESSAGE', $deleteMsg);

$newlanguageform = '
    <form name="newlanguage" method="post" action="' . $sess->url("main.php?area=$area&frame=$frame") . '">
        <input type="hidden" name="action" value="lang_newlanguage">
        <table cellpadding="0" cellspacing="0" border="0">
            <tr><td class="text_medium">' . i18n("New language") . ':
                <input type="text" name="name">&nbsp;&nbsp;&nbsp;
                <input type="image" src="' . $cfg['path']['images'] . 'but_ok.gif">
            </td></tr>
        </table>
    </from>
';

$tpl->set('s', 'NEWLANGUAGEFORM', $newlanguageform);

if ($tmp_notification) {
    $noti_html = '<tr><td colspan="3">' . $tmp_notification . '</td></tr>';
    $tpl->set('s', 'NOTIFICATION', $noti_html);
} else {
    $tmp_notification = $notification->returnNotification("ok", i18n("Language deleted"));
    $noti_html = '<tr><td colspan="3">' . $tmp_notification . '</td></tr>';
    $tpl->set('s', 'NOTIFICATION', '');
}

$tpl->set('s', 'LANG_COUNT', $iLangCount);

if ($action == 'lang_deactivatelanguage' || $action == 'lang_activatelanguage') {
    $sReloadScript = <<<JS
<script type="text/javascript">
(function(Con, $) {
    Con.multiLink(
        'right_bottom', Con.UtilUrl.build('main.php', {area: 'lang_edit', frame: 4, targetclient: $clientId, idlang: $iGetIdlang})
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
