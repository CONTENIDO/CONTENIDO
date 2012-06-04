<?php
$account = getEffectiveSetting('stats', 'ga_account', '');

if ($account != '') {
	$tpl = new Template();
	$tpl->set('s', 'account', $account);
	$tpl->generate('googleanalytics.html');
}
?>