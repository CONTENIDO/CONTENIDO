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

if (cRegistry::getPerm()->have_perm_area_action('form_fields', PifaRightBottomFormPage::STORE_FORM)) {
    global $area;

    $link = new cHTMLLink();
    $link->setMultiLink($area, PifaRightBottomFormPage::SHOW_FORM, $area, PifaRightBottomFormPage::SHOW_FORM);
    $link->setContent(Pifa::i18n('CREATE_FORM'));
    // class addfunction lets display add icon beneath link
    $link->updateAttributes(array(
        'class' => 'addfunction'
    ));

    $page = new cGuiPage('left_top', 'form_assistant');
    $page->set('s', 'LINK', $link->render());
    $page->render();
}

?>