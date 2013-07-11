<?php

/**
 *
 * @package Plugin
 * @subpackage FormAssistant
 * @version SVN Revision $Rev:$
 * @author marcus.gnass
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$cfg = cRegistry::getConfig();
$sess = cRegistry::getSession();

global $action, $idform;

if ('delete_form' !== $action && 0 < cSecurity::toInteger($idform)) {

    $anchorTpl = '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="%s">%s</a>';
    $idform = (int) $_GET['idform'];

    // Set template data
    $tpl->set('d', 'ID', 'c_' . $tpl->dyn_cnt);
    $tpl->set('d', 'CLASS', '');
    $tpl->set('d', 'OPTIONS', '');
    $tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sess->url("main.php?area=form&action=show_form&frame=4&idform=$idform"), i18n("Form", 'form_assistant')));
    $tpl->next();

    // Set template data
    $tpl->set('d', 'ID', 'c_' . $tpl->dyn_cnt);
    $tpl->set('d', 'CLASS', '');
    $tpl->set('d', 'OPTIONS', '');
    $tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sess->url("main.php?area=form_fields&action=show_fields&frame=4&idform=$idform"), i18n("Form fields", 'form_assistant')));
    $tpl->next();

    // Set template data
    $tpl->set('d', 'ID', 'c_' . $tpl->dyn_cnt);
    $tpl->set('d', 'CLASS', '');
    $tpl->set('d', 'OPTIONS', '');
    $tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sess->url("main.php?area=form_data&action=show_data&frame=4&idform=$idform"), i18n("Form data", 'form_assistant')));
    $tpl->next();

    $tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);

    // Generate the third navigation layer
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['subnav']);
} else {
    cInclude('templates', $cfg['templates']['right_top_blank']);
}

?>