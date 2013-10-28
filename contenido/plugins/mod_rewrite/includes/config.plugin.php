<?php
/**
 * Plugin Advanced Mod Rewrite initialization file.
 *
 * This file will be included by CONTENIDO plugin loader routine, and the content
 * of this file ensures that the AMR Plugin will be initialized correctly.
 *
 * @package     plugin
 * @subpackage  Mod Rewrite
 * @version     SVN Revision $Rev:$
 * @id          $Id$:
 * @author      Murat Purc <murat@purc.de>
 * @copyright   four for business AG <www.4fb.de>
 * @license     http://www.contenido.org/license/LIZENZ.txt
 * @link        http://www.4fb.de
 * @link        http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

global $_cecRegistry, $cfg, $contenido, $area, $client, $load_client;

####################################################################################################
/**
 * Chain Contenido.Frontend.CreateURL
 * This chain is called inside some scripts (front_content.php) to create urls.
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
$_cecRegistry->registerChain("Contenido.Frontend.CreateURL", "string");
####################################################################################################
// initialize client id
if (isset($client) && (int) $client > 0) {
    $clientId = (int) $client;
} elseif (isset($load_client) && (int) $load_client > 0) {
    $clientId = (int) $load_client;
} else {
    $clientId = '';
}


// include necessary sources
cInclude('classes', 'Debug/DebuggerFactory.class.php');

plugin_include('mod_rewrite', 'classes/controller/class.modrewrite_controller_abstract.php');
plugin_include('mod_rewrite', 'classes/controller/class.modrewrite_content_controller.php');
plugin_include('mod_rewrite', 'classes/controller/class.modrewrite_contentexpert_controller.php');
plugin_include('mod_rewrite', 'classes/controller/class.modrewrite_contenttest_controller.php');
plugin_include('mod_rewrite', 'classes/class.modrewritebase.php');
plugin_include('mod_rewrite', 'classes/class.modrewrite.php');
plugin_include('mod_rewrite', 'classes/class.modrewritecontroller.php');
plugin_include('mod_rewrite', 'classes/class.modrewritedebugger.php');
plugin_include('mod_rewrite', 'classes/class.modrewritetest.php');
plugin_include('mod_rewrite', 'classes/class.modrewriteurlstack.php');
plugin_include('mod_rewrite', 'classes/class.modrewriteurlutil.php');
plugin_include('mod_rewrite', 'includes/functions.mod_rewrite.php');


global $lngAct;

$lngAct['mod_rewrite']['mod_rewrite'] = i18n("Advanced Mod Rewrite", "mod_rewrite");
$lngAct['mod_rewrite']['mod_rewrite_expert'] = i18n("Advanced Mod Rewrite functions", "mod_rewrite");
$lngAct['mod_rewrite']['mod_rewrite_test'] = i18n("Advanced Mod Rewrite test", "mod_rewrite");


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
        // Add mr related function for hook "after plugins loaded" to CONTENIDO Extension Chainer
        $_cecRegistry->addChainFunction('Contenido.Frontend.AfterLoadPlugins', 'mr_runFrontendController');

        // Add url rewriting function to CONTENIDO Extension Chainer
        // @todo: no more need since CONTENIDO 4.8.9 provides central Url building,
        //        but it is still available  because of downwards compatibility
        // @deprecated
        $_cecRegistry->addChainFunction('Contenido.Frontend.CreateURL', 'mr_buildNewUrl');

        // overwrite url builder configuration with own url builder
        $cfg['url_builder']['name'] = 'MR';
        $cfg['config'] = array();
        cInclude('classes', 'Url/Contenido_Url.class.php');
        cInclude('classes', 'UrlBuilder/Contenido_UrlBuilderConfig.class.php');
        Contenido_UrlBuilderConfig::setConfig($cfg['url_builder']);

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
    Contenido_UrlBuilderConfig::setConfig($cfg['url_builder']);
    ModRewrite::setEnabled(true);
}

unset($clientId, $options);
