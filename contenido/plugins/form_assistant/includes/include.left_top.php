<?php

/**
 *
 * @package    Plugin
 * @subpackage FormAssistant
 * @author     Marcus Gnaß <marcus.gnass@4fb.de>
 * @copyright  four for business AG
 * @link       https://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$notification = new cGuiNotification();
$actions = [];

// ACTION: SHOW_FORM (in order to create new form)
if (cRegistry::getPerm()->have_perm_area_action('form', PifaRightBottomFormPage::STORE_FORM)) {
    global $area;

    $link = new cHTMLLink();
    $link->setMultiLink($area, PifaRightBottomFormPage::SHOW_FORM, $area, PifaRightBottomFormPage::SHOW_FORM);
    $link->setContent(Pifa::i18n('CREATE_FORM'));
    // class addfunction lets display add icon beneath link
    $link->updateAttributes([
        'class' => 'con_func_button addfunction'
    ]);
    $actions[] = $link->render();
} else {
    $actions[] = $notification->returnNotification(cGuiNotification::LEVEL_WARNING, Pifa::i18n('CREATE_FORM_NO_PERMISSIONS'));
}

// ACTION: IMPORT_FORM
if (cRegistry::getPerm()->have_perm_area_action('form_import', PifaRightBottomFormImportPage::IMPORT_FORM)) {
    $link = new cHTMLLink();
    $link->setMultiLink('form_import', PifaRightBottomFormImportPage::IMPORT_FORM, 'form_import', PifaRightBottomFormImportPage::IMPORT_FORM);
    $link->setContent(Pifa::i18n('IMPORT_FORM'));
    $link->setContent('<img src="images/folder_new.gif" alt="">&nbsp;' . Pifa::i18n('pifa_import_form'));
    $link->setTargetFrame('right_bottom');
    $actions[] = $link->render();
} else {
    $actions[] = $notification->returnNotification(cGuiNotification::LEVEL_WARNING, Pifa::i18n('IMPORT_FORM_NO_PERMISSIONS'));
}

$page = new cGuiPage('left_top', 'form_assistant');
foreach ($actions as $action) {
    $page->set('d', 'ACTION', $action);
    $page->next();
}
$page->render();
