<?php
/*****************************************
* File      :   $RCSfile: include.mycontenido_subnav.php,v $
* Project   :   Contenido
* Descr     :   MyContenido Subnavigation
* Modified  :   $Date: 2005/02/07 19:16:18 $
*
* © four for business AG, www.4fb.de
*
* $Id: include.mycontenido_subnav.php,v 1.11 2005/02/07 19:16:18 timo.hummel Exp $
******************************************/


	$nav = new Contenido_Navigation;

    $parentarea = $perm->getParentAreaID($area);
    $sql = "SELECT
                idarea
            FROM
                ".$cfg["tab"]["area"]." AS a
            WHERE
                a.name = '".$parentarea."' OR
                a.parent_id = '".$parentarea."'
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

    while ( $db->next_record() ) 
    {
       	/* Extract names from the XML document. */
		$caption = $nav->getName($db->f("location"));

        $tmp_area = $db->f("name");        
        # Set template data
        $tpl->set("d", "ID",        'c_'.$tpl->dyn_cnt);
        $tpl->set("d", "CLASS",     '');
        $tpl->set("d", "OPTIONS",   'nowrap');
        if ($cfg['help'] == true)
		{
        	$tpl->set("d", "CAPTION",   '<a class="black" onclick="'.setHelpContext($tmp_area).'sub.clicked(this)" target="right_bottom" href="'.$sess->url("main.php?area=$tmp_area&frame=4&idcat=$idcat").'">'.$caption.'uu</a>');
		} else {
			$tpl->set("d", "CAPTION",   '<a class="black" onclick="sub.clicked(this)" target="right_bottom" href="'.$sess->url("main.php?area=$tmp_area&frame=4&idcat=$idcat").'">'.$caption.'</a>');
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
    $tpl->generate($cfg["path"]["templates"] . $cfg["templates"]["mycontenido_subnav"]);


?>
