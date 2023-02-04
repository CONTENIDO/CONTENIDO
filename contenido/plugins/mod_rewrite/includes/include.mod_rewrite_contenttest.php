<?php
/**
 * Testscript for Advanced Mod Rewrite Plugin.
 *
 * The goal of this testscript is to provide an easy way for a variance comparison
 * of created SEO URLs against their resolved parts.
 *
 * This testscript fetches the full category and article structure of actual
 * CONTENIDO installation, creates the SEO URLs for each existing category/article
 * and resolves the generated URLs.
 *
 * @package     Plugin
 * @subpackage  ModRewrite
 * @id          $Id$:
 * @author      Murat Purc <murat@purc.de>
 * @copyright   four for business AG <www.4fb.de>
 * @license     http://www.contenido.org/license/LIZENZ.txt
 * @link        http://www.4fb.de
 * @link        http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

################################################################################
##### Initialization

$cfg = cRegistry::getConfig();
$client = cSecurity::toInteger(cRegistry::getClientId());
$pluginName = $cfg['pi_mod_rewrite']['pluginName'];

if ($client <= 0) {
    // if there is no client selected, display empty page
    $oPage = new cGuiPage("mod_rewrite_contenttest", "mod_rewrite");
    $oPage->displayCriticalError(i18n("No Client selected"));
    $oPage->render();
    return;
}

################################################################################
##### Processing

$mrTestNoOptionSelected = false;
if (!mr_getRequest('idart') && !mr_getRequest('idcat') && !mr_getRequest('idcatart') && !mr_getRequest('idartlang')) {
    $mrTestNoOptionSelected = true;
}

$oMrTestController = new ModRewrite_ContentTestController();

// view language variables
$oView = $oMrTestController->getView();
$oView->lng_form_info = i18n('Define options to genereate the URLs by using the form below and run the test.', $pluginName);
$oView->lng_form_label = i18n('Parameter to use', $pluginName);
$oView->lng_maxitems_lbl = i18n('Number of URLs to generate', $pluginName);
$oView->lng_run_test = i18n('Run test', $pluginName);

$oView->lng_result_item_tpl = i18n('{pref}<strong>{name}</strong><br>{pref}Builder in:    {url_in}<br>{pref}Builder out:   {url_out}<br>{pref}<span style="color:{color}">Resolved URL:  {url_res}</span><br>{pref}Resolver err:  {err}<br>{pref}Resolved data: {data}', $pluginName);

$oView->lng_result_message_tpl = i18n('Duration of test run: {time} seconds.<br>Number of processed URLs: {num_urls}<br><span class="settingFine">Successful resolved: {num_success}</span><br><span class="settingWrong">Errors during resolving: {num_fail}</span></strong>', $pluginName);

################################################################################
##### Action processing

if ($mrTestNoOptionSelected) {
    $oMrTestController->indexAction();
} else {
    $oMrTestController->testAction();
}

$oView = $oMrTestController->getView();
$oView->content .= mr_debugOutput(false);

################################################################################
##### Output

$oMrTestController->render(
    cRegistry::getBackendPath() . $cfg['path']['plugins'] . 'mod_rewrite/templates/contenttest.html'
);
