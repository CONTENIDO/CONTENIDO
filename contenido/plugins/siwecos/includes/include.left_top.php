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
$actions = [];

// ACTION: SHOW_FORM (in order to create new form)
if (cRegistry::getPerm()->have_perm_area_action('siwecos', SIWECOSRightBottomPage::STORE_FORM)) {
    $area = cRegistry::getArea();

    $link = new cHTMLLink();
    $link->setMultiLink($area, SIWECOSRightBottomPage::SHOW_FORM, $area, SIWECOSRightBottomPage::SHOW_FORM);
    $link->setContent(i18n('BTN_CREATE', 'siwecos'));
    // class addfunction lets display add icon beneath link
    $link->updateAttributes([
        'class' => 'addfunction'
    ]);
    $actions[] = $link->render();
} else {
    $actions[] = $notification->returnNotification(cGuiNotification::LEVEL_WARNING, i18n('ERR_PERMISSION_DENIED', 'siwecos'));
}

$page = new cGuiPage('left_top', 'siwecos');
foreach ($actions as $action) {
    $page->set('d', 'ACTION', $action);
    $page->next();
}
$page->render();
