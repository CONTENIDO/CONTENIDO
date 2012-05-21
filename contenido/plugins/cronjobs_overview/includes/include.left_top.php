<?php 

//Has the user permission for crontab_edit
if (!$perm->have_perm_area_action($area, 'crontab_edit'))
{
	$notification->displayNotification("error", i18n("Permission denied"));
	return -1;
}
		
$tpl = new Template();


$tpl->set('s', 'LABLE_CRONJOB_EDIT', i18n('Crontab bearbeiten'));
$tpl->set('s', 'ROW', 'javascript:conMultiLink(\'right_bottom\', \''.$sess->url("main.php?area=cronjob&frame=4&action=crontab_edit&file=$file").'\', \'left_bottom\',\''.$sess->url("main.php?area=cronjob&frame=2").'\');');
$tpl->generate($cfg['path']['contenido']. $cfg['path']['plugins'] . "cronjobs_overview/templates/left_top.html");

?>