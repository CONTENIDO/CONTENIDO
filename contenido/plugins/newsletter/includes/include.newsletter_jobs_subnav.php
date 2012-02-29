<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Custom subnavigation for the newsletters
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Plugins
 * @subpackage Newsletter
 * @version    1.0.3
 * @author     Björn Behrens (HerrB)
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created  2007-01-01, Björn Behrens (HerrB)
 *   modified 2008-06-27, Dominik Ziegler, add security fix
 *   modified 2010-05-20, Murat Purc, removed request check during processing ticket [#CON-307]
 *
 *   $Id: include.newsletter_jobs_subnav.php 1702 2011-11-14 23:34:42Z xmurrix $:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


if (isset($_GET['idnewsjob']) && (int) $_GET['idnewsjob'] > 0) {
    $anchorTpl = '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="%s">%s</a>';
    $idnewsjob = (int) $_GET['idnewsjob'];

    // Set template data
    $tpl->set('d', 'ID',      'c_'.$tpl->dyn_cnt);
    $tpl->set('d', 'CLASS',   '');
    $tpl->set('d', 'OPTIONS', '');
    $tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sess->url("main.php?area=news_jobs&frame=4&idnewsjob=$idnewsjob"), i18n("View", 'newsletter')));
    $tpl->next();

    // Set template data
    $tpl->set('d', 'ID',      'c_'.$tpl->dyn_cnt);
    $tpl->set('d', 'CLASS',   '');
    $tpl->set('d', 'OPTIONS', '');
    $tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sess->url("main.php?area=news_jobs&action=news_job_details&frame=4&idnewsjob=$idnewsjob"), i18n("Details", 'newsletter')));
    $tpl->next();

    $tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);

    // Generate the third navigation layer
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['subnav']);
} else {
    include($cfg['path']['contenido'].$cfg['path']['templates'] . $cfg['templates']['right_top_blank']);
}

?>