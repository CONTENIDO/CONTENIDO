<?php
/******************************************************************************
Description 	: Linkchecker 2.0.1
Author      	: Frederic Schneider (4fb)
Urls        	: http://www.4fb.de
Create date 	: 2007-11-02
Modified    	: 2007-12-13
*******************************************************************************/

$plugin_name = "linkchecker";
$whitelist_timeout = 2592000; // 30 days
global $perm;

if(!$perm->have_perm_area_action($plugin_name, $plugin_name)) {
	exit;
}

// Template-definition
$tpl->set('s', 'CONTENIDO_URL', $cfg['path']['contenido_fullhtml']);
$tpl->set('s', 'SID', $sess->id);

/* Whitelist: Delete */
if(!empty($_GET['url_to_delete'])) {
	$sql = "DELETE FROM " . $cfg['tab']['whitelist'] . " WHERE url = '" . base64_decode($_GET['url_to_delete']) . "'";
	$db->query($sql);
}

// Get whitelist
$sql = "SELECT url, lastview FROM " . $cfg['tab']['whitelist'] . " WHERE lastview < " . (time() + $whitelist_timeout) . "
		AND lastview > " . (time() - $whitelist_timeout) . " ORDER BY lastview DESC";
$db->query($sql);

$x = 0;
while($db->next_record()) {

	$tpl2 = new Template;
	$tpl2->reset();
    
	$tpl2->set('s', 'CONTENIDO_URL', $cfg['path']['contenido_fullhtml']);
	$tpl2->set('s', 'SID', $sess->id);
	$tpl2->set('s', 'URL', $db->f("url"));
	$tpl2->set('s', 'URL_ENCODE', base64_encode($db->f("url")));
	$tpl2->set('s', 'ENTRY', strftime(i18n('%Y-%m-%d, %I:%M%S %p', $plugin_name), $db->f("lastview")));

	$whitelist .= $tpl2->generate($cfg['templates']['linkchecker_whitelist_urls'], 1);
	$x++;
    
}

// Template- and languagevars
$tpl->set('s', 'HEADLINE', i18n("Links at whitelist", $plugin_name));
$tpl->set('s', 'HEADLINE_DELETE', i18n("Delete", $plugin_name));
$tpl->set('s', 'HEADLINE_ENTRY', i18n("Entry", $plugin_name));
$tpl->set('s', 'HEADLINE_URLS', i18n("URLs", $plugin_name));
$tpl->set('s', 'HELP', i18n("This links are on the whitelist. Whitelist-links won't be check at linkchecker.", $plugin_name));
$tpl->set('s', 'TITLE', "Whitelist");
$tpl->set('s', 'WHITELIST', $whitelist);
$tpl->set('s', 'WHITELIST_COUNT', $x);

$tpl->generate($cfg['templates']['linkchecker_whitelist']);
?>