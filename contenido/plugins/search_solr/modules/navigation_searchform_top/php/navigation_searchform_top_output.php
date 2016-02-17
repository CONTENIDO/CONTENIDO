<?php

/**
 * description: top search form
 *
 * @package Module
 * @subpackage NavigationSearchformTop
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

// assert framework initialization
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// get IDART of search result article
$searchResultIdart = getEffectiveSetting('navigation_searchform_top', 'search_result_idart');
$searchResultIdart = cSecurity::toInteger($searchResultIdart);

// check if plugin mod_rewrite is enabled
$isModRewriteEnabled = class_exists('ModRewrite') && ModRewrite::isEnabled();

// show search form only if search result article is defined
$action = $method = $label = $submit = '';
if (0 < $searchResultIdart) {

    // determine action & method for search form
    if ($isModRewriteEnabled) {
        $action = cUri::getInstance()->build(array(
            'idart' => $searchResultIdart,
            'lang' => cRegistry::getLanguageId()
        ));
    } else {
        $action = 'front_content.php';
    }

    // determine how the search request should be transmitted
    $method = 'GET';

    // determine label to be shown inside input field
    $label = mi18n("NAVIGATION_SEARCHFORM_TOP_LABEL");
    // this translation is optional
    if (false !== strpos($label, 'Module translation not found: ')) {
        $label = '';
    }

    // determine label to be shown on submit button
    $submit = mi18n("NAVIGATION_SEARCHFORM_TOP_SUBMIT");
    // this translation is optional
    if (false !== strpos($submit, 'Module translation not found: ')) {
        $submit = '';
    }
}

// use template to display search form
$tpl = cSmartyFrontend::getInstance();
$tpl->assign('action', $action);
$tpl->assign('method', $method);
$tpl->assign('label', $label);
$tpl->assign('submit', $submit);
if (!$isModRewriteEnabled) {
    $tpl->assign('idart', $searchResultIdart);
    $tpl->assign('idlang', cRegistry::getLanguageId());
}
$tpl->display('get.tpl');

?>