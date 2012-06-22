<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Builds the third navigation layer
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Plugins
 * @subpackage Workflow
 * @version    1.1.1
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created  2003-05-20
 *   modified 2010-05-20, Murat Purc, removed request check during processing ticket [#CON-307]
 *   
 *   $Id$
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


	$nav = new Contenido_Navigation;

    $parentarea = getParentAreaID($area);
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
                b.idarea IN ".Contenido_Security::escapeDB($in_str, $db)." AND
                b.idarea = a.idarea AND
                b.level = 1
            ORDER BY
                b.idnavs";

    $db->query($sql);

    while ( $db->next_record() ) {

        # Extract caption from
        # the xml language file
        $caption = $nav->getName($db->f("location"));

        $tmp_area = $db->f("name");
        
        # Set template data
        $tpl->set("d", "ID",        'c_'.$tpl->dyn_cnt);
        $tpl->set("d", "CLASS",     '');
        $tpl->set("d", "OPTIONS",   '');
        $tpl->set("d", "CAPTION",   '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="'.$sess->url("main.php?area=$tmp_area&frame=4&idworkflow=$idworkflow").'">'.$caption.'</a>');
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
	if ($idworkflow <= 0)
	{
		$tpl->generate($cfg["path"]["templates"].$cfg["templates"]["subnav_blank"]);
	} else {
		$tpl->generate($cfg["path"]["templates"] . $cfg["templates"]["subnav"]);
	}


?>
