<?php
/**
 * This file contains the menu frame (overview) backend page for language management.
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

$area = 'lang';

if (!isset($action)) {
    $action = '';
}

if (!is_numeric($targetclient)) {
    $targetclient = $client;
}

$iGetIdlang = $idlang;

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

    if ($db->f("active") == 0) {
        // activate
        $message = i18n("Activate language");
        if ($perm->have_perm_area_action($area, "lang_activatelanguage")) {
            $active = "<a title=\"$message\" href=\"" . $sess->url("main.php?area=$area&action=lang_activatelanguage&frame=$frame&targetclient=$targetclient&idlang=" . $db->f("idlang")) . "#clickedhere\"><img src=\"" . $cfg["path"]["images"] . "offline.gif" . "\" border=\"0\" title=\"$message\" alt=\"$message\"></a>";
        } else {
            $active = "<img src='" . $cfg["path"]["images"] . "offline.gif' title='" . i18n("Language offline") . "'>";
        }
    } else {
        // deactivate
        $message = i18n("Deactivate language");
        if ($perm->have_perm_area_action($area, "lang_deactivatelanguage")) {
            $active = "<a title=\"$message\" class=\"action\" href=\"" . $sess->url("main.php?area=$area&action=lang_deactivatelanguage&frame=$frame&targetclient=$targetclient&idlang=" . $db->f("idlang")) . "#clickedhere\"><img src=\"" . $cfg["path"]["images"] . "online.gif" . "\" border=\"0\" title=\"$message\" alt=\"$message\"></a>";
        } else {
            $active = "<img src='" . $cfg["path"]["images"] . "online.gif' title='" . i18n("Language online") . "'>";
        }
    }

    // Delete Button
    $deleteMsg = sprintf(i18n("Do you really want to delete the language %s?"), conHtmlSpecialChars($db->f("name")));
    $deleteAct = i18n("Delete language");
    $deletebutton = '<a title="' . $deleteAct . '" href="javascript:void(0)" onclick="Con.showConfirmation(&quot;' . $deleteMsg . '&quot;, function() { deleteLang(' . $db->f('idlang') . '); });return false;"><img src="' . $cfg['path']['images'] . 'delete.gif" border="0" title="' . $deleteAct . '" alt="' . $deleteAct . '"></a>';

    $tpl->set('d', 'LANGUAGE', '<a target="right_bottom" href="' . $sess->url("main.php?area=lang_edit&idlang=$idlang&targetclient=$targetclient&frame=4") . '">' . $db->f("name") . '&nbsp;<span>(' . $idlang . ')</span></a>');
    $tpl->set('d', 'ACTIVATEBUTTON', $active);
    if ($perm->have_perm_area_action("lang_edit", "lang_deletelanguage")) {
        $tpl->set('d', 'DELETEBUTTON', $deletebutton);
    } else {
        $tpl->set("d", "DELETEBUTTON", "");
    }

    if ($iGetIdlang == $idlang) {
        $tpl->set('d', 'MARKED', ' id="marked" ');
    } else {
        $tpl->set('d', 'MARKED', '');
    }

    $tpl->next();
}

$newlanguageform = '
    <form name="newlanguage" method="post" action="' . $sess->url("main.php?area=$area&frame=$frame") . '">
        <input type="hidden" name="action" value="lang_newlanguage">
        <table cellpadding="0" cellspacing="0" border="0">
            <tr><td class="text_medium">' . i18n("New language") . ':
                <input type="text" name="name">&nbsp;&nbsp;&nbsp;
                <input type="image" src="' . $cfg['path']['images'] . 'but_ok.gif" border="0">
            </td></tr>
        </table>
    </from>
';

$tpl->set('s', 'NEWLANGUAGEFORM', $newlanguageform);

if ($tmp_notification) {
    $noti_html = '<tr><td colspan="3">' . $tmp_notification . '</td></tr>';
    $tpl->set('s', 'NOTIFICATION', $noti_html);
} else {
    $tmp_notification = $notification->returnNotification("info", i18n("Language deleted"));
    $noti_html = '<tr><td colspan="3">' . $tmp_notification . '</td></tr>';
    $tpl->set('s', 'NOTIFICATION', '');
}

$tpl->set('s', 'LANG_COUNT', $iLangCount);

if ($action == 'lang_deactivatelanguage' || $action == 'lang_activatelanguage') {
	$sReloadScript = <<<JS
<script type="text/javascript">
(function(Con, $) {
    var frame = Con.getFrame('right_bottom');
    if (frame) {
        var href = Con.UtilUrl.replaceParams(frame.location.href, {idlang: $iGetIdlang});alert(href);
        frame.location.href = href;
    }
})(Con, Con.$);
</script>
JS;
} else {
	$sReloadScript = "";
}

$tpl->set('s', 'RELOAD_SCRIPT', $sReloadScript);

// Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['lang_overview']);
