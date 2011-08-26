<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Plugin Advanced Mod Rewrite initialization file.
 *
 * This file will be included by Contenido plugin loader routine, and the content
 * of this file ensures that the AMR Plugin will be initialized correctly.
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
 * @since      file available since Contenido release 4.9.0
 *
 * {@internal
 *   created  2008-05-xx
 *
 *   $Id$:
 * }}
 *
 */


defined('CON_FRAMEWORK') or die('Illegal call');


####################################################################################################
/**
 * Chain Contenido.Frontend.CreateURL
 * This chain is called inside some scripts (front_content.php) to create urls.
 *
 * @todo: Is added to provide downwards compatibility for the amr plugin.
 *        There is no need for this chain since Contenido 4.8.9 contains its own Url building feature.
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


global $cfg, $contenido, $mr_statics;

// used for caching
$mr_statics = array();

// initialize client id
if (isset($client) && (int) $client > 0) {
    $clientId = (int) $client;
} elseif (isset($load_client) && (int) $load_client > 0) {
    $clientId = (int) $load_client;
} else {
    $clientId = '';
}


// include necessary sources
plugin_include('mod_rewrite', 'classes/class.modrewritedebugger.php');
plugin_include('mod_rewrite', 'classes/class.modrewritebase.php');
plugin_include('mod_rewrite', 'classes/class.modrewrite.php');
plugin_include('mod_rewrite', 'classes/class.modrewritecontroller.php');
plugin_include('mod_rewrite', 'classes/class.modrewriteurlstack.php');
plugin_include('mod_rewrite', 'classes/class.modrewriteurlutil.php');
plugin_include('mod_rewrite', 'includes/functions.mod_rewrite.php');


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

    $_cecRegistry = cApiCECRegistry::getInstance();

    // Add new tree function to Contenido Extension Chainer
    $_cecRegistry->addChainFunction('Contenido.Action.str_newtree.AfterCall', 'mr_strNewTree');

    // Add move subtree function to Contenido Extension Chainer
    $_cecRegistry->addChainFunction('Contenido.Action.str_movesubtree.AfterCall', 'mr_strMoveSubtree');

    // Add new category function to Contenido Extension Chainer
    $_cecRegistry->addChainFunction('Contenido.Action.str_newcat.AfterCall', 'mr_strNewCategory');

    // Add rename category function to Contenido Extension Chainer
    $_cecRegistry->addChainFunction('Contenido.Action.str_renamecat.AfterCall', 'mr_strRenameCategory');

    // Add move up category function to Contenido Extension Chainer
    $_cecRegistry->addChainFunction('Contenido.Action.str_moveupcat.AfterCall', 'mr_strMoveUpCategory');

    // Add move down category function to Contenido Extension Chainer
    $_cecRegistry->addChainFunction('Contenido.Action.str_movedowncat.AfterCall', 'mr_strMovedownCategory');

    // Add copy category function to Contenido Extension Chainer
    $_cecRegistry->addChainFunction('Contenido.Category.strCopyCategory', 'mr_strCopyCategory');

    // Add category sync function to Contenido Extension Chainer
    $_cecRegistry->addChainFunction('Contenido.Category.strSyncCategory_Loop', 'mr_strSyncCategory');

    // Add save article (new and existing category) function to Contenido Extension Chainer
    $_cecRegistry->addChainFunction('Contenido.Action.con_saveart.AfterCall', 'mr_conSaveArticle');

    // Add move article function to Contenido Extension Chainer
    $_cecRegistry->addChainFunction('Contenido.Article.conMoveArticles_Loop', 'mr_conMoveArticles');

    // Add duplicate article function to Contenido Extension Chainer
    $_cecRegistry->addChainFunction('Contenido.Article.conCopyArtLang_AfterInsert', 'mr_conCopyArtLang');

    // Add sync article function to Contenido Extension Chainer
    $_cecRegistry->addChainFunction('Contenido.Article.conSyncArticle_AfterInsert', 'mr_conSyncArticle');

    if (!isset($contenido)) {
        // we are not in backend, add cec functions for rewriting

        // Add mr related function for hook "after plugins loaded" to Contenido Extension Chainer
        $_cecRegistry->addChainFunction('Contenido.Frontend.AfterLoadPlugins', 'mr_runFrontendController');

        // Add url rewriting function to Contenido Extension Chainer
        // @todo: no more need since Contenido 4.8.9 provides central Url building,
        //        but it is still available  because of downwards compatibility
        // @deprecated
        $_cecRegistry->addChainFunction('Contenido.Frontend.CreateURL', 'mr_buildNewUrl');

        // overwrite url builder configuration with own url builder
        $cfg['url_builder']['name'] = 'MR';
        $cfg['config']              = array();
        Contenido_UrlBuilderConfig::setConfig($cfg['url_builder']);

        if ($aMrCfg['rewrite_urls_at_congeneratecode'] == 1) {
            // Add url rewriting at code generation to Contenido Extension Chainer
            $_cecRegistry->addChainFunction('Contenido.Content.conGenerateCode', 'mr_buildGeneratedCode');
        } elseif ($aMrCfg['rewrite_urls_at_front_content_output'] == 1) {
            // Add url rewriting at html output to Contenido Extension Chainer
            $_cecRegistry->addChainFunction('Contenido.Frontend.HTMLCodeOutput', 'mr_buildGeneratedCode');
        } else {
            // Fallback solution: Add url rewriting at code generation to Contenido Extension Chainer
            $_cecRegistry->addChainFunction('Contenido.Content.conGenerateCode', 'mr_buildGeneratedCode');
        }
    }

}

if (isset($contenido) && isset($area) && $area == 'mod_rewrite_test') {
    // configure url builder to enable it on test page
    $cfg['url_builder']['name'] = 'MR';
    $cfg['config']              = array();
    Contenido_UrlBuilderConfig::setConfig($cfg['url_builder']);
    ModRewrite::setEnabled(true);
}

unset($clientId, $options);
