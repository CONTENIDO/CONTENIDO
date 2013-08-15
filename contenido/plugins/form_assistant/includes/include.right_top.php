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

global $action, $idform;

$cfg = cRegistry::getConfig();

if (PifaRightBottomFormPage::DELETE_FORM === $action) {

    // show blank menu when form was just deleted
    cInclude('templates', $cfg['templates']['right_top_blank']);
} else if (0 < cSecurity::toInteger($idform)) {

    $anchorTpl = '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="%s">%s</a>';
    $sess = cRegistry::getSession();

    $tpl = new cTemplate();

    // Set template data
    if (cRegistry::getPerm()->have_perm_area_action('form', PifaRightBottomFormPage::SHOW_FORM)) {
        $tpl->set('d', 'ID', 'c_' . $tpl->dyn_cnt);
        $tpl->set('d', 'CLASS', '');
        $tpl->set('d', 'OPTIONS', '');
        $tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sess->url("main.php?area=form&action=" . PifaRightBottomFormPage::SHOW_FORM . "&frame=4&idform=$idform"), i18n("form", 'form_assistant')));
        $tpl->next();
    }

    // Set template data
    if (cRegistry::getPerm()->have_perm_area_action('form_fields', PifaRightBottomFormFieldsPage::SHOW_FIELDS)) {
        $tpl->set('d', 'ID', 'c_' . $tpl->dyn_cnt);
        $tpl->set('d', 'CLASS', '');
        $tpl->set('d', 'OPTIONS', '');
        $tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sess->url("main.php?area=form_fields&action=" . PifaRightBottomFormFieldsPage::SHOW_FIELDS . "&frame=4&idform=$idform"), i18n("fields", 'form_assistant')));
        $tpl->next();
    }

    // Set template data
    if (cRegistry::getPerm()->have_perm_area_action('form_data', PifaRightBottomFormDataPage::SHOW_DATA)) {
        $tpl->set('d', 'ID', 'c_' . $tpl->dyn_cnt);
        $tpl->set('d', 'CLASS', '');
        $tpl->set('d', 'OPTIONS', '');
        $tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sess->url("main.php?area=form_data&action=" . PifaRightBottomFormDataPage::SHOW_DATA . "&frame=4&idform=$idform"), i18n("data", 'form_assistant')));
        $tpl->next();
    }

    $tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);

    // Generate the third navigation layer
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['subnav']);
} else {

    // show blank menu when no form was selected
    cInclude('templates', $cfg['templates']['right_top_blank']);
}

?>