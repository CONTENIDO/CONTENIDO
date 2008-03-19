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

if ( isset($_GET['idcat']) ) {

	$nav = new Contenido_Navigation;
	
    $sql = "SELECT
                b.location AS location,
                a.name AS name,
                a.relevant AS relevant
            FROM
                ".$cfg["tab"]["area"]." AS a,
                ".$cfg["tab"]["nav_sub"]." AS b
            WHERE
                b.level = 1 AND
                b.idarea = a.idarea
            ORDER BY
                b.idnavs";

    $db->query($sql);
    while ( $db->next_record() ) {

      /* Extract names from the XML document. */
      $caption = $nav->getName($db->f("location"));

        $tmp_area = $db->f("name");

        if ($perm->have_perm_area_action($tmp_area) || ($db->f("relevant") == 0))
        {
        # Set template data
            $tpl->set("d", "ID",        'c_'.$tpl->dyn_cnt);
            $tpl->set("d", "CLASS",     '');
            $tpl->set("d", "OPTIONS",   '');
            $tpl->set("d", "CAPTION",   '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="'.$sess->url("main.php?area=$tmp_area&frame=4&idcat=$idcat").'">'.$caption.'</a>');
            $tpl->next();
        }
    }

    $tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);

    # Generate the third
    # navigation layer
    $tpl->generate($cfg["path"]["templates"] . $cfg["templates"]["subnav"]);

} else {

    include ($cfg["path"]["contenido"].$cfg["path"]["templates"] . $cfg["templates"]["right_top_blank"]);

}

?>
