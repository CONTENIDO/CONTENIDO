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
$oView->lng_msg_no_htaccess_found = sprintf($sMsg, '<a href="main.php?area=mod_rewrite_expert&frame=4&contenido=' . $oView->sessid . '&idclient=' . $client . '" onclick="parent.right_top.sub.clicked(parent.right_top.document.getElementById(\'c_1\').firstChild);">', '</a>');

$oView->lng_enable_amr = i18n('Enable Advanced Mod Rewrite', 'mod_rewrite');

$oView->lng_msg_enable_amr_info = i18n('Disabling of plugin does not result in disabling mod rewrite module of the web server - This means,<br /> all defined rules in the .htaccess are still active and could create unwanted side effects.<br /><br />Apache mod rewrite could be enabled/disabled by setting the RewriteEngine directive.<br />Any defined rewrite rules could remain in the .htaccess and they will not processed,<br />if the mod rewrite module is disabled', 'mod_rewrite');

$oView->lng_example = i18n('Example', 'mod_rewrite');

$oView->lng_msg_enable_amr_info_example = i18n("# enable apache mod rewrite module\nRewriteEngine on\n\n# disable apache mod rewrite module\nRewriteEngine off", 'mod_rewrite');

$oView->lng_rootdir = i18n('Path to .htaccess from DocumentRoot', 'mod_rewrite');
$oView->lng_rootdir_info = i18n("Type '/' if the .htaccess file lies inside the wwwroot (DocumentRoot) folder.<br />Type the path to the subfolder fromm wwwroot, if Contenido is installed in a subfolder within the wwwroot<br />(e. g. http://domain/mycontenido -&gt; path = '/mycontenido/')", 'mod_rewrite');

$oView->lng_checkrootdir = i18n('Check path to .htaccess', 'mod_rewrite');
$oView->lng_checkrootdir_info = i18n('The path will be checked, if this option is enabled.<br />But this could result in an error in some cases, even if the specified path is valid and<br />clients DocumentRoot differs from Contenido backend DocumentRoot.', 'mod_rewrite');

$oView->lng_startfromroot = i18n('Should the name of root category be displayed in the URL?', 'mod_rewrite');
$oView->lng_startfromroot_lbl = i18n('Start from root category', 'mod_rewrite');
$oView->lng_startfromroot_info = i18n('If enabled, the name of the root category (e. g. "Mainnavigation" in a Contenido default installation), will be prepended to the URL.', 'mod_rewrite');

$oView->lng_use_client = i18n('Are several clients maintained in one directory?', 'mod_rewrite');
$oView->lng_use_client_lbl = i18n('Prepend client to the URL', 'mod_rewrite');
$oView->lng_use_client_name_lbl = i18n('Use client name instead of the id', 'mod_rewrite');

$oView->lng_use_language = i18n('Should the language appear in the URL (required for multi language websites)?', 'mod_rewrite');
$oView->lng_use_language_lbl = i18n('Prepend language to the URL', 'mod_rewrite');
$oView->lng_use_language_name_lbl = i18n('Use language name instead of the id', 'mod_rewrite');

$oView->lng_userdefined_separators_header = i18n('Configure your own separators with following 4 settings<br />to control generated URLs to your own taste', 'mod_rewrite');
$oView->lng_userdefined_separators_example = i18n("www.domain.com/category1-category2.articlename.html\nwww.domain.com/category1/category2-articlename.html\nwww.domain.com/category.name1~category2~articlename.html\nwww.domain.com/category_name1-category2-articlename.foo", 'mod_rewrite');
$oView->lng_userdefined_separators_example_a = i18n('Category separator has to be different from category-word separator', 'mod_rewrite');
$oView->lng_userdefined_separators_example_a_example = i18n("# Example: Category separator (/) and category-word separator (_)\ncategory_one/category_two/articlename.html", 'mod_rewrite');
$oView->lng_userdefined_separators_example_b = i18n('Category separator has to be different from article-word separator', 'mod_rewrite');
$oView->lng_userdefined_separators_example_b_example = i18n("# Example: Category separator (/) and article-word separator (-)\ncategory_one/category_two/article-description.html", 'mod_rewrite');
$oView->lng_userdefined_separators_example_c = i18n('Category-article separator has to be different from article-word separator', 'mod_rewrite');
$oView->lng_userdefined_separators_example_c_example = i18n("# Example: Category-article separator (/) and article-word separator (-)\ncategory_one/category_two/article-description.html", 'mod_rewrite');

$oView->lng_category_separator = i18n('Category separator (delemiter between single categories)', 'mod_rewrite');
$oView->lng_catart_separator_info = sprintf(i18n('(possible values: %s)', 'mod_rewrite'), $aSeparator['info']);
$oView->lng_word_separator_info = sprintf(i18n('(possible values: %s)', 'mod_rewrite'), $aWordSeparator['info']);
$oView->lng_category_word_separator = i18n('Category-word separator (delemiter between category words)', 'mod_rewrite');
$oView->lng_article_separator = i18n('Category-article separator (delemiter between category-block and article)', 'mod_rewrite');
$oView->lng_article_word_separator = i18n('Article-word separator (delemiter between article words)', 'mod_rewrite');

$oView->lng_add_startart_name_to_url = i18n('Append article name to URLs', 'mod_rewrite');
$oView->lng_add_startart_name_to_url_lbl = i18n('Append article name allways to URLs (even at URLs to categories)', 'mod_rewrite');
$oView->lng_default_startart_name = i18n('Default article name without extension', 'mod_rewrite');
$oView->lng_default_startart_name_info = i18n('e. g. "index" for index.ext<br />In case od selected "Append article name allways to URLs" option and a empty field,<br />the name of the start article will be used', 'mod_rewrite');


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

