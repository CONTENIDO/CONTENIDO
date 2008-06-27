<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Custom subnavigation for the newsletters
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.0
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-27, Dominik Ziegler, add security fix
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

if (isset($_GET['idnewsjob']) && (int)$_GET['idnewsjob'] > 0)
{
    $sCaption = i18n("View");
    $tmp_area = "foo2";

    # Set template data
    $tpl->set("d", "ID",        'c_'.$tpl->dyn_cnt);
    $tpl->set("d", "CLASS",     '');
    $tpl->set("d", "OPTIONS",   '');
    $tpl->set("d", "CAPTION",   '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="'.$sess->url("main.php?area=news_jobs&frame=4&idnewsjob=$idnewsjob").'">'.$sCaption.'</a>');
    $tpl->next();

    $sCaption = i18n("Details");
    $tmp_area = "foo2";

    # Set template data
    $tpl->set("d", "ID",        'c_'.$tpl->dyn_cnt);
    $tpl->set("d", "CLASS",     '');
    $tpl->set("d", "OPTIONS",   '');
    $tpl->set("d", "CAPTION",   '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="'.$sess->url("main.php?area=news_jobs&action=news_job_details&frame=4&idnewsjob=$idnewsjob").'">'.$sCaption.'</a>');
    $tpl->next();
        
    $tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);

    # Generate the third navigation layer
    $tpl->generate($cfg["path"]["templates"] . $cfg["templates"]["subnav"]);
} else {
    include ($cfg["path"]["contenido"].$cfg["path"]["templates"] . $cfg["templates"]["right_top_blank"]);
}

?>