<?php
/**
 * This file contains the Custom subnavigation for the newsletters.
 *
 * @package Plugin
 * @subpackage Newsletter
 * @version SVN Revision $Rev:$
 *
 * @author Bjoern Behrens
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

if (!isset($_GET['idnewsjob']) || (int) $_GET['idnewsjob'] <= 0) {
    $tpl->reset();
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['right_top_blank']);
    return;
}

$anchorTpl = '<a class="white" target="right_bottom" href="%s">%s</a>';
$idnewsjob = (int) $_GET['idnewsjob'];

// Set template data
$tpl->set('d', 'ID', 'c_' . $tpl->dyn_cnt);
$tpl->set('d', 'DATA_NAME', 'news_jobs');
$tpl->set('d', 'CLASS', '');
$tpl->set('d', 'OPTIONS', '');
$tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sess->url("main.php?area=news_jobs&frame=4&idnewsjob=$idnewsjob"), i18n("View", 'newsletter')));
$tpl->next();

// Set template data
$tpl->set('d', 'ID', 'c_' . $tpl->dyn_cnt);
$tpl->set('d', 'DATA_NAME', 'news_jobs');
$tpl->set('d', 'CLASS', '');
$tpl->set('d', 'OPTIONS', '');
$tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sess->url("main.php?area=news_jobs&action=news_job_details&frame=4&idnewsjob=$idnewsjob"), i18n("Details", 'newsletter')));
$tpl->next();

$tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);

$tpl->set('s', 'CLASS', ''); // With menu (left frame)

// Generate the third navigation layer
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['subnav']);
