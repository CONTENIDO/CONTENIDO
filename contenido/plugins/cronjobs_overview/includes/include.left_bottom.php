<?php 
//Has the user permission for view the cronjobs
if (!$perm->have_perm_area_action($area, 'cronjob_overview'))
{
	$notification->displayNotification("error", i18n("Permission denied"));
	return -1;
}

include_once (dirname(__FILE__).'/config.plugin.php');

$tpl = new Template();
$contenidoVars = array('cfg'=>$cfg);
$cronjobs = new Cronjobs($contenidoVars);

//include ($cfg['path']['contenido'].$cfg['path']['templates'].'template.left_top_blank.html');
foreach($cronjobs->getAllCronjobs() as $row) {
	
	$tpl->set('d','FILE', $row);
	$file = urlencode($row);
	$tpl->set('d', 'ROW', 'javascript:conMultiLink(\'right_bottom\', \''.$sess->url("main.php?area=cronjob&frame=4&action=cronjob_overview&file=$file").'\');');
	$tpl->next();
	
}

$tpl->generate($dir_plugin.'templates/left_bottom.html');


?>