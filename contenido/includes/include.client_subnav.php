<?php

/******************************************
* File      :   include.subnav.php
* Project   :   Contenido
* Descr     :   Builds the third navigation
*               layer
*
* Author    :   Jan Lengowski
* Created   :   25.01.2003
* Modified  :   25.01.2003
*
* © four for business AG
******************************************/
if ( $_REQUEST['cfg'] ) { exit; }

if ( isset($_GET['idclient']) && ($_GET['idclient'] != 0)) {

	$nav = new Contenido_Navigation;
	
    $sql = "SELECT
                idarea
            FROM
                ".$cfg["tab"]["area"]." AS a
            WHERE
                a.name = '".$area."' OR
                a.parent_id = '".$area."'
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
                b.level = 1
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
        $tpl->set("d", "CAPTION",   '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="'.$sess->url("main.php?area=$tmp_area&frame=4&idclient=$idclient&contenido=".$sess->id."").'">'.$caption.'</a>');
        //$tpl->set("d", "CAPTION",   '<a class="white" href="javascript://" onclick="sub.clicked(this);artObj.doAction(\''.$tmp_area.'\')">'.$caption.'</a>');
        $tpl->next();

    }

    $tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);

    $tpl->set('s', 'IDCAT', $idcat);
    $tpl->set('s', 'SESSID', $sess->id);
    $tpl->set('s', 'CLIENT', $client);
    $tpl->set('s', 'LANG', $lang);

    # Generate the third
    # navigation layer
    $tpl->generate($cfg["path"]["templates"] . $cfg['templates']['client_subnav']);

} else {

    include ($cfg["path"]["contenido"].$cfg["path"]["templates"] . $cfg["templates"]["right_top_blank"]);

}

?>
