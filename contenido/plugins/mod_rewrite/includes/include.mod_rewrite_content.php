<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Plugin mod_rewrite backend include file to administer settings (in content frame)
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend plugins
 * @version    0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since Contenido release 4.8.15
 *
 * {@internal
 *   created  2008-04-22
 *
 *   $Id$:
 * }}
 *
 */


defined('CON_FRAMEWORK') or die('Illegal call');


################################################################################
##### Initialization

plugin_include('mod_rewrite', 'classes/controller/class.modrewrite_content_controller.php');

$action = (isset($_REQUEST['mr_action'])) ? $_REQUEST['mr_action'] : 'index';
$bDebug  = false;

//var_dump($cfg['templates']['mod_rewrite_content']);


################################################################################
##### Some variables


$oMrController = new ModRewrite_ContentController();

$aMrCfg = ModRewrite::getConfig();

// downwards compatibility to previous plugin versions
if (mr_arrayValue($aMrCfg, 'category_seperator', '') == '') {
    $aMrCfg['category_seperator'] = '/';
}
if (mr_arrayValue($aMrCfg, 'category_word_seperator', '') == '') {
    $aMrCfg['category_word_seperator'] = '-';
}
if (mr_arrayValue($aMrCfg, 'article_seperator', '') == '') {
    $aMrCfg['article_seperator'] = '/';
}
if (mr_arrayValue($aMrCfg, 'article_word_seperator', '') == '') {
    $aMrCfg['article_word_seperator'] = '-';
}

// some settings
$aSeparator = array(
    'pattern'         => '/^[\/\-_\.\$~]{1}$/',
    'info'            => '<span style="font-family:courier;font-weight:bold;">/ - . _ ~</span>'
);
$aWordSeparator = array(
    'pattern'         => '/^[\-_\.\$~]{1}$/',
    'info'            => '<span style="font-family:courier;font-weight:bold;">- . _ ~</span>'
);

$routingSeparator = '>>>';


$oMrController->setProperty('bDebug', $bDebug);
$oMrController->setProperty('aSeparator', $aSeparator);
$oMrController->setProperty('aWordSeparator', $aWordSeparator);
$oMrController->setProperty('routingSeparator', $routingSeparator);

// define basic data contents (used for template)
$oView = $oMrController->getView();
$oView->content_before   = '';
$oView->idclient         = $client;
$oView->use_chk          = (ModRewrite::isEnabled()) ? ' checked="checked"' : '';

// mr copy .htaccess
$aHtaccessInfo = ModRewrite::getHtaccessInfo();

if ($aHtaccessInfo['has_htaccess']) {
    $oView->htaccess_info_css = 'display:none;';
} else {
    $oView->htaccess_info_css = 'display:table-row;';
}

// mr root dir
$oView->rootdir       = $aMrCfg['rootdir'];
$oView->rootdir_error = '';

// mr check root dir
$oView->checkrootdir_chk = ($aMrCfg['checkrootdir'] == 1) ? ' checked="checked"' : '';

// mr start from root
$oView->startfromroot_chk = ($aMrCfg['startfromroot'] == 1) ? ' checked="checked"' : '';

// mr prevent duplicated content
$oView->prevent_duplicated_content_chk = ($aMrCfg['prevent_duplicated_content'] == 1) ? ' checked="checked"' : '';

// mr language usage
$oView->use_language_chk           = ($aMrCfg['use_language'] == 1) ? ' checked="checked"' : '';
$oView->use_language_name_chk      = ($aMrCfg['use_language_name'] == 1) ? ' checked="checked"' : '';
$oView->use_language_name_disabled = ($aMrCfg['use_language'] == 1) ? '' : ' disabled="disabled"';

// mr client usage
$oView->use_client_chk           = ($aMrCfg['use_client'] == 1) ? ' checked="checked"' : '';
$oView->use_client_name_chk      = ($aMrCfg['use_client_name'] == 1) ? ' checked="checked"' : '';
$oView->use_client_name_disabled = ($aMrCfg['use_client'] == 1) ? '' : ' disabled="disabled"';

// mr lowecase uri
$oView->use_lowercase_uri_chk         = ($aMrCfg['use_lowercase_uri'] == 1) ? ' checked="checked"' : '';

// mr category/category word separator
$oView->category_separator             = $aMrCfg['category_seperator'];
$oView->category_separator_attrib      = '';
$oView->category_word_separator        = $aMrCfg['category_word_seperator'];
$oView->category_word_separator_attrib = '';
$oView->category_separator_error      = '';
$oView->category_word_separator_error = '';

// mr article/article word separator
$oView->article_separator             = $aMrCfg['article_seperator'];
$oView->article_separator_attrib      = '';
$oView->article_word_separator        = $aMrCfg['article_word_seperator'];
$oView->article_word_separator_attrib = '';
$oView->article_separator_error       = '';
$oView->article_word_separator_error  = '';

$oView->cat_art_sep_msg    = '(M&ouml;gliche Werte: ' . $aSeparator['info'] . ')';
$oView->word_separator_msg = '(M&ouml;gliche Werte: ' . $aWordSeparator['info'] . ')';

// mr file extension
$oView->file_extension       = $aMrCfg['file_extension'];
$oView->file_extension_error = '';

// mr category name resolve percentage
$oView->category_resolve_min_percentage       = $aMrCfg['category_resolve_min_percentage'];
$oView->category_resolve_min_percentage_error = '';

// mr add start article name to url
$oView->add_startart_name_to_url_chk   = ($aMrCfg['add_startart_name_to_url'] == 1) ? ' checked="checked"' : '';
$oView->add_startart_name_to_url_error = '';
$oView->default_startart_name          = $aMrCfg['default_startart_name'];

// mr rewrite urls at
$oView->rewrite_urls_at_congeneratecode_chk      = ($aMrCfg['rewrite_urls_at_congeneratecode'] == 1) ? ' checked="checked"' : '';
$oView->rewrite_urls_at_front_content_output_chk = ($aMrCfg['rewrite_urls_at_front_content_output'] == 1) ? ' checked="checked"' : '';
$oView->content_after = '';

// mr rewrite routing
$data = '';
if (is_array($aMrCfg['routing'])) {
    foreach ($aMrCfg['routing'] as $uri => $route){
        $data .= $uri . $routingSeparator . $route . "\n";
    }
}
$oView->rewrite_routing = $data;

// mr redirect invalid article
$oView->redirect_invalid_article_to_errorsite_chk = ($aMrCfg['redirect_invalid_article_to_errorsite'] == 1) ? ' checked="checked"' : '';


$oView->lng_version = i18n('Version', 'mod_rewrite');
$oView->lng_author = i18n('Author', 'mod_rewrite');
$oView->lng_mail_to_author = i18n('E-Mail to author', 'mod_rewrite');
$oView->lng_pluginpage = i18n('Plugin page', 'mod_rewrite');
$oView->lng_visit_pluginpage = i18n('Visit plugin page', 'mod_rewrite');
$oView->lng_opens_in_new_window = i18n('opens page in new window', 'mod_rewrite');
$oView->lng_contenido_forum = i18n('Contenido forum', 'mod_rewrite');
$oView->lng_pluginthread_in_contenido_forum = i18n('Plugin thread in Contenido forum', 'mod_rewrite');
$oView->lng_plugin_settings = i18n('Plugin settings', 'mod_rewrite');
$oView->lng_note = i18n('Note', 'mod_rewrite');

$sMsg = i18n('The .htaccess file could not found either in Contenido installation directory nor in client directory.<br />It should set up in %sFunctions%s area, if needed.', 'mod_rewrite');
$oView->lng_msg_no_htaccess_found = sprintf($sMsg, '<a href="main.php?area=mod_rewrite_expert&frame=4&contenido={SESSID}&idclient={IDCLIENT}" onclick="parent.right_top.sub.clicked(parent.right_top.document.getElementById(\'c_1\').firstChild);">', '</a>');

$oView->lng_enable_amr = i18n('Enable Advanced Mod Rewrite', 'mod_rewrite');

$oView->lng_msg_enable_amr_info = i18n('Disabling of plugin does not result in disabling mod rewrite module of the web server - This means,<br /> all defined rules in the .htaccess are still active and could create unwanted side effects.<br /><br />Apache mod rewrite could be enabled/disabled by setting the RewriteEngine directive.<br />Any defined rewrite rules could remain in the .htaccess and they will not processed,<br />if the mod rewrite module is disabled', 'mod_rewrite');

$oView->lng_example = i18n('Example', 'mod_rewrite');

$oView->lng_msg_enable_amr_info_example = i18n("# enable apache mod rewrite module\nRewriteEngine on\n\n# disable apache mod rewrite module\nRewriteEngine off", 'mod_rewrite');

# aktivieren des apache mod rewrite moduls\nRewriteEngine on\n\n# deaktivieren des apache mod rewrite moduls\nRewriteEngine off

################################################################################
##### Action processing

if ($action == 'index') {

    $oMrController->indexAction();

} elseif ($action == 'save') {

    $oMrController->saveAction();

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
    $cfg['path']['contenido'] . $cfg['path']['plugins'] . 'mod_rewrite/templates/content.html'
);

