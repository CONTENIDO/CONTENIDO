<?php
/**
 * This is the whitelist backend page for the linkchecker plugin.
 *
 * @package Plugin
 * @subpackage Linkchecker
 * @author Frederic Schneider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$plugin_name = "linkchecker";
global $perm, $notification;

// Check permissions for whitelist_view action
if (!$perm->have_perm_area_action($plugin_name, "whitelist_view")) {
    cRegistry::addErrorMessage(i18n("No permissions"));
    $page = new cGuiPage('generic_page');
    $page->abortRendering();
    $page->render();
    exit();
}

$backendUrl = cRegistry::getBackendUrl();

// Template-definition
/* Whitelist: Delete */
if (!empty($_GET['url_to_delete'])) {
    $sql = "DELETE FROM " . $cfg['tab']['whitelist'] . " WHERE url = '" . $db->escape(base64_decode($_GET['url_to_delete'])) . "'";
    $db->query($sql);
}

// Get whitelist
$sql = "SELECT url, lastview
        FROM " . $cfg['tab']['whitelist'] . "
        WHERE lastview < " . (time() + $iWhitelistTimeout) . "
        AND lastview > " . (time() - $iWhitelistTimeout) . "
        ORDER BY lastview DESC";
$db->query($sql);

while ($db->nextRecord()) {
    $tpl2 = new cTemplate();
    $tpl2->reset();

    $tpl2->set('s', 'URL', $db->f("url"));
    $tpl2->set('s', 'URL_ENCODE', base64_encode($db->f("url")));
    $tpl2->set('s', 'ENTRY', strftime(i18n('%Y-%m-%d, %I:%M%S %p', $plugin_name), $db->f("lastview")));

    $aWhitelist .= $tpl2->generate($cfg['templates']['linkchecker_whitelist_urls'], 1);
}

// Template- and languagevars
$tpl->set('s', 'HEADLINE', i18n("Links at whitelist", $plugin_name));
$tpl->set('s', 'HEADLINE_DELETE', i18n("Delete", $plugin_name));
$tpl->set('s', 'HEADLINE_ENTRY', i18n("Entry", $plugin_name));
$tpl->set('s', 'HEADLINE_URLS', i18n("URLs", $plugin_name));
$tpl->set('s', 'HELP', i18n("This links are on the whitelist. Whitelist-links won't be check at linkchecker.", $plugin_name));
$tpl->set('s', 'TITLE', "Whitelist");
$tpl->set('s', 'WHITELIST', $aWhitelist);
$tpl->set('s', 'WHITELIST_COUNT', $db->numRows());

$tpl->generate($cfg['templates']['linkchecker_whitelist']);
?>