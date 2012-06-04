<?php 
$url	= getEffectiveSetting('stats', 'piwik_url', '');
$site	= getEffectiveSetting('stats', 'piwik_site', '');

if ($url != '' && $site != '') {
	$tpl = new Template();

	$tpl->set('s', 'url', $url);
	$tpl->set('s', 'site', $site);
	$tpl->generate('piwik.html');
}
?>