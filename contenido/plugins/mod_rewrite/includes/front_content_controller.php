<?php

/**
 * Mod Rewrite front_content.php controller. Does some preprocessing jobs, tries
 * to set following variables, depending on mod rewrite configuration and if
 * request part exists:
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

global $changeclient, $changelang, $path, $mr_preprocessedPageError;

$client = cRegistry::getClientId();
$cfgClient = cRegistry::getClientConfig();
$lang = cRegistry::getLanguageId();
$idart = cRegistry::getArticleId();
$idcat = cRegistry::getCategoryId();

ModRewriteDebugger::add(ModRewrite::getConfig(), 'front_content_controller.php mod rewrite config');

// get REQUEST_URI
$requestUri = $_SERVER['REQUEST_URI'] ?? '';

// create a mod rewrite controller instance and execute processing
$mrController = new ModRewriteController($requestUri);
$mrController->execute();

if ($mrController->isError()) {
    // an error occurred (idcat and or idart couldn't catch by controller)

    $redirectToErrorPage = ModRewrite::getConfig('redirect_invalid_article_to_errorsite', 0);
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
        mr_header($errsite);
        exit();
    }
} else {

    // TODO The code below has no effect. The `front_content_controller.php` runs within a function scope,
    //      it dosen't modify the global variables, see the import of globals at the top of this file.
    //      This should be checked and adjusted.

    // set some global variables

    if ($mrController->getClient()) {
        $client = $mrController->getClient();
    }

    if ($mrController->getChangeClient()) {
        $changeclient = $mrController->getChangeClient();
    }

    if ($mrController->getLang()) {
        $lang = $mrController->getLang();
    }

    if ($mrController->getChangeLang()) {
        $changelang = $mrController->getChangeLang();
    }

    if ($mrController->getIdArt()) {
        $idart = $mrController->getIdArt();
    }

    if ($mrController->getIdCat()) {
        $idcat = $mrController->getIdCat();
    }

    if ($mrController->getPath()) {
        $path = $mrController->getPath();
    }
}

// some debugs
ModRewriteDebugger::add($mr_preprocessedPageError, 'mr $mr_preprocessedPageError');
if ($mrController->getError()) {
    ModRewriteDebugger::add($mrController->getError(), 'mr error');
}
ModRewriteDebugger::add($idart, 'mr $idart');
ModRewriteDebugger::add($idcat, 'mr $idcat');
ModRewriteDebugger::add($lang, 'mr $lang');
ModRewriteDebugger::add($client, 'mr $client');

