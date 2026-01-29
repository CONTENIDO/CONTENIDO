<?php

/**
 * Plugin mod_rewrite backend include file to administer settings (in content frame)
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
    $page = new cGuiPage('mod_rewrite_content', 'mod_rewrite');
    $page->displayCriticalError(i18n("No Client selected"));
    $page->render();
    return;
}

$action = $_REQUEST['mr_action'] ?? 'index';
$bDebug = false;

################################################################################
##### Some variables

$mrController = new PiModRewriteContentController();

$aMrCfg = PiModRewrite::getConfig();

// downwards compatibility to previous plugin versions
if (PiModRewriteUtil::arrayValue($aMrCfg, 'category_seperator', '') == '') {
    $aMrCfg['category_seperator'] = '/';
}
if (PiModRewriteUtil::arrayValue($aMrCfg, 'category_word_seperator', '') == '') {
    $aMrCfg['category_word_seperator'] = '-';
}
if (PiModRewriteUtil::arrayValue($aMrCfg, 'article_seperator', '') == '') {
    $aMrCfg['article_seperator'] = '/';
}
if (PiModRewriteUtil::arrayValue($aMrCfg, 'article_word_seperator', '') == '') {
    $aMrCfg['article_word_seperator'] = '-';
}

// some settings
$aSeparator = [
    'pattern' => '/^[\/\-_\.\$~]{1}$/',
    'info' => '<span class="text_medium_bold">/ - . _ ~</span>'
];
$aWordSeparator = [
    'pattern' => '/^[\-_\.\$~]{1}$/',
    'info' => '<span class="text_medium_bold">- . _ ~</span>'
];

$routingSeparator = '>>>';

$mrController->setProperty('bDebug', $bDebug);
$mrController->setProperty('aSeparator', $aSeparator);
$mrController->setProperty('aWordSeparator', $aWordSeparator);
$mrController->setProperty('routingSeparator', $routingSeparator);

// define basic data contents (used for template)
$view = $mrController->getView();
$view->content_before = '';
$view->idclient = $clientId;
$view->use_chk = (PiModRewrite::isEnabled()) ? ' checked="checked"' : '';
$view->header_notes_css = 'display:none;';

// mr copy .htaccess
$aHtaccessInfo = PiModRewrite::getHtaccessInfo();
if ($aHtaccessInfo['has_htaccess']) {
    $view->htaccess_info_css = 'display:none;';
} else {
    $view->header_notes_css = 'display:table-row;';
    $view->htaccess_info_css = 'display:block;';
}

// empty aliases
$emptyArticleAliasesCount = PiModRewrite::getEmptyArticlesAliases();
$emptyCategoryAliasesCount = PiModRewrite::getEmptyCategoriesAliases();
if ($emptyArticleAliasesCount > 0 || $emptyCategoryAliasesCount > 0) {
    $view->header_notes_css = 'display:table-row;';
    $view->emptyaliases_info_css = 'display:block;';
} else {
    $view->emptyaliases_info_css = 'display:none;';
}

// mr root dir
$view->rootdir = $aMrCfg['rootdir'];
$view->rootdir_error = '';

// mr check root dir
$view->checkrootdir_chk = ($aMrCfg['checkrootdir'] == 1) ? ' checked="checked"' : '';

// mr start from root
$view->startfromroot_chk = ($aMrCfg['startfromroot'] == 1) ? ' checked="checked"' : '';

// mr prevent duplicated content
$view->prevent_duplicated_content_chk = ($aMrCfg['prevent_duplicated_content'] == 1) ? ' checked="checked"' : '';

// mr language usage
$view->use_language_chk = ($aMrCfg['use_language'] == 1) ? ' checked="checked"' : '';
$view->use_language_name_chk = ($aMrCfg['use_language_name'] == 1) ? ' checked="checked"' : '';
$view->use_language_name_disabled = ($aMrCfg['use_language'] == 1) ? '' : ' disabled="disabled"';

// mr client usage
$view->use_client_chk = ($aMrCfg['use_client'] == 1) ? ' checked="checked"' : '';
$view->use_client_name_chk = ($aMrCfg['use_client_name'] == 1) ? ' checked="checked"' : '';
$view->use_client_name_disabled = ($aMrCfg['use_client'] == 1) ? '' : ' disabled="disabled"';

// mr lowercase uri
$view->use_lowercase_uri_chk = ($aMrCfg['use_lowercase_uri'] == 1) ? ' checked="checked"' : '';

// mr category/category word separator
$view->category_separator = $aMrCfg['category_seperator'];
$view->category_separator_attrib = '';
$view->category_word_separator = $aMrCfg['category_word_seperator'];
$view->category_word_separator_attrib = '';
$view->category_separator_error = '';
$view->category_word_separator_error = '';

// mr article/article word separator
$view->article_separator = $aMrCfg['article_seperator'];
$view->article_separator_attrib = '';
$view->article_word_separator = $aMrCfg['article_word_seperator'];
$view->article_word_separator_attrib = '';
$view->article_separator_error = '';
$view->article_word_separator_error = '';

// mr file extension
$view->file_extension = $aMrCfg['file_extension'];
$view->file_extension_error = '';

// mr category name resolve percentage
$view->category_resolve_min_percentage = $aMrCfg['category_resolve_min_percentage'];
$view->category_resolve_min_percentage_error = '';

// mr add start article name to url
$view->add_startart_name_to_url_chk = ($aMrCfg['add_startart_name_to_url'] == 1) ? ' checked="checked"' : '';
$view->add_startart_name_to_url_error = '';
$view->default_startart_name = $aMrCfg['default_startart_name'];

// mr rewrite urls at
$view->rewrite_urls_at_congeneratecode_chk = ($aMrCfg['rewrite_urls_at_congeneratecode'] == 1) ? ' checked="checked"' : '';
$view->rewrite_urls_at_front_content_output_chk = ($aMrCfg['rewrite_urls_at_front_content_output'] == 1) ? ' checked="checked"' : '';
$view->content_after = '';

// mr rewrite routing
$data = '';
if (is_array($aMrCfg['routing'])) {
    foreach ($aMrCfg['routing'] as $uri => $route) {
        $data .= $uri . $routingSeparator . $route . "\n";
    }
}
$view->rewrite_routing = $data;

// mr redirect invalid article
$view->redirect_invalid_article_to_errorsite_chk = ($aMrCfg['redirect_invalid_article_to_errorsite'] == 1) ? ' checked="checked"' : '';

$view->lng_version = i18n('Version', $pluginName);
$view->lng_author = i18n('Author', $pluginName);
$view->lng_mail_to_author = i18n('E-Mail to author', $pluginName);
$view->lng_pluginpage = i18n('Plugin page', $pluginName);
$view->lng_visit_pluginpage = i18n('Visit plugin page', $pluginName);
$view->lng_opens_in_new_window = i18n('opens page in new window', $pluginName);
$view->lng_contenido_forum = i18n('CONTENIDO forum', $pluginName);
$view->lng_pluginthread_in_contenido_forum = i18n('Plugin thread in CONTENIDO forum', $pluginName);
$view->lng_plugin_settings = i18n('Plugin settings', $pluginName);
$view->lng_note = i18n('Note', $pluginName);

$sMsg = i18n('The .htaccess file could not be found either in CONTENIDO installation directory nor in client directory.<br>It should set up in %sFunctions%s area, if needed.', $pluginName);
$view->lng_msg_no_htaccess_found = sprintf($sMsg, '<a href="main.php?area=mod_rewrite_expert&frame=4&contenido=' . $view->sessid . '&idclient=' . $clientId . '" onclick="Con.markSubmenuItem(\'mod_rewrite_expert\');">', '</a>');

$sMsg = i18n('Found some category and/or article aliases. It is recommended to run the reset function in %sFunctions%s area, if needed.', $pluginName);
$view->lng_msg_no_emptyaliases_found = sprintf($sMsg, '<a href="main.php?area=mod_rewrite_expert&frame=4&contenido=' . $view->sessid . '&idclient=' . $clientId . '" onclick="Con.markSubmenuItem(\'mod_rewrite_expert\');">', '</a>');

$view->lng_enable_amr = i18n('Enable Advanced Mod Rewrite', $pluginName);

$view->lng_msg_enable_amr_info = i18n('Disabling of plugin does not result in disabling mod rewrite module of the web server - This means,<br> all defined rules in the .htaccess are still active and could create unwanted side effects.<br><br>Apache mod rewrite could be enabled/disabled by setting the RewriteEngine directive.<br>Any defined rewrite rules could remain in the .htaccess and they will not be processed,<br>if the mod rewrite module is disabled', $pluginName);

$view->lng_example = i18n('Example', $pluginName);

$view->lng_msg_enable_amr_info_example = i18n("# enable apache mod rewrite module\nRewriteEngine on\n\n# disable apache mod rewrite module\nRewriteEngine off", $pluginName);

$view->lng_rootdir = i18n('Path to .htaccess from DocumentRoot', $pluginName);
$view->lng_rootdir_info = i18n("Type '/' if the .htaccess file lies inside the www-root (DocumentRoot) folder.<br>Type the path to the subfolder fromm www-root, if CONTENIDO is installed in a subfolder within the www-root<br>(e. g. https://domain/mycontenido -&gt; path = '/mycontenido/')", $pluginName);

$view->lng_checkrootdir = i18n('Check path to .htaccess', $pluginName);
$view->lng_checkrootdir_info = i18n('The path will be checked, if this option is enabled.<br>But this could result in an error in some cases, even if the specified path is valid and<br>clients DocumentRoot differs from CONTENIDO backend DocumentRoot.', $pluginName);

$view->lng_startfromroot = i18n('Should the name of root category be displayed in the URL?', $pluginName);
$view->lng_startfromroot_lbl = i18n('Start from root category', $pluginName);
$view->lng_startfromroot_info = i18n('If enabled, the name of the root category (e. g. "Main Navigation" in a CONTENIDO default installation), will be preceded to the URL.', $pluginName);

$view->lng_use_client = i18n('Are several clients maintained in one directory?', $pluginName);
$view->lng_use_client_lbl = i18n('Prepend client to the URL', $pluginName);
$view->lng_use_client_name_lbl = i18n('Use client name instead of the id', $pluginName);

$view->lng_use_language = i18n('Should the language appear in the URL (required for multi language websites)?', $pluginName);
$view->lng_use_language_lbl = i18n('Prepend language to the URL', $pluginName);
$view->lng_use_language_name_lbl = i18n('Use language name instead of the id', $pluginName);

$view->lng_userdefined_separators_header = i18n('Configure your own separators with following 4 settings<br>to control generated URLs to your own taste', $pluginName);
$view->lng_userdefined_separators_example = i18n("www.domain.com/category1-category2.articlename.html\nwww.domain.com/category1/category2-articlename.html\nwww.domain.com/category.name1~category2~articlename.html\nwww.domain.com/category_name1-category2-articlename.foo", $pluginName);
$view->lng_userdefined_separators_example_a = i18n('Category separator has to be different from category-word separator', $pluginName);
$view->lng_userdefined_separators_example_a_example = i18n("# Example: Category separator (/) and category-word separator (_)\ncategory_one/category_two/articlename.html", $pluginName);
$view->lng_userdefined_separators_example_b = i18n('Category separator has to be different from article-word separator', $pluginName);
$view->lng_userdefined_separators_example_b_example = i18n("# Example: Category separator (/) and article-word separator (-)\ncategory_one/category_two/article-description.html", $pluginName);
$view->lng_userdefined_separators_example_c = i18n('Category-article separator has to be different from article-word separator', $pluginName);
$view->lng_userdefined_separators_example_c_example = i18n("# Example: Category-article separator (/) and article-word separator (-)\ncategory_one/category_two/article-description.html", $pluginName);

$view->lng_category_separator = i18n('Category separator (delimiter between single categories)', $pluginName);
$view->lng_catart_separator_info = sprintf(i18n('(possible values: %s)', $pluginName), $aSeparator['info']);
$view->lng_word_separator_info = sprintf(i18n('(possible values: %s)', $pluginName), $aWordSeparator['info']);
$view->lng_category_word_separator = i18n('Category-word separator (delimiter between category words)', $pluginName);
$view->lng_article_separator = i18n('Category-article separator (delimiter between category-block and article)', $pluginName);
$view->lng_article_word_separator = i18n('Article-word separator (delimiter between article words)', $pluginName);

$view->lng_add_startart_name_to_url = i18n('Append article name to URLs', $pluginName);
$view->lng_add_startart_name_to_url_lbl = i18n('Append article name always to URLs (even at URLs to categories)', $pluginName);
$view->lng_default_startart_name = i18n('Default article name without extension', $pluginName);
$view->lng_default_startart_name_info = i18n('e. g. "index" for index.ext<br>In case of selected "Append article name always to URLs" option and a empty field,<br>the name of the start article will be used', $pluginName);

$view->lng_file_extension = i18n('File extension at the end of the URL', $pluginName);
$view->lng_file_extension_info = i18n('Specification of file extension with a preceded dot<br>e.g. ".html" for https://host/foo/bar.html', $pluginName);
$view->lng_file_extension_info2 = i18n('It\'s strongly recommended to specify an extension here,<br>if the option "Append article name always to URLs" was not selected.<br><br>Otherwise, URLs to categories and articles would have the same format<br>which may result in unresolvable categories/articles in some cases.', $pluginName);
$view->lng_file_extension_info3 = i18n('It\'s necessary to specify a file extension at the moment, due do existing issues, which are not solved until yet. An not defined extension may result in invalid article detection in some cases.', $pluginName);

$view->lng_use_lowercase_uri = i18n('Should the URLs be written in lower case?', $pluginName);
$view->lng_use_lowercase_uri_lbl = i18n('URLs in lower case', $pluginName);

$view->lng_prevent_duplicated_content = i18n('Duplicated content', $pluginName);
$view->lng_prevent_duplicated_content_lbl = i18n('Prevent duplicated content', $pluginName);

$view->lng_prevent_duplicated_content_info = i18n('Depending on configuration, pages could be found through different URLs.<br>Enabling of this option prevents this. Examples for duplicated content', $pluginName);
$view->lng_prevent_duplicated_content_info2 = i18n("Name of the root category in the URL: Feasible is /maincategory/subcategory/ and /subcategory/\nLanguage in the URL: Feasible is /german/category/ and /1/category/\nClient in the URL: Feasible is /client/category/ und /1/category/", $pluginName);
$view->lng_prevent_duplicated_content_info2 = '<li>' . str_replace("\n", '</li><li>', $view->lng_prevent_duplicated_content_info2) . '</li>';

$view->lng_category_resolve_min_percentage = i18n('Percentage for similar category paths in URLs', $pluginName);
$view->lng_category_resolve_min_percentage_info = i18n('This setting refers only to the category path of a URL. If AMR is configured<br>to prepend e. g. the root category, language and/or client to the URL,<br>the specified percentage will not apply to those parts of the URL.<br>A incoming URL will be cleaned from those values and the remaining path (urlpath of the category)<br>will be checked against similarities.', $pluginName);
$view->lng_category_resolve_min_percentage_example = i18n("100 = exact match with no tolerance\n85  = paths with little errors will match to similar ones\n0   = matching will work even for total wrong paths", $pluginName);

$view->lng_redirect_invalid_article_to_errorsite = i18n('Redirect in case of invalid articles', $pluginName);
$view->lng_redirect_invalid_article_to_errorsite_lbl = i18n('Redirect to error page in case of invalid articles', $pluginName);
$view->lng_redirect_invalid_article_to_errorsite_info = i18n('The start page will be displayed if this option is not enabled', $pluginName);

$view->lng_rewrite_urls_at = i18n('Moment of URL generation', $pluginName);
$view->lng_rewrite_urls_at_front_content_output_lbl = i18n('a.) During the output of HTML code of the page', $pluginName);
$view->lng_rewrite_urls_at_front_content_output_info = i18n('Clean-URLs will be generated during page output. Modules/Plugins are able to generate URLs to frontend<br>as usual as in previous CONTENIDO versions using a format like "front_content.php?idcat=1&amp;idart=2".<br>The URLs will be replaced by the plugin to Clean-URLs before sending the HTML output.', $pluginName);
$view->lng_rewrite_urls_at_front_content_output_info2 = i18n('Differences to variant b.)', $pluginName);
$view->lng_rewrite_urls_at_front_content_output_info3 = i18n("Still compatible to old modules/plugins, since no changes in codes are required\nAll occurring URLs in HTML code, even those set by wysiwyg, will be switched to Clean-URLs\nAll URLs will usually be collected and converted to Clean-URLs at once.<br>Doing it this way reduces the amount of executed database significantly.", $pluginName);
$view->lng_rewrite_urls_at_front_content_output_info3 = '<li>' . str_replace("\n", '</li><li>', $view->lng_rewrite_urls_at_front_content_output_info3) . '</li>';

$view->lng_rewrite_urls_at_congeneratecode_lbl = i18n('b.) In modules or plugins', $pluginName);
$view->lng_rewrite_urls_at_congeneratecode_info = i18n('By using this option, all Clean-URLs will be generated directly in module or plugins.<br>This means, all areas in modules/plugins, who generate internal URLs to categories/articles, have to be adapted manually.<br>All Clean-URLs have to be generated by using following function:', $pluginName);
$view->lng_rewrite_urls_at_congeneratecode_example = i18n("# structure of a normal url\n\$url = 'front_content.php?idart=123&amp;lang=2&amp;client=1';\n\n# creation of a url by using the CONTENIDOs Url-Builder (since 4.8.9),\n# wich expects the parameter as a assoziative array\n\$params = array('idart'=>123, 'lang'=>2, 'client'=>1);\n\$newUrl = cUri::getInstance()->build(\$params);", $pluginName);
$view->lng_rewrite_urls_at_congeneratecode_info2 = i18n('Differences to variant a.)', $pluginName);
$view->lng_rewrite_urls_at_congeneratecode_info3 = i18n("The default way to generate URLs to fronend pages\nEach URL in modules/plugins has to be generated by UriBuilder\nEach generated Clean-Url requires a database query", $pluginName);
$view->lng_rewrite_urls_at_congeneratecode_info3 = '<li>' . str_replace("\n", '</li><li>', $view->lng_rewrite_urls_at_congeneratecode_info3) . '</li>';

$view->lng_rewrite_routing = i18n('Routing', $pluginName);
$view->lng_rewrite_routing_info = i18n('Routing definitions for incoming URLs', $pluginName);
$view->lng_rewrite_routing_info2 = i18n('Type one routing definition per line as follows:', $pluginName);
$view->lng_rewrite_routing_example = i18n("# {incoming_url}>>>{new_url}\n/incoming_url/name.html>>>new_url/new_name.html\n\n# route a specific incoming url to a new page\n/campaign/20_percent_on_everything_except_animal_food.html>>>front_content.php?idcat=23\n\n# route request to www-root to a specific page\n/>>>front_content.php?idart=16", $pluginName);
$view->lng_rewrite_routing_info3 = i18n("The routing does not send a HTTP header redirection to the destination URL, the redirection will happen internally by<br>replacing the detected incoming URL against the new destination URL (overwriting of article- categoryid)\nIncoming URLs can point to non-existing resources (category/article), but the destination URLs should point<br>to valid CONTENIDO articles/categories\nDestination URLs should point to real URLs to categories/articles,<br>e. g.front_content.php?idcat=23 or front_content.php?idart=34\nThe language id should be attached to the URL on multi-language sites<br>e. g. front_content.php?idcat=23&amp;lang=1\nThe client id should be attached to the URL in multi client sites sharing the same folder<br>e. g. front_content.php?idcat=23&amp;client=2\nThe destination URL should not start with '/' or './' (wrong: /front_content.php, correct: front_content.php)", $pluginName);
$view->lng_rewrite_routing_info3 = '<li>' . str_replace("\n", '</li><li>', $view->lng_rewrite_routing_info3) . '</li>';

$view->lng_discard_changes = i18n('Discard changes', $pluginName);
$view->lng_save_changes = i18n('Save changes', $pluginName);

$view->lng_more_informations = i18n('More information', $pluginName);

################################################################################
##### Action processing

if ($action === 'index') {
    $mrController->indexAction();
} elseif ($action === 'save') {
    $mrController->saveAction();
} else {
    $mrController->indexAction();
}

################################################################################
##### Output

$mrController->render(
    cRegistry::getBackendPath() . $cfg['path']['plugins'] . 'mod_rewrite/templates/content.html'
);

