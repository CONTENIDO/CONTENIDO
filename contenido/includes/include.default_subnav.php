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
	
/*
 * Url-Params 
 * with key like 'id%' or '%id' and value are integer
 */
$sUrlParams = '';
foreach( $_GET as $sTempKey => $sTempValue ) {
	if( (substr($sTempKey, 0, 2)=='id' || substr($sTempKey, -2, 2)=='id')
	 && (int)$sTempValue!=0 ) {
		$sUrlParams.= '&'.$sTempKey.'='.$sTempValue;
	}
}



if( isset($area) ) {
	$nav = new Contenido_Navigation;
	
    $sql = "SELECT
                navsub.location AS location,
                area.name     AS name
            FROM
                ".$cfg["tab"]["area"]."    AS area,
                ".$cfg["tab"]["nav_sub"]." AS navsub
            WHERE
				area.idarea = navsub.idarea
			  AND
				navsub.level = 1
			  AND ( 
					area.idarea = '".$area."'
				OR
					area.parent_id = '".$area."' 
				)
			  AND 
			  	navsub.online = 1
            ORDER BY
                navsub.idnavs";

    $db->query($sql);

    while( $db->next_record() ) {
		/* Set translation path */
		$caption = $nav->getName( $db->f("location") );

        $tmp_area = $db->f("name");

        /* fill template */
        $tpl->set("d", "ID",        'c_'.$tpl->dyn_cnt);
        $tpl->set("d", "CAPTION",   '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="'.$sess->url("main.php?area=".$tmp_area."&frame=4&contenido=".$sess->id.$sUrlParams).'">'.$caption.'</a>');
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
