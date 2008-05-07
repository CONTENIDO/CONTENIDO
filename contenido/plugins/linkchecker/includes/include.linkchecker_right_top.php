<?php
/******************************************************************************
Description 	: Linkchecker 2.0.1
Author      	: Frederic Schneider (4fb)
Urls        	: http://www.4fb.de
Create date 	: 2007-12-05
Modified    	: 2007-12-06
Based on    	: Jan Legowski (2003)
*******************************************************************************/

if($_REQUEST['cfg']) {
	exit;
}

$nav = new Contenido_Navigation;

$sql = "SELECT idarea FROM " . $cfg['tab']['area'] . " AS a WHERE a.name = '" . $area . "' OR a.parent_id = '" . $area . "' ORDER BY idarea";
$db->query($sql);

$in_str = "";

while($db->next_record()) {
	$in_str .= $db->f('idarea') . ',';
}

$len = strlen($in_str)-1;
$in_str = substr($in_str, 0, $len);
$in_str = '(' . $in_str . ')';

$sql = "SELECT b.location AS location, a.name AS name FROM " . $cfg['tab']['area'] . " AS a, " . $cfg['tab']['nav_sub'] . " AS b
		WHERE b.idarea IN " . $in_str . " AND b.idarea = a.idarea AND b.level = 1 ORDER BY b.idnavs";

$db->query($sql);

while($db->next_record()) {

	// Extract names from the XML document.
	$caption = $nav->getName($db->f("location"));

	$tmp_area = $db->f("name");

	# Set template data
	$tpl->set("d", "ID", 'c_' . $tpl->dyn_cnt);
	$tpl->set("d", "CLASS", '');
	$tpl->set("d", "OPTIONS", '');

	$tpl->set("d", "CAPTION", '<a onclick="sub.clicked(this)" target="right_bottom" href="' . $sess->url("main.php?area=$tmp_area&frame=4&action=linkchecker") . '">' . $caption . '</a>');
  
	$tpl->next();

}

$tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);
$tpl->set('s', 'IDCAT', 0);
$tpl->set('s', 'CLIENT', $client);
$tpl->set('s', 'LANG', $lang);
$tpl->set('s', 'SESSID', $sess->id);

# Generate the third
# navigation layer
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['con_subnav_noleft']);

?>