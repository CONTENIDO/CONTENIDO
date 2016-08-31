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
 * @license     http://www.contenido.org/license/LIZENZ.txt
 * @link        http://www.4fb.de
 * @link        http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $_cecRegistry, $cfg, $contenido, $area, $client, $load_client;

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
// initialize client id
if (isset($client) && (int) $client > 0) {
    $clientId = (int) $client;
} elseif (isset($load_client) && (int) $load_client > 0) {
    $clientId = (int) $load_client;
} else {
    $clientId = '';
}


// include necessary sources, setup autoloader for plugin
// @todo Use config variables for $pluginClassPath below!
$pluginClassPath = 'contenido/plugins/mod_rewrite/classes/';
cAutoload::addClassmapConfig(array(
    'ModRewrite_ControllerAbstract' => $pluginClassPath . 'controller/class.modrewrite_controller_abstract.php',
    'ModRewrite_ContentController' => $pluginClassPath . 'controller/class.modrewrite_content_controller.php',
    'ModRewrite_ContentExpertController' => $pluginClassPath . 'controller/class.modrewrite_contentexpert_controller.php',
    'ModRewrite_ContentTestController' => $pluginClassPath . 'controller/class.modrewrite_contenttest_controller.php',
    'ModRewriteBase' => $pluginClassPath . 'class.modrewritebase.php',
    'ModRewrite' => $pluginClassPath . 'class.modrewrite.php',
    'ModRewriteController' => $pluginClassPath . 'class.modrewritecontroller.php',
    'ModRewriteDebugger' => $pluginClassPath . 'class.modrewritedebugger.php',
    'ModRewriteTest' => $pluginClassPath . 'class.modrewritetest.php',
    'ModRewriteUrlStack' => $pluginClassPath . 'class.modrewriteurlstack.php',
    'ModRewriteUrlUtil' => $pluginClassPath . 'class.modrewriteurlutil.php'
));
unset($pluginClassPath);
plugin_include('mod_rewrite', 'includes/functions.mod_rewrite.php');


global $lngAct;

$lngAct['mod_rewrite']['mod_rewrite'] = i18n('Advanced Mod Rewrite', 'mod_rewrite');
$lngAct['mod_rewrite']['mod_rewrite_expert'] = i18n('Advanced Mod Rewrite functions', 'mod_rewrite');
$lngAct['mod_rewrite']['mod_rewrite_test'] = i18n('Advanced Mod Rewrite test', 'mod_rewrite');


// set debug configuration
if (isset($contenido)) {
    ModRewriteDebugger::setEnabled(true);
} else {
    ModRewriteDebugger::setEnabled(false);
}

// initialize mr plugin
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

    if (!isset($contenido)) {
        // we are not in backend, add cec functions for rewriting

        if ((int)$_GET['idart'] == 0 && (int)$_GET['idcat'] == 0 && (int)$_POST['idart'] == 0 && (int)$_POST['idcat'] == 0) {
            // submitted idart and idcat vars has a higher priority than submitted seo url
            // Add mr related function for hook "after plugins loaded" to CONTENIDO Extension Chainer
            $_cecRegistry->addChainFunction('Contenido.Frontend.AfterLoadPlugins', 'mr_runFrontendController');
        }

        // Add url rewriting function to CONTENIDO Extension Chainer
        // @todo: no more need since CONTENIDO 4.8.9 provides central Url building,
        //        but it is still available  because of downwards compatibility
        // @deprecated
        $_cecRegistry->addChainFunction('Contenido.Frontend.CreateURL', 'mr_buildNewUrl');

        // overwrite url builder configuration with own url builder
        $cfg['url_builder']['name'] = 'MR';
        $cfg['config'] = array();
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

if (isset($contenido) && isset($area) && $area == 'mod_rewrite_test') {
    // configure url builder to enable it on test page
    $cfg['url_builder']['name'] = 'MR';
    $cfg['config'] = array();
    cUriBuilderConfig::setConfig($cfg['url_builder']);
    ModRewrite::setEnabled(true);
}

//activate the plugin in the meta section to display the correct link
if (isset($contenido) && isset($area) && $area == 'con_meta' && ModRewrite::isEnabled()) {
    $cfg['url_builder']['name'] = 'MR';
    $cfg['config'] = array();
    cUriBuilderConfig::setConfig($cfg['url_builder']);
}

unset($clientId);
