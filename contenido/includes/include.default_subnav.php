<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Builds the third navigation layer
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.0
 * @author     Oliver Lohkemper
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release 4.8.14
 * 
 * {@internal 
 *   created 2010-08-23
 *
 *   $Id: include.default_subnav.php 338 2008-06-27 09:02:23Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK'))
	die('Illegal call');

if( isset($_GET['idclient']) && (int)$_GET['idclient'] != 0 )
{

	$nav = new Contenido_Navigation;
	
    $sql = "SELECT
                b.location AS location,
                a.name     AS name
            FROM
                ".$cfg["tab"]["area"]."    AS a,
                ".$cfg["tab"]["nav_sub"]." AS b
            WHERE
				a.idarea = b.idarea
			  AND (
					a.idarea = '".$area."'
				  OR
					a.parent_id = '".$area."'
				)
			  AND
				b.level = 1
            ORDER BY
                b.idnavs";

    $db->query($sql);

    while( $db->next_record() )
	{

		/* Set translation path */
		$caption = $nav->getName( $db->f("location") );

        $tmp_area = $db->f("name");

        /* fill template */
        $tpl->set("d", "ID",        'c_'.$tpl->dyn_cnt);
        $tpl->set("d", "CAPTION",   '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="'.$sess->url("main.php?area=".$tmp_area."&frame=4&idclient=".$idclient."&contenido=".$sess->id."").'">'.$caption.'</a>');
        $tpl->next();
    }
	
    $tpl->set('s', 'SESSID', $sess->id);
	
    $tpl->generate($cfg["path"]["templates"] . $cfg['templates']['default_subnav']);

} else {
    $tpl->reset();
    $tpl->set('s', 'ACTION', '');
    $tpl->generate($cfg["path"]["templates"] . $cfg['templates']['right_top_blank']);
}
?>
