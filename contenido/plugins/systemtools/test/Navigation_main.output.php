<?php
/**
 * @file Navigation_main.output.php 
 * 
 * @project Germany.co.uk
 * @version	1.0.0
 * @author Marco Jahn
 * @copyright four for business AG <www.4fb.de>
 * @created 
 * @modified 31.10.2005 by Willi Man
 */
 
if ( !is_object($db2) ) {
    $db2 = new DB_Contenido;
}

$mainCatStart = getEffectiveSetting('navigation', 'category_main');

if ( catIsChildOf($idcat, $mainCatStart) ) {
    $sel_idcat = $idcat;
} else {
    $sel_idcat = $mainCatStart;
}

/**
 * Array storing alle the
 * navigation data
 */
$navitems = array();

/* Template Instance */
$tpl = new Template;

$sql = "SELECT
            A.idcat,
            C.name
        FROM
            ".$cfg["tab"]["cat_tree"]." AS A,
            ".$cfg["tab"]["cat"]." AS B,
            ".$cfg["tab"]["cat_lang"]." AS C
        WHERE
            A.idcat     = B.idcat   AND
            B.idcat     = C.idcat   AND
            B.idclient  = '$client' AND
            C.idlang    = '$lang'   AND
            C.visible   = '1'       AND
            B.parentid  = '$sel_idcat'
        ORDER BY
            A.idtree";

#print "<pre>"; print_r($sql); print "</pre>";

$db->query($sql);

while ( $db->next_record() ) {

        # Check for external redirects...
        $sql = "SELECT
                    a.external_redirect AS ext,
                    a.idartlang AS idartlang
                FROM
                    ".$cfg["tab"]["art_lang"]." AS a,
                    ".$cfg["tab"]["cat_art"]." AS b,
                    ".$cfg["tab"]["cat"]." AS c
                WHERE
                    b.idcat     = '".$db->f("idcat")."' AND
                    c.idclient  = '".$client."' AND
                    c.idcat     = b.idcat AND
                    a.idart     = b.idart AND
                    a.idlang    = '".$lang."'";

#print "<pre>"; print_r($sql); print "</pre>";

        $db2->query($sql);
        $db2->next_record();
        #if (isStartArticle($db2->f("idartlang"), $db->f("idcat"), $lang))
        #{
            $target = ( $db2->f("ext") == 0 ) ? '_self' : '_blank';
    
            $navitems[$db->f("idcat")] = array("idcat"  => $db->f("idcat"),
                                               "name"   => $db->f("name"),
                                               "target" => $target);
        #}
}

#print "<pre>navitems pre "; print_r($navitems); print "</pre>";

/* Create Navigation Array */
nav($sel_idcat);

#print "<pre>navitems post "; print_r($navitems); print "</pre>";

$navLevel1 = '<div id="nav_first">NAVCONTENT</div>';
$navLevel2 = '<div id="nav_second">NAVCONTENT</div>';

$aNavLevel1 = array();
$aNavLevel2 = array();

$bNavLevel1 = false;
$bNavLevel2 = false;

$tplListItems = '<li><a{ACTIVE} href="{HREF}" {TARGET} {ADDONS}>{NAME}</a></li>' . "\n";

// default for Home
$tpl->reset();
$tpl->set('s', 'NAME', 'Home');
$tpl->set('s', 'TARGET', 'target="_self"');
$tpl->set('s', 'HREF', $cfgClient[$client]['path']['htmlpath'] . 'index.html');
$tpl->set('s', 'ADDONS', 'title="Goto: Doorpage"');
if (!isset($idcat) || $idcat == 1) {
	$tpl->set('s', 'ACTIVE', ' class="active"');
} else {
	$tpl->set('s', 'ACTIVE', '');
}
$aNavLevel1[] = $tpl->generate($tplListItems, true, "");

$search_prevent = array('\'', '"');
$replace_prevent = array('', '');

foreach ($navitems as $key => $data) {

	$bNavLevel1 = true;
    /* 1. Navigations Ebene */
    $tpl->reset();
    $tpl->set('s', 'NAME',  $data['name']);
    $tpl->set('s', 'TARGET', 'target="'.$data['target'].'"');
    #$tpl->set('s', 'HREF',  $sess->url('front_content.php?idcat='.$data['idcat']));
	$tpl->set('s', 'HREF', cms_getUrlPath ($data['idcat'], true) . 'index.html');
	$tpl->set('s', 'ADDONS', 'title="Goto: ' . str_replace($search_prevent, $replace_prevent, $data['name']) . '"');

    if ($idcat == $data['idcat'] || is_array($data['sub'])) {
        $tpl->set('s', 'ACTIVE', ' class="active"');
    } else {
		$tpl->set('s', 'ACTIVE', '');
    }
	
	$aNavLevel1[] = $tpl->generate($tplListItems, true, "");

    if (is_array($data['sub'])) {
		
		$bNavLevel2 = true;
        foreach ($data['sub'] as $key => $data) {

            /* 2. Navigations Ebene */
            $tpl->reset();
			$tpl->set('s', 'NAME',  $data['name']);
			$tpl->set('s', 'TARGET', 'target="'.$data['target'].'"');
			#$tpl->set('s', 'HREF',  $sess->url('front_content.php?idcat='.$data['idcat']));
			$tpl->set('s', 'HREF', cms_getUrlPath ($data['idcat'], true) . 'index.html');
			$tpl->set('s', 'ADDONS', 'title="Goto: ' . str_replace($search_prevent, $replace_prevent, $data['name']) . '"');
			
			if ($idcat == $data['idcat'] || is_array($data['sub'])) {
				$tpl->set('s', 'ACTIVE', ' class="active"');
			} else {
				$tpl->set('s', 'ACTIVE', '');
			}
			
			$aNavLevel2[] = $tpl->generate($tplListItems, true, "");

        } // end foreach

    } // end if

} // end foreach

if ($bNavLevel1 === true) {
	echo str_replace('NAVCONTENT', '<ul>' . implode('', $aNavLevel1) . '</ul>', $navLevel1);
}

if ($bNavLevel2 === true) {
	echo str_replace('NAVCONTENT', '<ul>' . implode('', $aNavLevel2) . '</ul>', $navLevel2);
} else {
	echo str_replace('NAVCONTENT', '&nbsp;', $navLevel2);
}

?>