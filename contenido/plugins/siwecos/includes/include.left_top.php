<?php

/**
 *
 * @package Plugin
 * @subpackage SIWECOS
 * @author Fulai Zhang <fulai.zhang@4fb.de>
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$notification = new cGuiNotification();
$actions = array();

// ACTION: SHOW_FORM (in order to create new form)
if (cRegistry::getPerm()->have_perm_area_action('siwecos', SIWECOSRightBottomPage::STORE_FORM)) {
    global $area;

    $link = new cHTMLLink();
    $link->setMultiLink($area, SIWECOSRightBottomPage::SHOW_FORM, $area, SIWECOSRightBottomPage::SHOW_FORM);
    $link->setContent(SIWECOS::i18n("create"));
    // class addfunction lets display add icon beneath link
    $link->updateAttributes(array(
        'class' => 'addfunction'
    ));
    $actions[] = $link->render();
} else {
    $actions[] = $notification->returnNotification(cGuiNotification::LEVEL_WARNING, SIWECOS::i18n('CREATE_FORM_NO_PERMISSIONS'));
}

$page = new cGuiPage('left_top', 'siwecos');
foreach ($actions as $action) {
    $page->set('d', 'ACTION', $action);
    $page->next();
}
$page->render();

?>