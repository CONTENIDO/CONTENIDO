<?php
/**
 * Plugin Manager configurations
 *
 * @package plugin
 * @subpackage URL Shortener
 * @version SVN Revision $Rev:$
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

global $cfg;

// extend the $cfg array with the table name if the table name has not been
// defined yet
if (!isset($cfg['tab']['url_shortener']['shorturl'])) {
    $cfg['tab']['url_shortener']['shorturl'] = $cfg['sql']['sqlprefix'] . '_pius_shorturl';
}

// include plugin classes
plugin_include('url_shortener', 'classes/class.url_shortener.shorturl.php');

// include plugin includes
plugin_include('url_shortener', 'includes/functions.url_shortener.php');

// add chain functions
$cecRegistry = cApiCecRegistry::getInstance();
// add additional rows to the article edit form
// TODO uncomment after next merge
// $cecRegistry->addChainFunction('Contenido.Backend.ConEditFormAdditionalRows', 'piusEditFormAdditionalRows');
// extend the save action of articles, so that the short URL is also saved
$cecRegistry->addChainFunction('Contenido.Action.con_saveart.AfterCall', 'piusConSaveArtAfter');
