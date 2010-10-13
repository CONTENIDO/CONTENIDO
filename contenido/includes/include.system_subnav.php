<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Administration/System/Subnav
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.6.0
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * @deprecated Was replaced by include.default_subnav.php
 * 
 * {@internal 
 *   created 2003-08-15
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2010-09-07, Oliver Lohkemper, deprecated
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

$nav = new Contenido_Navigation;

$parentarea = $perm->getParentAreaID($area);
$sql = "SELECT
            idarea
        FROM
            ".$cfg["tab"]["area"]." AS a
        WHERE
            a.name = '".Contenido_Security::escapeDB($parentarea, $db)."' OR
            a.parent_id = '".Contenido_Security::escapeDB($parentarea, $db)."'
        ORDER BY
            idarea";

$db->query($sql);

$in_str = "";

while ( $db->next_record() ) {
    $in_str .= $db->f('idarea') . ',';
}

$len = strlen($in_str)-1;
$in_str = substr($in_str, 0, $len);
$in_str = '('.$in_str.')';

$sql = "SELECT
            b.location AS location,
            a.name AS name
        FROM
            ".$cfg["tab"]["area"]." AS a,
            ".$cfg["tab"]["nav_sub"]." AS b
        WHERE
            b.idarea IN ".$in_str." AND
            b.idarea = a.idarea AND
            b.level = 1 AND 
			b.online = 1
        ORDER BY
            b.idnavs";

$db->query($sql);

while ( $db->next_record() ) {

	/* Extract names from the XML document. */
	$caption = $nav->getName($db->f("location"));

    $tmp_area = $db->f("name");

        
    # Set template data
    $tpl->set("d", "ID",        'c_'.$tpl->dyn_cnt);
    $tpl->set("d", "CLASS",     '');
    $tpl->set("d", "OPTIONS",   '');
    $tpl->set("d", "CAPTION",   '<a class="black" onclick="sub.clicked(this)" target="right_bottom" href="'.$sess->url("main.php?area=$tmp_area&frame=4&idcat=$idcat").'">'.$caption.'</a>');
        
    if ($area == $tmp_area)
    {
        $tpl->set('s', 'DEFAULT', markSubMenuItem($tpl->dyn_cnt,true));
    }
    $tpl->next();

}

$tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);

$tpl->set('s', 'IDCAT', $idcat);
$tpl->set('s', 'SESSID', $sess->id);
$tpl->set('s', 'CLIENT', $client);
$tpl->set('s', 'LANG', $lang);
    

# Generate the third
# navigation layer

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['system_subnav']);
?>