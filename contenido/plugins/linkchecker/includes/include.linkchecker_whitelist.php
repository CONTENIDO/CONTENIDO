<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Whitelist for the Linkchecker
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend plugins
 * @version    2.0.1
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release 4.8.7
 * 
 * {@internal 
 *   created 2007-11-02
 *   modified 2007-12-13, 2008-05-09, 2008-05-15, Frederic Schneider
 *   modified 2008-06-02, Frederic Schneider, add security fix
 *
 *   $Id: include.linkchecker_whitelist.php 483 2008-07-02 10:22:50Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

$plugin_name = "linkchecker";
$iWhitelist_timeout = 2592000; // 30 days
global $perm;

if(!$perm->have_perm_area_action($plugin_name, $plugin_name)) {
	exit;
}

// Template-definition
$tpl->set('s', 'CONTENIDO_URL', $cfg['path']['contenido_fullhtml']);
$tpl->set('s', 'SID', $sess->id);

/* Whitelist: Delete */
if(!empty($_GET['url_to_delete'])) {
	$sql = "DELETE FROM " . $cfg['tab']['whitelist'] . " WHERE url = '" . Contenido_Security::escapeDB(base64_decode($_GET['url_to_delete']), $db) . "'";
	$db->query($sql);
}

// Get whitelist
$sql = "SELECT url, lastview FROM " . $cfg['tab']['whitelist'] . " WHERE lastview < " . (time() + $iWhitelist_timeout) . "
		AND lastview > " . (time() - $iWhitelist_timeout) . " ORDER BY lastview DESC";
$db->query($sql);

while($db->next_record()) {

	$tpl2 = new Template;
	$tpl2->reset();
    
	$tpl2->set('s', 'CONTENIDO_URL', $cfg['path']['contenido_fullhtml']);
	$tpl2->set('s', 'SID', $sess->id);
	$tpl2->set('s', 'URL', $db->f("url"));
	$tpl2->set('s', 'URL_ENCODE', base64_encode($db->f("url")));
	$tpl2->set('s', 'ENTRY', strftime(i18n("%Y-%m-%d, %I:%M%S %p", $plugin_name), $db->f("lastview")));

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
$tpl->set('s', 'WHITELIST_COUNT', $db->num_rows());

$tpl->generate($cfg['templates']['linkchecker_whitelist']);
?>