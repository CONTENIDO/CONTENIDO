<?php

/**
 * Plugin mod_rewrite backend include file to administer expert (in content frame)
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
    $page = new cGuiPage('mod_rewrite_contentexpert', 'mod_rewrite');
    $page->displayCriticalError(i18n("No Client selected"));
    $page->render();
    return;
}

$action = $_REQUEST['mr_action'] ?? 'index';
$debug = false;

################################################################################
##### Some variables

$mrController = new PiModRewriteExpertController();

$aMrCfg = PiModRewrite::getConfig();

$aHtaccessInfo = PiModRewrite::getHtaccessInfo();

// define basic data contents (used for template)
$view = $mrController->getView();

// view variables
$view->copy_htaccess_css = 'display:table-row;';
$view->copy_htaccess_error = '';
$view->copy_htaccess_contenido_chk = ' checked="checked"';
$view->copy_htaccess_cms_chk = '';
$view->contenido_full_path = $aHtaccessInfo['contenido_full_path'];
$view->client_full_path = $aHtaccessInfo['client_full_path'];
$view->content_after = '';

$mrController->setProperty('htaccessInfo', $aHtaccessInfo);

// view language variables
$view->lng_plugin_functions = i18n('Plugin functions', $pluginName);

$view->lng_copy_htaccess_type = i18n('Copy/Download .htaccess template', $pluginName);
$view->lng_copy_htaccess_type_lbl = i18n('Select .htaccess template', $pluginName);
$view->lng_copy_htaccess_type1 = i18n('Restrictive .htaccess', $pluginName);
$view->lng_copy_htaccess_type2 = i18n('Simple .htaccess', $pluginName);
$view->lng_copy_htaccess_type_info1 = i18n('Contains rules with restrictive settings.<br>All requests pointing to extension avi, css, doc, flv, gif, gzip, ico, jpeg, jpg, js, mov, <br>mp3, pdf, png, ppt, rar, svg, swf, txt, wav, wmv, xml, zip, will be excluded vom rewriting.<br>Remaining requests will be rewritten to front_content.php,<br>except requests to \'contenido/\', \'setup/\', \'cms/upload\', \'cms/front_content.php\', etc.<br>Each resource, which has to be excluded from rewriting must be specified explicitly.', $pluginName);

$view->lng_copy_htaccess_type_info2 = i18n('Contains a simple collection of rules. Each requests pointing to valid symlinks, folders or<br>files, will be excluded from rewriting. Remaining requests will be rewritten to front_content.php', $pluginName);

$view->lng_copy_htaccess_to = i18n('and copy to', $pluginName);
$view->lng_copy_htaccess_to_contenido = i18n('CONTENIDO installation directory', $pluginName);
$view->lng_copy_htaccess_to_contenido_info = i18n('Copy the selected .htaccess template into CONTENIDO installation directory<br><br>&nbsp;&nbsp;&nbsp;&nbsp;{CONTENIDO_FULL_PATH}.<br><br>This is the recommended option for a CONTENIDO installation with one or more clients<br>who are running on the same domain.', $pluginName);
$view->lng_copy_htaccess_to_contenido_info = str_replace('{CONTENIDO_FULL_PATH}', $view->contenido_full_path, $view->lng_copy_htaccess_to_contenido_info);
$view->lng_copy_htaccess_to_client = i18n('client directory', $pluginName);
$view->lng_copy_htaccess_to_client_info = i18n('Copy the selected .htaccess template into client\'s directory<br><br>&nbsp;&nbsp;&nbsp;&nbsp;{CLIENT_FULL_PATH}.<br><br>This is the recommended option for a multiple client system<br>where each client has it\'s own domain/subdomain', $pluginName);
$view->lng_copy_htaccess_to_client_info = str_replace('{CLIENT_FULL_PATH}', $view->client_full_path, $view->lng_copy_htaccess_to_client_info);
$view->lng_or = i18n('or', $pluginName);
$view->lng_download = i18n('Download', $pluginName);
$view->lng_download_info = i18n('Download selected .htaccess template to copy it to the destination folder<br>or to take over the settings manually.', $pluginName);

$view->lng_reset_aliases = i18n('Reset category-/ and article aliases', $pluginName);
$view->lng_reset_empty_link = i18n('Reset only empty aliases', $pluginName);
$view->lng_reset_empty_info = i18n('Only empty aliases will be reset, existing aliases, e. g. manually set aliases, will not be changed.', $pluginName);
$view->lng_reset_all_link = i18n('Reset all aliases', $pluginName);
$view->lng_reset_all_info = i18n('Reset all category-/article aliases. Existing aliases will be overwritten.', $pluginName);
$view->lng_note = i18n('Note', $pluginName);
$view->lng_reset_aliases_note = i18n('This process could require some time depending on amount of categories/articles.<br>The aliases will not contain the configured plugin separators, but the CONTENIDO default separators \'/\' und \'-\', e. g. \'/category-word/article-word\'.<br>Execution of this function ma be helpful to prepare all or empty aliases for the usage by the plugin.', $pluginName);

$view->lng_discard_changes = i18n('Discard changes', $pluginName);
$view->lng_save_changes = i18n('Save changes', $pluginName);

$view->lng_more_informations = i18n('More information', $pluginName);

################################################################################
##### Action processing

if ($action === 'index') {
    $mrController->indexAction();
} elseif ($action === 'copy_htaccess') {
    $mrController->copyHtaccessAction();
} elseif ($action === 'download_htaccess') {
    $mrController->downloadHtaccessAction();
    exit();
} elseif ($action === 'reset') {
    $mrController->resetAction();
} elseif ($action === 'reset_empty') {
    $mrController->resetEmptyAction();
} else {
    $mrController->indexAction();
}

################################################################################
##### Output

$mrController->render(
    cRegistry::getBackendPath() . $cfg['path']['plugins'] . 'mod_rewrite/templates/contentexpert.html'
);
