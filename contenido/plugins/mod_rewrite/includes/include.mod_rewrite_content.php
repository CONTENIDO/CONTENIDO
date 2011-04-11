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
 *   $Id: $:
 * }}
 * 
 */


defined('CON_FRAMEWORK') or die('Illegal call');


################################################################################
##### Initialization

plugin_include('mod_rewrite', 'classes/controller/class.modrewrite_content_controller.php');

$action = (isset($_REQUEST['mr_action'])) ? $_REQUEST['mr_action'] : 'index';
$debug  = false;

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

