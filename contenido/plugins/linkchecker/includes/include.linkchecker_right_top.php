<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * right_top frame for Linkchecker
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend plugins
 * @version    2.0.2
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release 4.8.7
 * @deprecated Was replaced by include.default_subnav.php
 * 
 * {@internal 
 *   created  2007-12-05 (based on 2003)
 *   modified 2007-12-06, Frederic Schneider, Linkchecker-Edition
 *   modified 2008-07-02, Frederic Schneider, add security fix
 *   modified 2010-05-20, Murat Purc, removed request check during processing ticket [#CON-307]
 *   modified 2010-09-07, Oliver Lohkemper, deprecated
 *
 *   $Id: include.linkchecker_right_top.php 1205 2010-09-07 10:33:42Z OliverL $:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


$nav = new Contenido_Navigation;

$sql = "SELECT idarea FROM " . $cfg['tab']['area'] . " AS a WHERE a.name = '" . Contenido_Security::escapeDB($area, $db) . "' OR a.parent_id = '" . Contenido_Security::escapeDB($area, $db) . "' ORDER BY idarea";
$db->query($sql);

$in_str = "";

while($db->next_record()) {
	$in_str .= $db->f('idarea') . ',';
}

$len = strlen($in_str)-1;
$in_str = substr($in_str, 0, $len);
$in_str = '(' . $in_str . ')';

$sql = "SELECT b.location AS location, a.name AS name FROM " . $cfg['tab']['area'] . " AS a, " . $cfg['tab']['nav_sub'] . " AS b
		WHERE b.idarea IN " . Contenido_Security::escapeDB($in_str, $db) . " AND b.idarea = a.idarea AND b.level = 1 ORDER BY b.idnavs";

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