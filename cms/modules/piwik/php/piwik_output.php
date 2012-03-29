<?php 
$url	= getEffectiveSetting('piwik', 'url', '');
$site	= getEffectiveSetting('piwik', 'site', '');

if ($url != '' && $site != '') {
	$tpl = new Template();

	$tpl->set('s', 'url', $url);
	$tpl->set('s', 'site', $site);
	$tpl->generate('piwik.html');
}
?>