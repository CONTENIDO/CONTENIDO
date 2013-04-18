<?php
/**
 * This file contains the Custom subnavigation for the newsletters.
 *
 * @package Plugin
 * @subpackage Newsletter
 * @version SVN Revision $Rev:$
 *
 * @author Björn Behrens
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

if (isset($_GET['idnewsletter']) && (int) $_GET['idnewsletter'] > 0) {
    $anchorTpl = '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="%s">%s</a>';
    $idnewsletter = (int) $_GET['idnewsletter'];

    // Set template data
    $tpl->set('d', 'ID', 'c_' . $tpl->dyn_cnt);
    $tpl->set('d', 'CLASS', '');
    $tpl->set('d', 'OPTIONS', '');
    $tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sess->url("main.php?area=news&frame=4&idnewsletter=$idnewsletter"), i18n("Edit", 'newsletter')));
    $tpl->next();

    // Set template data
    $tpl->set('d', 'ID', 'c_' . $tpl->dyn_cnt);
    $tpl->set('d', 'CLASS', '');
    $tpl->set('d', 'OPTIONS', '');
    $tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sess->url("main.php?area=news_edit&frame=4&idnewsletter=$idnewsletter"), i18n("Edit Message", 'newsletter')));
    $tpl->next();

    $tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);

    // Generate the third navigation layer
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['subnav']);
} else {
    include (cRegistry::getBackendPath() . $cfg['path']['templates'] . $cfg['templates']['right_top_blank']);
}

?>