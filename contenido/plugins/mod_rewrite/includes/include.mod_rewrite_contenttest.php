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
 * @package    Plugin
 * @subpackage ModRewrite
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

################################################################################
##### Initialization

$cfg = cRegistry::getConfig();
$clientId = cRegistry::getClientId();
$pluginName = $cfg['pi_mod_rewrite']['pluginName'];

if ($clientId <= 0) {
    // if there is no client selected, display an empty page
    $page = new cGuiPage('mod_rewrite_contenttest', 'mod_rewrite');
    $page->displayCriticalError(i18n("No Client selected"));
    $page->render();
    return;
}

################################################################################
##### Processing

$mrTestNoOptionSelected = false;
if (
    !PiModRewriteRequestUtil::getRequest('idart')
    && !PiModRewriteRequestUtil::getRequest('idcat')
    && !PiModRewriteRequestUtil::getRequest('idcatart')
    && !PiModRewriteRequestUtil::getRequest('idartlang')) {
    $mrTestNoOptionSelected = true;
}

$oMrTestController = new PiModRewriteTestController();

// view language variables
$view = $oMrTestController->getView();
$view->lng_form_info = i18n('Define options to generate the URLs by using the form below and run the test.', $pluginName);
$view->lng_form_label = i18n('Parameter to use', $pluginName);
$view->lng_maxitems_lbl = i18n('Number of URLs to generate', $pluginName);
$view->lng_run_test = i18n('Run test', $pluginName);

$view->lng_result_item_tpl = i18n('{pref}<strong>{name}</strong><br>{pref}Builder in:    {url_in}<br>{pref}Builder out:   {url_out}<br>{pref}<span style="color:{color}">Resolved URL:  {url_res}</span><br>{pref}Resolver err:  {err}<br>{pref}Resolved data: {data}', $pluginName);

$view->lng_result_message_tpl = i18n('Duration of test run: {time} seconds.<br>Number of processed URLs: {num_urls}<br><span class="settingFine">Successful resolved: {num_success}</span><br><span class="settingWrong">Errors during resolving: {num_fail}</span></strong>', $pluginName);

################################################################################
##### Action processing

if ($mrTestNoOptionSelected) {
    $oMrTestController->indexAction();
} else {
    $oMrTestController->testAction();
}

$view = $oMrTestController->getView();
$view->content .= PiModRewriteDebugger::output(false);

################################################################################
##### Output

$oMrTestController->render(
    cRegistry::getBackendPath() . $cfg['path']['plugins'] . 'mod_rewrite/templates/contenttest.html'
);
