<?php
$account = getEffectiveSetting('googleanalytics', 'account', '');

if ($account != '') {
	$tpl = new Template();
	$tpl->set('s', 'account', $account);
	$tpl->generate('googleanalytics.html');
}
?>