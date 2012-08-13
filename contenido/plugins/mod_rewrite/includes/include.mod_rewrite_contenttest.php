<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Testscript for Advanced Mod Rewrite Plugin.
 *
 * The goal of this testscript is to provide an easy way for a variance comparison
 * of created SEO URLs against their resolved parts.
 *
 * This testscript fetches the full category and article structure of actual
 * CONTENIDO installation, creates the SEO URLs for each existing category/article
 * and resolves the generated URLs.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package     CONTENIDO Plugins
 * @subpackage  ModRewrite
 * @version     0.1
 * @author      Murat Purc <murat@purc.de>
 * @copyright   four for business AG <www.4fb.de>
 * @license     http://www.contenido.org/license/LIZENZ.txt
 * @link        http://www.4fb.de
 * @link        http://www.contenido.org
 * @since       file available since CONTENIDO release 4.9.0
 *
 * {@internal
 *   created   2011-04-11
 *   modified  2011-05-17  Murat Purc, added check for available client id
 *
 *   $Id$:
 * }}
 *
 */


defined('CON_FRAMEWORK') or die('Illegal call');


################################################################################
##### Initialization

if ((int) $client <= 0) {
    // if there is no client selected, display empty page
    $oPage = new cGuiPage("mod_rewrite_contenttest", "mod_rewrite");
    $oPage->displayCriticalError(i18n("No Client selected"));
    $oPage->render();
    return;
}

plugin_include('mod_rewrite', 'classes/controller/class.modrewrite_contenttest_controller.php');


################################################################################
##### Processing

$mrTestNoOptionSelected = false;
if (!mr_getRequest('idart') && !mr_getRequest('idcat') && !mr_getRequest('idcatart') && !mr_getRequest('idartlang')) {
    $mrTestNoOptionSelected = true;
}


$oMrTestController = new ModRewrite_ContentTestController();


// view language variables
$oView = $oMrTestController->getView();
$oView->lng_form_info = i18n('Define options to genereate the URLs by using the form below and run the test.', 'mod_rewrite');
$oView->lng_form_label = i18n('Parameter to use', 'mod_rewrite');
$oView->lng_maxitems_lbl = i18n('Number of URLs to generate', 'mod_rewrite');
$oView->lng_run_test = i18n('Run test', 'mod_rewrite');

$oView->lng_result_item_tpl = i18n('{pref}<strong>{name}</strong>
{pref}Builder in:    {url_in}
{pref}Builder out:   {url_out}
{pref}<span style="color:{color}">Resolved URL:  {url_res}</span>
{pref}Resolved data: {data}', 'mod_rewrite');

$oView->lng_result_message_tpl = i18n('Duration of test run: {time} seconds.<br>
Number of processed URLs: {num_urls}<br>
<span style="color:green">Successful resolved: {num_success}</span><br>
<span style="color:red">Errors during resolving: {num_fail}</span></strong>', 'mod_rewrite');


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
    $cfg['path']['contenido'] . $cfg['path']['plugins'] . 'mod_rewrite/templates/contenttest.html'
);
