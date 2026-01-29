<?php

/**
 * Advanced Mod Rewrite front_content.php controller. Does some preprocessing jobs, tries to set the
 * following variables, depending on mod rewrite configuration and if the request part exists:
 * - $client
 * - $changeclient
 * - $lang
 * - $changelang
 * - $idart
 * - $idcat
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

// NOTE: Use global here, the will be updated with the values from the `PiModRewriteFrontContentService`.
global $client, $changeclient, $lang, $changelang, $idart, $idcat, $path;

$client = cRegistry::getClientId();
$cfgClient = cRegistry::getClientConfig();
$lang = cRegistry::getLanguageId();
$idart = cRegistry::getArticleId();
$idcat = cRegistry::getCategoryId();

PiModRewriteDebugger::add(PiModRewrite::getConfig(), basename(__FILE__) . ' mod rewrite config');

// Run the URL resolving process
$mrFcService = new PiModRewriteFrontContentService($_SERVER['REQUEST_URI'] ?? '');
$mrFcService->execute();

if ($mrFcService->isError()) {
    // Some error occurred (idcat and or idart couldn't be resolved)

    $redirectToErrorPage = PiModRewrite::getConfig('redirect_invalid_article_to_errorsite', 0);
    // try to redirect to the error page if desired
    if ($redirectToErrorPage == 1 && $client > 0 && $lang > 0) {
        // error page
        $errsite = 'Location: ' . cUri::getInstance()->buildRedirect([
            'client' => $client,
            'idcat' => $cfgClient[$client]['errsite']['idcat'],
            'idart' => $cfgClient[$client]['errsite']['idart'],
            'lang' => $lang,
            'error' => '1'
        ]);
        PiModRewriteUtil::responseHeader($errsite);
        exit();
    }
} else {
    // Set some global variables

    if ($mrFcService->getClient()) {
        $client = $mrFcService->getClient();
    }

    if ($mrFcService->getChangeClient()) {
        $changeclient = $mrFcService->getChangeClient();
    }

    if ($mrFcService->getLang()) {
        $lang = $mrFcService->getLang();
    }

    if ($mrFcService->getChangeLang()) {
        $changelang = $mrFcService->getChangeLang();
    }

    if ($mrFcService->getIdArt()) {
        $idart = $mrFcService->getIdArt();
    }

    if ($mrFcService->getIdCat()) {
        $idcat = $mrFcService->getIdCat();
    }

    if ($mrFcService->getPath()) {
        $path = $mrFcService->getPath();
    }
}

// Debug stuff
if ($mrFcService->getError()) {
    PiModRewriteDebugger::add($mrFcService->getError(), basename(__FILE__). ' error');
}
PiModRewriteDebugger::add($idart, basename(__FILE__) . ' $idart');
PiModRewriteDebugger::add($idcat, basename(__FILE__) . ' $idcat');
PiModRewriteDebugger::add($lang, basename(__FILE__) . ' $lang');
PiModRewriteDebugger::add($client, basename(__FILE__) . ' $client');

