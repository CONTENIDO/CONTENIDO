<?php
/**
 * Plugin mod_rewrite backend include file to administer expert (in content frame)
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
    $oPage = new cGuiPage("mod_rewrite_contentexpert", "mod_rewrite");
    $oPage->displayCriticalError(i18n("No Client selected"));
    $oPage->render();
    return;
}

$action = (isset($_REQUEST['mr_action'])) ? $_REQUEST['mr_action'] : 'index';
$debug = false;

################################################################################
##### Some variables

$oMrController = new ModRewrite_ContentExpertController();

$aMrCfg = ModRewrite::getConfig();

$aHtaccessInfo = ModRewrite::getHtaccessInfo();

// define basic data contents (used for template)
$oView = $oMrController->getView();

// view variables
$oView->copy_htaccess_css = 'display:table-row;';
$oView->copy_htaccess_error = '';
$oView->copy_htaccess_contenido_chk = ' checked="checked"';
$oView->copy_htaccess_cms_chk = '';
$oView->contenido_full_path = $aHtaccessInfo['contenido_full_path'];
$oView->client_full_path = $aHtaccessInfo['client_full_path'];
$oView->content_after = '';

$oMrController->setProperty('htaccessInfo', $aHtaccessInfo);

// view language variables
$oView->lng_plugin_functions = i18n('Plugin functions', $pluginName);

$oView->lng_copy_htaccess_type = i18n('Copy/Download .htaccess template', $pluginName);
$oView->lng_copy_htaccess_type_lbl = i18n('Select .htaccess template', $pluginName);
$oView->lng_copy_htaccess_type1 = i18n('Restrictive .htaccess', $pluginName);
$oView->lng_copy_htaccess_type2 = i18n('Simple .htaccess', $pluginName);
$oView->lng_copy_htaccess_type_info1 = i18n('Contains rules with restrictive settings.<br>All requests pointing to extension avi, css, doc, flv, gif, gzip, ico, jpeg, jpg, js, mov, <br>mp3, pdf, png, ppt, rar, svg, swf, txt, wav, wmv, xml, zip, will be excluded vom rewriting.<br>Remaining requests will be rewritten to front_content.php,<br>except requests to \'contenido/\', \'setup/\', \'cms/upload\', \'cms/front_content.php\', etc.<br>Each resource, which has to be excluded from rewriting must be specified explicitly.', $pluginName);

$oView->lng_copy_htaccess_type_info2 = i18n('Contains a simple collection of rules. Each requests pointing to valid symlinks, folders or<br>files, will be excluded from rewriting. Remaining requests will be rewritten to front_content.php', $pluginName);

$oView->lng_copy_htaccess_to = i18n('and copy to', $pluginName);
$oView->lng_copy_htaccess_to_contenido = i18n('CONTENIDO installation directory', $pluginName);
$oView->lng_copy_htaccess_to_contenido_info = i18n('Copy the selected .htaccess template into CONTENIDO installation directory<br><br>&nbsp;&nbsp;&nbsp;&nbsp;{CONTENIDO_FULL_PATH}.<br><br>This is the recommended option for a CONTENIDO installation with one or more clients<br>who are running on the same domain.', $pluginName);
$oView->lng_copy_htaccess_to_contenido_info = str_replace('{CONTENIDO_FULL_PATH}', $oView->contenido_full_path, $oView->lng_copy_htaccess_to_contenido_info);
$oView->lng_copy_htaccess_to_client = i18n('client directory', $pluginName);
$oView->lng_copy_htaccess_to_client_info = i18n('Copy the selected .htaccess template into client\'s directory<br><br>&nbsp;&nbsp;&nbsp;&nbsp;{CLIENT_FULL_PATH}.<br><br>This is the recommended option for a multiple client system<br>where each client has it\'s own domain/subdomain', $pluginName);
$oView->lng_copy_htaccess_to_client_info = str_replace('{CLIENT_FULL_PATH}', $oView->client_full_path, $oView->lng_copy_htaccess_to_client_info);
$oView->lng_or = i18n('or', $pluginName);
$oView->lng_download = i18n('Download', $pluginName);
$oView->lng_download_info = i18n('Download selected .htaccess template to copy it to the destination folder<br>or to take over the settings manually.', $pluginName);

$oView->lng_resetaliases = i18n('Reset category-/ and article aliases', $pluginName);
$oView->lng_resetempty_link = i18n('Reset only empty aliases', $pluginName);
$oView->lng_resetempty_info = i18n('Only empty aliases will be reset, existing aliases, e. g. manually set aliases, will not be changed.', $pluginName);
$oView->lng_resetall_link = i18n('Reset all aliases', $pluginName);
$oView->lng_resetall_info = i18n('Reset all category-/article aliases. Existing aliases will be overwritten.', $pluginName);
$oView->lng_note = i18n('Note', $pluginName);
$oView->lng_resetaliases_note = i18n('This process could require some time depending on amount of categories/articles.<br>The aliases will not contain the configured plugin separators, but the CONTENIDO default separators \'/\' und \'-\', e. g. \'/category-word/article-word\'.<br>Execution of this function ma be helpful to prepare all or empty aliases for the usage by the plugin.', $pluginName);

$oView->lng_discard_changes = i18n('Discard changes', $pluginName);
$oView->lng_save_changes = i18n('Save changes', $pluginName);

################################################################################
##### Action processing

if ($action == 'index') {
    $oMrController->indexAction();
} elseif ($action == 'copyhtaccess') {
    $oMrController->copyHtaccessAction();
} elseif ($action == 'downloadhtaccess') {
    $oMrController->downloadHtaccessAction();
    exit();
} elseif ($action == 'reset') {
    $oMrController->resetAction();
} elseif ($action == 'resetempty') {
    $oMrController->resetEmptyAction();
} else {
    $oMrController->indexAction();
}

################################################################################
##### Output

$oMrController->render(
    cRegistry::getBackendPath() . $cfg['path']['plugins'] . 'mod_rewrite/templates/contentexpert.html'
);
