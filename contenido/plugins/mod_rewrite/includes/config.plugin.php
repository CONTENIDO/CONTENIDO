<?php
/**
 * Plugin Advanced Mod Rewrite initialization file.
 *
 * This file will be included by CONTENIDO plugin loader routine, and the content
 * of this file ensures that the AMR Plugin will be initialized correctly.
 *
 * @package     Plugin
 * @subpackage  ModRewrite
 * @id          $Id$:
 * @author      Murat Purc <murat@purc.de>
 * @copyright   four for business AG <www.4fb.de>
 * @license     https://www.contenido.org/license/LIZENZ.txt
 * @link        https://www.4fb.de
 * @link        https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $_cecRegistry, $cfg, $lngAct, $load_client;

####################################################################################################
/**
 * Chain Contenido.Frontend.CreateURL
 * is called inside some scripts (front_content.php) to create urls.
 *
 * @todo: Is added to provide downwards compatibility for the amr plugin.
 *        There is no need for this chain since CONTENIDO 4.8.9 contains its own Url building feature.
 * @deprecated
 *
 * Parameters & order:
 * string   URL including parameter value pairs
 *
 * Returns:
 * string     Returns modified URL
 */

####################################################################################################

$contenido = cRegistry::getBackendSessionId();
$area = cRegistry::getArea();
$client = cSecurity::toInteger(cRegistry::getClientId());

// Initialize client id
if (isset($client) && (int) $client > 0) {
    $clientId = (int) $client;
} elseif (isset($load_client) && (int) $load_client > 0) {
    $clientId = (int) $load_client;
} else {
    $clientId = '';
}

$pluginName = basename(dirname(__DIR__, 1));

$cfg['plugins'][$pluginName] = cRegistry::getBackendPath() . $cfg['path']['plugins'] . "$pluginName/";

// Plugin configuration
$cfg['pi_mod_rewrite'] = [
    'pluginName' => $pluginName,
];

// Plugin translation for usage in backend areas (menus, right, etc.)
$lngAct[$pluginName]['mod_rewrite'] = i18n('Advanced Mod Rewrite', $pluginName);
$lngAct[$pluginName]['mod_rewrite_expert'] = i18n('Advanced Mod Rewrite functions', $pluginName);
$lngAct[$pluginName]['mod_rewrite_test'] = i18n('Advanced Mod Rewrite test', $pluginName);

// Include necessary sources, Setup autoloader for plugin
$pluginClassesPath = cRegistry::getBackendPath(true) . $cfg['path']['plugins'] . "$pluginName/classes";
cAutoload::addClassmapConfig([
    'ModRewrite_ControllerAbstract' => $pluginClassesPath . '/controller/class.modrewrite_controller_abstract.php',
    'ModRewrite_ContentController' => $pluginClassesPath . '/controller/class.modrewrite_content_controller.php',
    'ModRewrite_ContentExpertController' => $pluginClassesPath . '/controller/class.modrewrite_contentexpert_controller.php',
    'ModRewrite_ContentTestController' => $pluginClassesPath . '/controller/class.modrewrite_contenttest_controller.php',
    'ModRewriteBase' => $pluginClassesPath . '/class.modrewritebase.php',
    'ModRewrite' => $pluginClassesPath . '/class.modrewrite.php',
    'ModRewriteController' => $pluginClassesPath . '/class.modrewritecontroller.php',
    'ModRewriteDebugger' => $pluginClassesPath . '/class.modrewritedebugger.php',
    'ModRewriteTest' => $pluginClassesPath . '/class.modrewritetest.php',
    'ModRewriteUrlStack' => $pluginClassesPath . '/class.modrewriteurlstack.php',
    'ModRewriteUrlUtil' => $pluginClassesPath . '/class.modrewriteurlutil.php'
]);

plugin_include($pluginName, 'includes/functions.mod_rewrite.php');

// Set debug configuration
ModRewriteDebugger::setEnabled(!empty(cRegistry::getBackendSessionId()));

// Initialize mr plugin
ModRewrite::initialize($clientId);

if (ModRewrite::isEnabled()) {
    $aMrCfg = ModRewrite::getConfig();

    $_cecRegistry = cApiCecRegistry::getInstance();

    // Add new tree function to CONTENIDO Extension Chainer
    $_cecRegistry->addChainFunction('Contenido.Action.str_newtree.AfterCall', 'mr_strNewTree');

    // Add move subtree function to CONTENIDO Extension Chainer
    $_cecRegistry->addChainFunction('Contenido.Action.str_movesubtree.AfterCall', 'mr_strMoveSubtree');

    // Add new category function to CONTENIDO Extension Chainer
    $_cecRegistry->addChainFunction('Contenido.Action.str_newcat.AfterCall', 'mr_strNewCategory');

    // Add rename category function to CONTENIDO Extension Chainer
    $_cecRegistry->addChainFunction('Contenido.Action.str_renamecat.AfterCall', 'mr_strRenameCategory');

    // Add move up category function to CONTENIDO Extension Chainer
    $_cecRegistry->addChainFunction('Contenido.Action.str_moveupcat.AfterCall', 'mr_strMoveUpCategory');

    // Add move down category function to CONTENIDO Extension Chainer
    $_cecRegistry->addChainFunction('Contenido.Action.str_movedowncat.AfterCall', 'mr_strMovedownCategory');

    // Add copy category function to CONTENIDO Extension Chainer
    $_cecRegistry->addChainFunction('Contenido.Category.strCopyCategory', 'mr_strCopyCategory');

    // Add category sync function to CONTENIDO Extension Chainer
    $_cecRegistry->addChainFunction('Contenido.Category.strSyncCategory_Loop', 'mr_strSyncCategory');

    // Add save article (new and existing category) function to CONTENIDO Extension Chainer
    $_cecRegistry->addChainFunction('Contenido.Action.con_saveart.AfterCall', 'mr_conSaveArticle');

    // Add move article function to CONTENIDO Extension Chainer
    $_cecRegistry->addChainFunction('Contenido.Article.conMoveArticles_Loop', 'mr_conMoveArticles');

    // Add duplicate article function to CONTENIDO Extension Chainer
    $_cecRegistry->addChainFunction('Contenido.Article.conCopyArtLang_AfterInsert', 'mr_conCopyArtLang');

    // Add sync article function to CONTENIDO Extension Chainer
    $_cecRegistry->addChainFunction('Contenido.Article.conSyncArticle_AfterInsert', 'mr_conSyncArticle');

    if (!cRegistry::getBackendSessionId()) {
        // We are not in backend, add cec functions for rewriting

        $requestIdArt = cRegistry::getArticleId($_REQUEST['idart'] ?? '0');
        $requestIdCat = cRegistry::getArticleId($_REQUEST['idcat'] ?? '0');

        if ($requestIdArt <= 0 && $requestIdCat <= 0) {
            // Submitted idart and idcat vars have a higher priority than submitted seo url
            // Add mr related function for hook "after plugins loaded" to CONTENIDO Extension Chainer
            $_cecRegistry->addChainFunction('Contenido.Frontend.AfterLoadPlugins', 'mr_runFrontendController');
        }

        // Overwrite url builder configuration with own url builder
        $cfg['url_builder']['name'] = 'MR';
        $cfg['config'] = [];
        cUriBuilderConfig::setConfig($cfg['url_builder']);

        if ($aMrCfg['rewrite_urls_at_congeneratecode'] == 1) {
            // Add url rewriting at code generation to CONTENIDO Extension Chainer
            $_cecRegistry->addChainFunction('Contenido.Content.conGenerateCode', 'mr_buildGeneratedCode');
        } elseif ($aMrCfg['rewrite_urls_at_front_content_output'] == 1) {
            // Add url rewriting at html output to CONTENIDO Extension Chainer
            $_cecRegistry->addChainFunction('Contenido.Frontend.HTMLCodeOutput', 'mr_buildGeneratedCode');
        } else {
            // Fallback solution: Add url rewriting at code generation to CONTENIDO Extension Chainer
            $_cecRegistry->addChainFunction('Contenido.Content.conGenerateCode', 'mr_buildGeneratedCode');
        }
    }
}

if (cRegistry::getBackendSessionId() && $area === 'mod_rewrite_test') {
    // Configure url builder to enable it on test page
    $cfg['url_builder']['name'] = 'MR';
    $cfg['config'] = [];
    cUriBuilderConfig::setConfig($cfg['url_builder']);
    ModRewrite::setEnabled(true);
}

// Activate the plugin in the meta section to display the correct link
if (cRegistry::getBackendSessionId() && $area === 'con_meta' && ModRewrite::isEnabled()) {
    $cfg['url_builder']['name'] = 'MR';
    $cfg['config'] = [];
    cUriBuilderConfig::setConfig($cfg['url_builder']);
}

unset($pluginName, $pluginClassesPath, $clientId);
