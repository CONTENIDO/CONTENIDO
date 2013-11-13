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

global $area, $action, $idform;

$cfg = cRegistry::getConfig();
$sess = cRegistry::getSession();

$anchorTpl = '<a class="white" target="right_bottom" href="%s">%s</a>';

$tpl = new cTemplate();

switch ($area) {
    case 'form':
    case 'form_fields':
    case 'form_data':
    case 'form_export':

        // show blank menu when form was just deleted
        if (PifaRightBottomFormPage::DELETE_FORM === $action) {
            cInclude('templates', $cfg['templates']['right_top_blank']);
            break;
        }

        // Set template data
        if (cRegistry::getPerm()->have_perm_area_action('form', PifaRightBottomFormPage::SHOW_FORM)) {
            $tpl->set('d', 'ID', 'c_' . $tpl->dyn_cnt);
            $tpl->set('d', 'DATA_NAME', 'form');
            $tpl->set('d', 'CLASS', '');
            $tpl->set('d', 'OPTIONS', '');
            $tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sess->url("main.php?area=form&action=" . PifaRightBottomFormPage::SHOW_FORM . "&frame=4&idform=$idform"), i18n("form", 'form_assistant')));
            $tpl->next();
        }

        if (0 < cSecurity::toInteger($idform)) {

            // Set template data
            if (cRegistry::getPerm()->have_perm_area_action('form_fields', PifaRightBottomFormFieldsPage::SHOW_FIELDS)) {
                $tpl->set('d', 'ID', 'c_' . $tpl->dyn_cnt);
                $tpl->set('d', 'DATA_NAME', 'form_fields');
                $tpl->set('d', 'CLASS', '');
                $tpl->set('d', 'OPTIONS', '');
                $tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sess->url("main.php?area=form_fields&action=" . PifaRightBottomFormFieldsPage::SHOW_FIELDS . "&frame=4&idform=$idform"), i18n("fields", 'form_assistant')));
                $tpl->next();
            }

            // Set template data
            if (cRegistry::getPerm()->have_perm_area_action('form_data', PifaRightBottomFormDataPage::SHOW_DATA)) {
                $tpl->set('d', 'ID', 'c_' . $tpl->dyn_cnt);
                $tpl->set('d', 'DATA_NAME', 'form_data');
                $tpl->set('d', 'CLASS', '');
                $tpl->set('d', 'OPTIONS', '');
                $tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sess->url("main.php?area=form_data&action=" . PifaRightBottomFormDataPage::SHOW_DATA . "&frame=4&idform=$idform"), i18n("data", 'form_assistant')));
                $tpl->next();
            }

            // Set template data
            if (cRegistry::getPerm()->have_perm_area_action('form_export', PifaRightBottomFormExportPage::EXPORT_FORM)) {
                $tpl->set('d', 'ID', 'c_' . $tpl->dyn_cnt);
                $tpl->set('d', 'DATA_NAME', 'form_export');
                $tpl->set('d', 'CLASS', '');
                $tpl->set('d', 'OPTIONS', '');
                $tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sess->url("main.php?area=form_export&action=" . PifaRightBottomFormExportPage::EXPORT_FORM . "&frame=4&idform=$idform"), i18n("EXPORT", 'form_assistant')));
                $tpl->next();
            }
        }

        break;

    case 'form_import':

        // Set template data
        if (cRegistry::getPerm()->have_perm_area_action('form_import', PifaRightBottomFormImportPage::IMPORT_FORM)) {
            $tpl->set('d', 'ID', 'c_' . $tpl->dyn_cnt);
            $tpl->set('d', 'DATA_NAME', 'form_import');
            $tpl->set('d', 'CLASS', '');
            $tpl->set('d', 'OPTIONS', '');
            $tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sess->url("main.php?area=form_import&action=" . PifaRightBottomFormImportPage::IMPORT_FORM . "&frame=4"), i18n("IMPORT", 'form_assistant')));
            $tpl->next();
        }

        break;
}

$tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);

// Generate the third navigation layer
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['subnav']);

?>