<?php
/**
 * Plugin mod_rewrite backend include file to administer settings (in content frame)
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

global $client, $cfg;

################################################################################
##### Initialization

if ((int) $client <= 0) {
    // if there is no client selected, display empty page
    $oPage = new cGuiPage("mod_rewrite_content", "mod_rewrite");
    $oPage->displayCriticalError(i18n("No Client selected"));
    $oPage->render();
    return;
}

$action = (isset($_REQUEST['mr_action'])) ? $_REQUEST['mr_action'] : 'index';
$bDebug = false;


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
    'pattern' => '/^[\/\-_\.\$~]{1}$/',
    'info' => '<span class="text_medium_bold">/ - . _ ~</span>'
);
$aWordSeparator = array(
    'pattern' => '/^[\-_\.\$~]{1}$/',
    'info' => '<span class="text_medium_bold">- . _ ~</span>'
);

$routingSeparator = '>>>';


$oMrController->setProperty('bDebug', $bDebug);
$oMrController->setProperty('aSeparator', $aSeparator);
$oMrController->setProperty('aWordSeparator', $aWordSeparator);
$oMrController->setProperty('routingSeparator', $routingSeparator);

// define basic data contents (used for template)
$oView = $oMrController->getView();
$oView->content_before = '';
$oView->idclient = $client;
$oView->use_chk = (ModRewrite::isEnabled()) ? ' checked="checked"' : '';
$oView->header_notes_css = 'display:none;';

// mr copy .htaccess
$aHtaccessInfo = ModRewrite::getHtaccessInfo();
if ($aHtaccessInfo['has_htaccess']) {
    $oView->htaccess_info_css = 'display:none;';
} else {
    $oView->header_notes_css = 'display:table-row;';
    $oView->htaccess_info_css = 'display:block;';
}

// empty aliases
$iEmptyArticleAliases = ModRewrite::getEmptyArticlesAliases();
$iEmptyCategoryAliases = ModRewrite::getEmptyCategoriesAliases();
if ($iEmptyArticleAliases > 0 || $iEmptyCategoryAliases > 0) {
    $oView->header_notes_css = 'display:table-row;';
    $oView->emptyaliases_info_css = 'display:block;';
} else {
    $oView->emptyaliases_info_css = 'display:none;';
}

// mr root dir
$oView->rootdir = $aMrCfg['rootdir'];
$oView->rootdir_error = '';

// mr check root dir
$oView->checkrootdir_chk = ($aMrCfg['checkrootdir'] == 1) ? ' checked="checked"' : '';

// mr start from root
$oView->startfromroot_chk = ($aMrCfg['startfromroot'] == 1) ? ' checked="checked"' : '';

// mr prevent duplicated content
$oView->prevent_duplicated_content_chk = ($aMrCfg['prevent_duplicated_content'] == 1) ? ' checked="checked"' : '';

// mr language usage
$oView->use_language_chk = ($aMrCfg['use_language'] == 1) ? ' checked="checked"' : '';
$oView->use_language_name_chk = ($aMrCfg['use_language_name'] == 1) ? ' checked="checked"' : '';
$oView->use_language_name_disabled = ($aMrCfg['use_language'] == 1) ? '' : ' disabled="disabled"';

// mr client usage
$oView->use_client_chk = ($aMrCfg['use_client'] == 1) ? ' checked="checked"' : '';
$oView->use_client_name_chk = ($aMrCfg['use_client_name'] == 1) ? ' checked="checked"' : '';
$oView->use_client_name_disabled = ($aMrCfg['use_client'] == 1) ? '' : ' disabled="disabled"';

// mr lowecase uri
$oView->use_lowercase_uri_chk = ($aMrCfg['use_lowercase_uri'] == 1) ? ' checked="checked"' : '';

// mr category/category word separator
$oView->category_separator = $aMrCfg['category_seperator'];
$oView->category_separator_attrib = '';
$oView->category_word_separator = $aMrCfg['category_word_seperator'];
$oView->category_word_separator_attrib = '';
$oView->category_separator_error = '';
$oView->category_word_separator_error = '';

// mr article/article word separator
$oView->article_separator = $aMrCfg['article_seperator'];
$oView->article_separator_attrib = '';
$oView->article_word_separator = $aMrCfg['article_word_seperator'];
$oView->article_word_separator_attrib = '';
$oView->article_separator_error = '';
$oView->article_word_separator_error = '';

// mr file extension
$oView->file_extension = $aMrCfg['file_extension'];
$oView->file_extension_error = '';

// mr category name resolve percentage
$oView->category_resolve_min_percentage = $aMrCfg['category_resolve_min_percentage'];
$oView->category_resolve_min_percentage_error = '';

// mr add start article name to url
$oView->add_startart_name_to_url_chk = ($aMrCfg['add_startart_name_to_url'] == 1) ? ' checked="checked"' : '';
$oView->add_startart_name_to_url_error = '';
$oView->default_startart_name = $aMrCfg['default_startart_name'];

// mr rewrite urls at
$oView->rewrite_urls_at_congeneratecode_chk = ($aMrCfg['rewrite_urls_at_congeneratecode'] == 1) ? ' checked="checked"' : '';
$oView->rewrite_urls_at_front_content_output_chk = ($aMrCfg['rewrite_urls_at_front_content_output'] == 1) ? ' checked="checked"' : '';
$oView->content_after = '';

// mr rewrite routing
$data = '';
if (is_array($aMrCfg['routing'])) {
    foreach ($aMrCfg['routing'] as $uri => $route) {
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
$oView->lng_contenido_forum = i18n('CONTENIDO forum', 'mod_rewrite');
$oView->lng_pluginthread_in_contenido_forum = i18n('Plugin thread in CONTENIDO forum', 'mod_rewrite');
$oView->lng_plugin_settings = i18n('Plugin settings', 'mod_rewrite');
$oView->lng_note = i18n('Note', 'mod_rewrite');

$sMsg = i18n('The .htaccess file could not found either in CONTENIDO installation directory nor in client directory.<br>It should set up in %sFunctions%s area, if needed.', 'mod_rewrite');
$oView->lng_msg_no_htaccess_found = sprintf($sMsg, '<a href="main.php?area=mod_rewrite_expert&frame=4&contenido=' . $oView->sessid . '&idclient=' . $client . '" onclick="Con.markSubmenuItem(\'mod_rewrite_expert\');">', '</a>');

$sMsg = i18n('Found some category and/or article aliases. It is recommended to run the reset function in %sFunctions%s area, if needed.', 'mod_rewrite');
$oView->lng_msg_no_emptyaliases_found = sprintf($sMsg, '<a href="main.php?area=mod_rewrite_expert&frame=4&contenido=' . $oView->sessid . '&idclient=' . $client . '" onclick="Con.markSubmenuItem(\'mod_rewrite_expert\');">', '</a>');

$oView->lng_enable_amr = i18n('Enable Advanced Mod Rewrite', 'mod_rewrite');

$oView->lng_msg_enable_amr_info = i18n('Disabling of plugin does not result in disabling mod rewrite module of the web server - This means,<br> all defined rules in the .htaccess are still active and could create unwanted side effects.<br><br>Apache mod rewrite could be enabled/disabled by setting the RewriteEngine directive.<br>Any defined rewrite rules could remain in the .htaccess and they will not processed,<br>if the mod rewrite module is disabled', 'mod_rewrite');

$oView->lng_example = i18n('Example', 'mod_rewrite');

$oView->lng_msg_enable_amr_info_example = i18n("# enable apache mod rewrite module\nRewriteEngine on\n\n# disable apache mod rewrite module\nRewriteEngine off", 'mod_rewrite');

$oView->lng_rootdir = i18n('Path to .htaccess from DocumentRoot', 'mod_rewrite');
$oView->lng_rootdir_info = i18n("Type '/' if the .htaccess file lies inside the wwwroot (DocumentRoot) folder.<br>Type the path to the subfolder fromm wwwroot, if CONTENIDO is installed in a subfolder within the wwwroot<br>(e. g. http://domain/mycontenido -&gt; path = '/mycontenido/')", 'mod_rewrite');

$oView->lng_checkrootdir = i18n('Check path to .htaccess', 'mod_rewrite');
$oView->lng_checkrootdir_info = i18n('The path will be checked, if this option is enabled.<br>But this could result in an error in some cases, even if the specified path is valid and<br>clients DocumentRoot differs from CONTENIDO backend DocumentRoot.', 'mod_rewrite');

$oView->lng_startfromroot = i18n('Should the name of root category be displayed in the URL?', 'mod_rewrite');
$oView->lng_startfromroot_lbl = i18n('Start from root category', 'mod_rewrite');
$oView->lng_startfromroot_info = i18n('If enabled, the name of the root category (e. g. "Mainnavigation" in a CONTENIDO default installation), will be preceded to the URL.', 'mod_rewrite');

$oView->lng_use_client = i18n('Are several clients maintained in one directory?', 'mod_rewrite');
$oView->lng_use_client_lbl = i18n('Prepend client to the URL', 'mod_rewrite');
$oView->lng_use_client_name_lbl = i18n('Use client name instead of the id', 'mod_rewrite');

$oView->lng_use_language = i18n('Should the language appear in the URL (required for multi language websites)?', 'mod_rewrite');
$oView->lng_use_language_lbl = i18n('Prepend language to the URL', 'mod_rewrite');
$oView->lng_use_language_name_lbl = i18n('Use language name instead of the id', 'mod_rewrite');

$oView->lng_userdefined_separators_header = i18n('Configure your own separators with following 4 settings<br>to control generated URLs to your own taste', 'mod_rewrite');
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
$oView->lng_add_startart_name_to_url_lbl = i18n('Append article name always to URLs (even at URLs to categories)', 'mod_rewrite');
$oView->lng_default_startart_name = i18n('Default article name without extension', 'mod_rewrite');
$oView->lng_default_startart_name_info = i18n('e. g. "index" for index.ext<br>In case of selected "Append article name always to URLs" option and a empty field,<br>the name of the start article will be used', 'mod_rewrite');

$oView->lng_file_extension = i18n('File extension at the end of the URL', 'mod_rewrite');
$oView->lng_file_extension_info = i18n('Specification of file extension with a preceded dot<br>e.g. ".html" for http://host/foo/bar.html', 'mod_rewrite');
$oView->lng_file_extension_info2 = i18n('It\'s strongly recommended to specify a extension here,<br>if the option "Append article name always to URLs" was not selected.<br><br>Otherwise URLs to categories and articles would have the same format<br>which may result in unresolvable categories/articles in some cases.', 'mod_rewrite');
$oView->lng_file_extension_info3 = i18n('It\'s necessary to specify a file extension at the moment, due do existing issues, which are not solved until yet. An not defined extension may result in invalid article detection in some cases.', 'mod_rewrite');

$oView->lng_use_lowercase_uri = i18n('Should the URLs be written in lower case?', 'mod_rewrite');
$oView->lng_use_lowercase_uri_lbl = i18n('URLs in lower case', 'mod_rewrite');

$oView->lng_prevent_duplicated_content = i18n('Duplicated content', 'mod_rewrite');
$oView->lng_prevent_duplicated_content_lbl = i18n('Prevent duplicated content', 'mod_rewrite');

$oView->lng_prevent_duplicated_content_info = i18n('Depending on configuration, pages could be found thru different URLs.<br>Enabling of this option prevents this. Examples for duplicated content', 'mod_rewrite');
$oView->lng_prevent_duplicated_content_info2 = i18n("Name of the root category in the URL: Feasible is /maincategory/subcategory/ and /subcategory/\nLanguage in the URL: Feasible is /german/category/ and /1/category/\nClient in the URL: Feasible is /client/category/ und /1/category/", 'mod_rewrite');
$oView->lng_prevent_duplicated_content_info2 = '<li>' . str_replace("\n", '</li><li>', $oView->lng_prevent_duplicated_content_info2) . '</li>';

$oView->lng_category_resolve_min_percentage = i18n('Percentage for similar category paths in URLs', 'mod_rewrite');
$oView->lng_category_resolve_min_percentage_info = i18n('This setting refers only to the category path of a URL. If AMR is configured<br>to prepend e. g. the root category, language and/or client to the URL,<br>the specified percentage will not applied to those parts of the URL.<br>A incoming URL will be cleaned from those values and the remaining path (urlpath of the category)<br>will be checked against similarities.', 'mod_rewrite');
$oView->lng_category_resolve_min_percentage_example = i18n("100 = exact match with no tolerance\n85  = paths with little errors will match to similar ones\n0   = matching will work even for total wrong paths", 'mod_rewrite');

$oView->lng_redirect_invalid_article_to_errorsite = i18n('Redirect in case of invalid articles', 'mod_rewrite');
$oView->lng_redirect_invalid_article_to_errorsite_lbl = i18n('Redirect to error page in case of invaid articles', 'mod_rewrite');
$oView->lng_redirect_invalid_article_to_errorsite_info = i18n('The start page will be displayed if this option is not enabled', 'mod_rewrite');

$oView->lng_rewrite_urls_at = i18n('Moment of URL generation', 'mod_rewrite');
$oView->lng_rewrite_urls_at_front_content_output_lbl = i18n('a.) During the output of HTML code of the page', 'mod_rewrite');
$oView->lng_rewrite_urls_at_front_content_output_info = i18n('Clean-URLs will be generated during page output. Modules/Plugins are able to generate URLs to frontend<br>as usual as in previous CONTENIDO versions using a format like "front_content.php?idcat=1&amp;idart=2".<br>The URLs will be replaced by the plugin to Clean-URLs before sending the HTML output.', 'mod_rewrite');
$oView->lng_rewrite_urls_at_front_content_output_info2 = i18n('Differences to variant b.)', 'mod_rewrite');
$oView->lng_rewrite_urls_at_front_content_output_info3 = i18n("Still compatible to old modules/plugins, since no changes in codes are required\nAll occurring URLs in HTML code, even those set by wysiwyg, will be switched to Clean-URLs\nAll URLs will usually be collected and converted to Clean-URLs at once.<br>Doing it this way reduces the amount of executed database significantly.", 'mod_rewrite');
$oView->lng_rewrite_urls_at_front_content_output_info3 = '<li>' . str_replace("\n", '</li><li>', $oView->lng_rewrite_urls_at_front_content_output_info3) . '</li>';

$oView->lng_rewrite_urls_at_congeneratecode_lbl = i18n('b.) In modules or plugins', 'mod_rewrite');
$oView->lng_rewrite_urls_at_congeneratecode_info = i18n('By using this option, all Clean-URLs will be generated directly in module or plugins.<br>This means, all areas in modules/plugins, who generate internal URLs to categories/articles, have to be adapted manually.<br>All Clean-URLs have to be generated by using following function:', 'mod_rewrite');
$oView->lng_rewrite_urls_at_congeneratecode_example = i18n("# structure of a normal url\n\$url = 'front_content.php?idart=123&amp;lang=2&amp;client=1';\n\n# creation of a url by using the CONTENIDOs Url-Builder (since 4.8.9),\n# wich expects the parameter as a assoziative array\n\$params = array('idart'=>123, 'lang'=>2, 'client'=>1);\n\$newUrl = cUri::getInstance()->build(\$params);", 'mod_rewrite');
$oView->lng_rewrite_urls_at_congeneratecode_info2 = i18n('Differences to variant a.)', 'mod_rewrite');
$oView->lng_rewrite_urls_at_congeneratecode_info3 = i18n("The default way to generate URLs to fronend pages\nEach URL in modules/plugins has to be generated by UriBuilder\nEach generated Clean-Url requires a database query", 'mod_rewrite');
$oView->lng_rewrite_urls_at_congeneratecode_info3 = '<li>' . str_replace("\n", '</li><li>', $oView->lng_rewrite_urls_at_congeneratecode_info3) . '</li>';

$oView->lng_rewrite_routing = i18n('Routing', 'mod_rewrite');
$oView->lng_rewrite_routing_info = i18n('Routing definitions for incoming URLs', 'mod_rewrite');
$oView->lng_rewrite_routing_info2 = i18n('Type one routing definition per line as follows:', 'mod_rewrite');
$oView->lng_rewrite_routing_example = i18n("# {incoming_url}>>>{new_url}\n/incoming_url/name.html>>>new_url/new_name.html\n\n# route a specific incoming url to a new page\n/campaign/20_percent_on_everything_except_animal_food.html>>>front_content.php?idcat=23\n\n# route request to wwwroot to a specific page\n/>>>front_content.php?idart=16", 'mod_rewrite');
$oView->lng_rewrite_routing_info3 = i18n("The routing does not sends a HTTP header redirection to the destination URL, the redirection will happen internally by<br>replacing the detected incoming URL against the new destination URL (overwriting of article- categoryid)\nIncoming URLs can point to non existing resources (category/article), but the desttination URLs should point<br>to valid CONTENIDO articles/categories\nDestination URLs should point to real URLs to categories/articles,<br>e. g.front_content.php?idcat=23 or front_content.php?idart=34\nThe language id should attached to the URL in multi language sites<br>e. g. front_content.php?idcat=23&amp;lang=1\nThe client id should attached to the URL in multi client sites sharing the same folder<br>e. g. front_content.php?idcat=23&amp;client=2\nThe destination URL should not start with '/' or './' (wrong: /front_content.php, correct: front_content.php)", 'mod_rewrite');
$oView->lng_rewrite_routing_info3 = '<li>' . str_replace("\n", '</li><li>', $oView->lng_rewrite_routing_info3) . '</li>';

$oView->lng_discard_changes = i18n('Discard changes', 'mod_rewrite');
$oView->lng_save_changes = i18n('Save changes', 'mod_rewrite');


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
    cRegistry::getBackendPath() . $cfg['path']['plugins'] . 'mod_rewrite/templates/content.html'
);

