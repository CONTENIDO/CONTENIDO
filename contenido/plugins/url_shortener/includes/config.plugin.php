<?php

/**
 * This file contains the Plugin Manager configurations.
 *
 * @package    Plugin
 * @subpackage UrlShortener
 * @author     Simon Sprankel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $cfg, $lngAct;

$pluginName = basename(dirname(__DIR__, 1));

$cfg['plugins'][$pluginName] = cRegistry::getBackendPath() . $cfg['path']['plugins'] . "$pluginName/";

// extend the $cfg array with the table name if the table name has not been
// defined yet
// @deprecated [2023-02-03] Since 4.10.2, don't use $cfg['tab']['url_shortener']['shorturl'], use $cfg['tab']['url_shortener_shorturl'] instead!
if (!isset($cfg['tab']['url_shortener']['shorturl'])) {
    $cfg['tab']['url_shortener']['shorturl'] = $cfg['sql']['sqlprefix'] . '_pi_shorturl';
}
if (!isset($cfg['tab']['url_shortener_shorturl'])) {
    $cfg['tab']['url_shortener_shorturl'] = $cfg['sql']['sqlprefix'] . '_pi_shorturl';
}

// extend the $cfg array with the short URL rules if they have not been defined
// yet
if (!isset($cfg['url_shortener']['exlude_dirs'])) {
    $cfg['url_shortener']['exlude_dirs'] = [];
}
if (!isset($cfg['url_shortener']['minimum_length'])) {
    $cfg['url_shortener']['minimum_length'] = 3;
}
if (!isset($cfg['url_shortener']['allowed_chars'])) {
    $cfg['url_shortener']['allowed_chars'] = '/^[a-zA-Z0-9-_]*$/';
}

// Plugin translations for backend
$lngAct[$pluginName]["url_shortener_delete"] = i18n("Delete Short URLs", $pluginName);
$lngAct[$pluginName]["url_shortener_edit"] = i18n("Edit Short URLs", $pluginName);

// Include plugin sources
plugin_include($pluginName, 'classes/class.url_shortener.shorturl.php');
plugin_include($pluginName, 'includes/functions.url_shortener.php');

// add chain functions
$cecRegistry = cApiCecRegistry::getInstance();
// add additional rows to the article edit form
$cecRegistry->addChainFunction('Contenido.Backend.ConMetaEditFormAdditionalRows', 'piUsEditFormAdditionalRows');
// extend the save action of articles, so that the short URL is also saved
$cecRegistry->addChainFunction('Contenido.Action.con_meta_saveart.AfterCall', 'piUsConSaveArtAfter');
// hook as soon as possible, so that short URLs can be resolved early
$cecRegistry->addChainFunction('Contenido.Frontend.AfterLoadPlugins', 'piUsAfterLoadPlugins');
// delete short url entries if you delete article
$cecRegistry->addChainFunction('Contenido.Action.con_deleteart.AfterCall', 'piUseConDeleteArtAfter');

unset($pluginName);
