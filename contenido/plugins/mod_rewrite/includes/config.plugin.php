<?php

/**
 * Plugin Advanced Mod Rewrite initialization file.
 *
 * This file will be included by CONTENIDO plugin loader routine, and the content
 * of this file ensures that the AMR Plugin will be initialized correctly.
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

global $cfg, $lngAct;

$contenido = cRegistry::getBackendSessionId();
$area = cRegistry::getArea();
$client = cRegistry::getClientId();

// Initialize client id
if ($client > 0) {
    $clientId = $client;
} elseif (cRegistry::getLoadClientId() > 0) {
    $clientId = cRegistry::getLoadClientId();
} else {
    $clientId = '';
}

$pluginName = basename(dirname(__DIR__));

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
    'PiModRewrite' => $pluginClassesPath . '/PiModRewrite.php',
    'PiModRewriteBase' => $pluginClassesPath . '/PiModRewriteBase.php',
    'PiModRewriteConfigurationService' => $pluginClassesPath . '/Service/PiModRewriteConfigurationService.php',
    'PiModRewriteContentController' => $pluginClassesPath . '/Controller/PiModRewriteContentController.php',
    'PiModRewriteControllerAbstract' => $pluginClassesPath . '/Controller/PiModRewriteControllerAbstract.php',
    'PiModRewriteDatabaseUtil' => $pluginClassesPath . '/Util/PiModRewriteDatabaseUtil.php',
    'PiModRewriteDebugger' => $pluginClassesPath . '/Util/PiModRewriteDebugger.php',
    'PiModRewriteExpertController' => $pluginClassesPath . '/Controller/PiModRewriteExpertController.php',
    'PiModRewriteFrontContentService' => $pluginClassesPath . '/Service/PiModRewriteFrontContentService.php',
    'PiModRewriteRequestUtil' => $pluginClassesPath . '/Util/PiModRewriteRequestUtil.php',
    'PiModRewriteTestController' => $pluginClassesPath . '/Controller/PiModRewriteTestController.php',
    'PiModRewriteTestService' => $pluginClassesPath . '/Service/PiModRewriteTestService.php',
    'PiModRewriteUrlStackService' => $pluginClassesPath . '/Service/PiModRewriteUrlStackService.php',
    'PiModRewriteUrlUtil' => $pluginClassesPath . '/Util/PiModRewriteUrlUtil.php',
    'PiModRewriteUtil' => $pluginClassesPath . '/Util/PiModRewriteUtil.php',
    'PiModRewritePrettyUrlDto' => $pluginClassesPath . '/Dto/PiModRewritePrettyUrlDto.php',
    'PiModRewriteResolvedUrlDto' => $pluginClassesPath . '/Dto/PiModRewriteResolvedUrlDto.php',
]);

plugin_include($pluginName, 'includes/functions.mod_rewrite.php');

// Set debug configuration
PiModRewriteDebugger::setEnabled(!empty(cRegistry::getBackendSessionId()));

// Initialize mr plugin
PiModRewrite::initialize(cSecurity::toInteger($clientId));

if (PiModRewrite::isEnabled()) {
    $aMrCfg = PiModRewrite::getConfig();

    $cecRegistry = cApiCecRegistry::getInstance();

    // Add the chain functions to the CONTENIDO Extension Chainer
    $cecRegistry->addChainFunction(
        'Contenido.Action.str_newtree.AfterCall',
        'PiModRewriteUtil::strNewTree'
    );

    $cecRegistry->addChainFunction(
        'Contenido.Action.str_movesubtree.AfterCall',
        'PiModRewriteUtil::strMoveSubtree'
    );

    $cecRegistry->addChainFunction(
        'Contenido.Action.str_newcat.AfterCall',
        'PiModRewriteUtil::strNewCategory'
    );

    $cecRegistry->addChainFunction(
        'Contenido.Action.str_renamecat.AfterCall',
        'PiModRewriteUtil::strRenameCategory'
    );

    $cecRegistry->addChainFunction(
        'Contenido.Action.str_moveupcat.AfterCall',
        'PiModRewriteUtil::strMoveUpCategory'
    );

    $cecRegistry->addChainFunction(
        'Contenido.Action.str_movedowncat.AfterCall',
        'PiModRewriteUtil::strMovedownCategory'
    );

    $cecRegistry->addChainFunction(
        'Contenido.Category.strCopyCategory',
        'PiModRewriteUtil::strCopyCategory'
    );

    $cecRegistry->addChainFunction(
        'Contenido.Category.strSyncCategory_Loop',
        'PiModRewriteUtil::strSyncCategory'
    );

    $cecRegistry->addChainFunction(
        'Contenido.Action.con_saveart.AfterCall',
        'PiModRewriteUtil::conSaveArticle'
    );

    $cecRegistry->addChainFunction(
        'Contenido.Article.conMoveArticles_Loop',
        'PiModRewriteUtil::conMoveArticles'
    );

    $cecRegistry->addChainFunction(
        'Contenido.Article.conCopyArtLang_AfterInsert',
        'PiModRewriteUtil::conCopyArtLang'
    );

    $cecRegistry->addChainFunction(
        'Contenido.Article.conSyncArticle_AfterInsert',
        'PiModRewriteUtil::conSyncArticle'
    );

    if (!cRegistry::getBackendSessionId()) {
        // We are not in backend, add cec functions for rewriting
        $requestIdArt = cRegistry::getArticleId($_REQUEST['idart'] ?? '0');
        $requestIdCat = cRegistry::getCategoryId($_REQUEST['idcat'] ?? '0');

        if ($requestIdArt <= 0 && $requestIdCat <= 0) {
            // Submitted idart and idcat vars have a higher priority than submitted seo url
            // Add mr related function for hook "after plugins loaded" to CONTENIDO Extension Chainer
            $cecRegistry->addChainFunction(
                'Contenido.Frontend.AfterLoadPlugins',
                'PiModRewriteUtil::runFrontendController'
            );
        }

        // Overwrite url builder configuration with own url builder
        $cfg['url_builder']['name'] = 'MR';
        $cfg['config'] = [];
        cUriBuilderConfig::setConfig($cfg['url_builder']);

        // Add further chain functions to the CONTENIDO Extension Chainer depending on configuration.
        if ($aMrCfg['rewrite_urls_at_congeneratecode'] == 1) {
            $cecRegistry->addChainFunction(
                'Contenido.Content.conGenerateCode',
                'PiModRewriteUtil::buildGeneratedCode'
            );
        } elseif ($aMrCfg['rewrite_urls_at_front_content_output'] == 1) {
            $cecRegistry->addChainFunction(
                'Contenido.Frontend.HTMLCodeOutput',
                'PiModRewriteUtil::buildGeneratedCode'
            );
        } else {
            $cecRegistry->addChainFunction(
                'Contenido.Content.conGenerateCode',
                'PiModRewriteUtil::buildGeneratedCode'
            );
        }
    }
}

if (cRegistry::getBackendSessionId() && $area === 'mod_rewrite_test') {
    // Configure url builder to enable it on the test page
    $cfg['url_builder']['name'] = 'MR';
    $cfg['config'] = [];
    cUriBuilderConfig::setConfig($cfg['url_builder']);
    PiModRewrite::setEnabled(true);
}

// Activate the plugin in the meta-section to display the correct link
if (cRegistry::getBackendSessionId() && $area === 'con_meta' && PiModRewrite::isEnabled()) {
    $cfg['url_builder']['name'] = 'MR';
    $cfg['config'] = [];
    cUriBuilderConfig::setConfig($cfg['url_builder']);
}

unset($pluginName, $pluginClassesPath, $clientId);
