<?php

/**
 * This file handles the view of an article in frontend and in backend.
 * To handle the page we use the Database Abstraction Layer, the Session,
 * Authentication and Permissions Handler.
 * The Client Id and the Language Id of an article will be determined depending
 * on file __FRONTEND_PATH__/data/config/config.php where
 * $load_lang and $load_client are defined.
 * Depending on http globals via e.g. front_content.php?idcat=41&idart=34
 * the most important CONTENIDO globals $idcat (Category Id), $idart (Article
 * Id), $idcatart, $idartlang will be determined.
 * The article can be displayed and edited in the Backend or the Frontend.
 * The attributes of an article will be considered (an article can be online,
 * offline or protected ...).
 * It is possible to customize the behavior by including the file
 * __FRONTEND_PATH__/data/config/config.local.php or
 * the file __FRONTEND_PATH__/data/config/config.after.php
 * If you use 'Frontend User' for protected areas, the category access
 * permission will by handled via the
 * CONTENIDO Extension Chainer.
 * Finally the 'code' of an article will by evaluated and displayed.
 * NOTE:
 * This file has to run in clients frontend directory!
 *
 * @package    Core
 * @subpackage Frontend
 * @author     Olaf Niemann
 * @author     Jan Lengowski
 * @author     Timo A. Hummel
 * @author     et al.
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// if we are in the frontend and no clients are configured, display an error
if (!isset($contenido)) {
    if (!isset($cfgClient["set"])) {
        echo("CONTENIDO is not configured properly. More details can be found in the error log");
        cError("Could not include config.clients.php. Make sure it exists and has a valid configuration!");
    }
}

global $cfg, $belang, $force, $load_client;

// Initialize common variables
$idcat    = $idcat ?? 0;
$idart    = $idart ?? 0;
$idcatart = $idcatart ?? 0;
$error    = $error ?? 0;

cInclude('includes', 'functions.con.php');
cInclude('includes', 'functions.con2.php');
cInclude('includes', 'functions.api.php');
cInclude('includes', 'functions.pathresolver.php');

$backendPath = cRegistry::getBackendPath();
$backendUrl  = cRegistry::getBackendUrl();

// Include cronjob-Emulator
if ($cfg['use_pseudocron'] == true) {
    $currentWorkingDirectory = getcwd();
    chdir($backendPath . $cfg['path']['cronjobs']);
    cInclude('includes', 'pseudo-cron.inc.php');
    chdir($currentWorkingDirectory);
}

// Initialize the database abstraction layer, the session, authentication and
// permissions handler of the PHPLIB application development toolkit
// @see http://sourceforge.net/projects/phplib
if (cRegistry::getBackendSessionId()) {
    // Backend
    cRegistry::bootstrap([
        'sess' => 'cSession',
        'auth' => 'cAuthHandlerBackend',
        'perm' => 'cPermission'
    ]);
    i18nInit($cfg['path']['contenido_locale'], $belang);
} else {
    // Frontend
    cRegistry::bootstrap([
        'sess' => 'cFrontendSession',
        'auth' => 'cAuthHandlerFrontend',
        'perm' => 'cPermission'
    ]);
}

// Include plugins & call hook after plugins are loaded
require_once($backendPath . $cfg['path']['includes'] . 'functions.includePluginConf.php');
cApiCecHook::execute('Contenido.Frontend.AfterLoadPlugins');

$db   = cRegistry::getDb();
$sess = cRegistry::getSession();
$lang = cRegistry::getLanguageId();
$auth = cRegistry::getAuth();
$perm = cRegistry::getPerm();

// $sess->register('cfgClient');
// $sess->register('errsite_idcat');
// $sess->register('errsite_idart');
$sess->register('encoding');

// get encodings of all languages
if (!isset($encoding) || !is_array($encoding) || count($encoding) == 0) {
    $oLangColl = new cApiLanguageCollection();
    $oLangColl->select('');
    $encoding = [];
    while ($oLang = $oLangColl->next()) {
        $encoding[$oLang->get('idlang')] = $oLang->get('encoding');
    }
}

// Check frontend globals
// TODO Should be outsourced into startup process but requires a better detection (frontend or backend)
if (isset($tmpchangelang) && $tmpchangelang > 0) {
    // savelang is needed to set language before closing the page, see
    // {frontend_clientdir}/front_content.php before cRegistry::shutdown()
    $savedlang = $lang;
    $lang      = $tmpchangelang;
}

// Change client
if (isset($changeclient)) {
    $client = $changeclient;
    unset($lang);
    unset($load_lang);
}

// Change language
if (isset($changelang)) {
    $lang = $changelang;
}

// Initialize client
if (!isset($client)) {
    // load_client defined in __FRONTEND_PATH__/data/config/config.php
    $client = $load_client;
}

// Update UriBuilder, set http base path
cUri::getInstance()->getUriBuilder()->setHttpBasePath(cRegistry::getFrontendUrl());

// Initialize language
if (!isset($lang)) {
    // If there is an entry load_lang in
    // __FRONTEND_PATH__/data/config/config.php use it, else use the first
    // language of this client
    if (isset($load_lang)) {
        // load_client is set in __FRONTEND_PATH__/data/config/config.php
        $lang = $load_lang;
    } else {
        $oClientLangColl = new cApiClientLanguageCollection();
        $lang = (int) $oClientLangColl->getFirstLanguageIdByClient($client);
    }
}

if (!$sess->isRegistered('lang')) {
    $sess->register('lang');
}

if (!$sess->isRegistered('client')) {
    $sess->register('client');
}

if (isset($username)) {
    $auth->restart();
}

// check if category ID is empty (like in lost and found)
if (!$idcat) {
    $idcat = 0;
}

// Send HTTP header with encoding
header("Content-Type: text/html; charset={$encoding[$lang]}");

// If http global logout is set e.g. front_content.php?logout=true, log out the
// current user.
if (isset($logout)) {
    $auth->logout(true);
    $auth->resetAuthInfo(true);
    $auth->auth['uname'] = 'nobody';
}

// If the path variable was passed, try to resolve it to a category id,
// e.g. front_content.php?path=/company/products/
if (isset($path) && cString::getStringLength($path) > 1) {
    // Which resolve method is configured?
    if ($cfg['urlpathresolve'] == true) {
        $idcat = prResolvePathViaURLNames($path);
    } else {
        $iLangCheck = 0;
        $idcat      = prResolvePathViaCategoryNames($path, $iLangCheck);
        if (($lang != $iLangCheck) && ((int)$iLangCheck != 0)) {
            $lang = $iLangCheck;
        }
    }
}

// Error page
$aParams = [
    'client' => $client,
    'idcat'  => $cfgClient[$client]["errsite"]["idcat"],
    'idart'  => $cfgClient[$client]["errsite"]["idart"],
    'lang'   => $lang,
    'error'  => '1'
];
$errsite = 'Location: ' . cUri::getInstance()->buildRedirect($aParams);

$errtpl = $cfgClient[$client]['tpl']['path'] . "frontend_error.html";
if (cFileHandler::exists($errtpl) === false) {
    $errtpl = $cfg['path']['contenido'] . "templates/frontend_error.html";
}

if ($error == 1) {
    header("HTTP/1.0 404 Not found");
}

// Try to initialize variables $idcat, $idart, $idcatart, $idartlang
// Note: These variables can be set via http globals e.g.
// front_content.php?idcat=41&idart=34&idcatart=35&idartlang=42
// If not, the values will be computed.
if ($idart && !$idcat && !$idcatart) {
    // Try to fetch the idcat by idart
    $catArtColl = new cApiCategoryArticleCollection();
    $categories = $catArtColl->getCategoryIdsByArticleId($idart);
    $idcat      = $categories[0];
}

unset($code, $markscript);

if ($idcatart) {
    // Try to fetch article and category id by idcatart
    $oCatArt = new cApiCategoryArticle((int)$idcatart);
    if ($oCatArt->isLoaded()) {
        $idcat = $oCatArt->get('idcat');
        $idart = $oCatArt->get('idart');
    }
} elseif (!$idart) {
    if (!$idcat) {
        // Try to get caetgory and article id of first item in current
        // clients tree structure
        $oCatArtColl = new cApiCategoryArticleCollection();
        $oCatArt     = $oCatArtColl->fetchFirstFromTreeByClientIdAndLangId($client, $lang);
        if ($oCatArt) {
            $idart = $oCatArt->get('idart');
            $idcat = $oCatArt->get('idcat');
        } elseif ($contenido) {
            cInclude('includes', 'functions.i18n.php');
            die(i18n('No start article for this category'));
        } elseif ($error == 1) {
            $tpl = new cTemplate();
            $tpl->set("s", "ERROR_TITLE", "Fatal error");
            $tpl->set("s", "ERROR_TEXT", "No start article for this category.");
            $tpl->generate($errtpl);
            exit();
        } else {
            header($errsite);
            exit();
        }
    } else {
        $idart = -1;

        // Try to fetch article by category and language
        $oCatLang = new cApiCategoryLanguage();
        if ($oCatLang->loadByCategoryIdAndLanguageId($idcat, $lang)) {
            if ($oCatLang->get('startidartlang') != 0) {
                $oArtLang = new cApiArticleLanguage($oCatLang->get('startidartlang'));
                $idart    = $oArtLang->get('idart');
            }
        }

        if ($idart != -1) {
            // donut
        } elseif ($contenido) {
            // Error message in backend
            cInclude('includes', 'functions.i18n.php');
            die(i18n('No start article for this category'));
        } elseif ($error == 1) {
            $tpl = new cTemplate();
            $tpl->set("s", "ERROR_TITLE", "Fatal error");
            $tpl->set("s", "ERROR_TEXT", "No start article for this category.");
            $tpl->generate($errtpl);
            exit();
        } else {
            header($errsite);
            exit();
        }
    }
}

// Get idcatart
if (0 != $idart && 0 != $idcat) {
    $oCatArtColl = new cApiCategoryArticleCollection();
    if ($oCatArt = $oCatArtColl->fetchByCategoryIdAndArticleId($idcat, $idart)) {
        $idcatart = $oCatArt->get('idcatart');
    }
}

// Initializing CategoryLanguage class
$oCatLang = new cApiCategoryLanguage();
$oCatLang->loadByCategoryIdAndLanguageId($idcat, $lang);

// Get idartlang
$idartlang = getArtLang($idart, $lang);

// CON-2148
// check if category is online, allow access if article is specified for loading
if (isset($idart)) {
    $oArtLang = new cApiArticleLanguage($idartlang);
    $online   = $oArtLang->get('online');
} else {
    $online = ('0' !== $oCatLang->get('visible'));
}

// always allow editing article in backend
if (!cRegistry::getBackendSessionId() && ($idartlang === false || $online != true)) {
    if ($_GET['display_errorpage']) {
        // show only if $idart > 0
        if ($idart > 0) {
            $tpl = new cTemplate();
            $tpl->set('s', 'CONTENIDO_PATH', $backendUrl);
            $tpl->set('s', 'ERROR_TITLE', i18n('Error page'));
            $tpl->set('s', 'ERROR_TEXT', i18n('Error article/category not found!'));
            $tpl->generate($errtpl);
        }
        exit();
    } else {
        header($errsite . '&display_errorpage=1');
    }
    exit();
}

// Start page cache, if enabled
if ($cfg['cache']['disable'] != '1') {
    cInclude('frontend', 'data/config/' . CON_ENVIRONMENT . '/concache.php');
    $oCacheHandler = new cOutputCacheHandler($GLOBALS['cfgConCache'], $db);
    // $iStartTime ist optional und ist die Startzeit des Scriptes,
    // z.B. am Anfang von front_content.php
    $oCacheHandler->start($iStartTime ?? null);
}

// Backend / Frontend editing

// First we have to figure out if the user is allowed to edit the article.
// We'll check if it's inuse, if they want to edit it and if all plugins allow it.
$inUse = false;
$allow = false;
$view  = false;
if ($contenido) {
    $perm->load_permissions();

    // Change mode edit / view
    if (isset($changeview)) {
        $sess->register('view');
        $view = $changeview;
    }

    $col = new cApiInUseCollection();

    $overrideid   = $overrideid ?? '';
    $overridetype = $overridetype ?? '';
    $type         = $type ?? '';
    $typenr       = $typenr ?? '';

    if ($overrideid != '' && $overridetype != '') {
        $col->removeItemMarks($overridetype, $overrideid);
    }
    // Remove all own marks
    $col->removeSessionMarks($sess->id);
    // If the override flag is set, override a specific cApiInUse

    $inUseUrl = $backendUrl . "external/backendedit/front_content.php?changeview=edit&action=con_editart&idartlang=$idartlang&type=$type&typenr=$typenr&idart=$idart&idcat=$idcat&idcatart=$idcatart&client=$client&lang=$lang";
    list($inUse, $message) = $col->checkAndMark('article', $idartlang, true, i18n('Article is in use by %s (%s)'), true, $inUseUrl);
    $sHtmlInUse        = '';
    $sHtmlInUseMessage = '';

    if ($inUse == true) {
        $disabled          = 'disabled="disabled"';
        $sHtmlInUseCss     = '<link rel="stylesheet" type="text/css" href="' . $backendUrl . 'styles/inuse.css">';
        $sHtmlInUseMessage = $message;
    }

    // Is article locked?
    $oArtLang = new cApiArticleLanguage();
    $oArtLang->loadByArticleAndLanguageId($idart, $lang);
    $locked = $oArtLang->get('locked');
    if ($locked == 1) {
        // admin can edit article despite its locked status
        $isAdmin = cPermission::checkAdminPermission($auth->getPerms());
        if (false === $isAdmin) {
            $notification      = new cGuiNotification();
            $modErrorMessage   = i18n('This article is currently frozen and can not be edited!');
            $inUse             = true;
            $sHtmlInUseCss     = '<link rel="stylesheet" type="text/css" href="' . $backendUrl . 'styles/inuse.css">';
            $sHtmlInUseMessage = $notification->returnMessageBox('warning', $modErrorMessage, 0);
        }
    }

    // do not load erroneous modules
    if ($oArtLang->get('idartlang')) {
        // get id of current container
        $tpl = new cApiTemplate();
        $tpl->loadByArticleOrCategory($idart, $idcat, $lang, $client);
        $idtpl = $tpl->get('idtpl');
        unset($tpl);

        // get ids of modules inside container
        $containerModules = conGetUsedModules($idtpl);
        $erroneousModules = [];
        foreach ($containerModules as $containerModule) {
            $oModule = new cApiModule($containerModule);
            if ($oModule->get('idmod') !== false
                && ($oModule->get('error') !== 'none'
                    || $oModule->get('error') === 'both')
            ) {
                $erroneousModules[] = $oModule->get('name');
            }
        }

        if (isset($view) && $view === 'edit' && count($erroneousModules) > 0) {
            $notification    = new cGuiNotification();
            $modErrorMessage = i18n("The following modules are erroneus and are therefore not executed:<br>\n");
            foreach ($erroneousModules as $erroneousModule) {
                $modErrorMessage .= "- " . $erroneousModule . "<br />\n";
            }
            $inUse             = true;
            $sHtmlInUseCss     = '<link rel="stylesheet" type="text/css" href="' . $backendUrl . 'styles/inuse.css">';
            $sHtmlInUseMessage = $notification->returnMessageBox('error', $modErrorMessage, 0);
        }
    }

    // CEC to check if the user has permission to edit articles in this category
    // break at 'false', default value 'true'
    cApiCecHook::setBreakCondition(false, true);
    $allow = cApiCecHook::executeWhileBreakCondition('Contenido.Frontend.AllowEdit', $lang, $idcat, $idart, $auth->auth['uid']);
}

// check if isset parent category template
// do not show error message if user calls an article explicitly via idart URL parameter
if ($contenido) {
    $sql        = "
        SELECT
            a.idtplcfg
        FROM
            `" . $cfg['tab']['cat_lang'] . "` AS a,
            `" . $cfg['tab']['cat_art'] . "` AS b
        WHERE
            a.idcat = b.idcat
            AND b.idart = $idart
            AND a.idlang = $lang
        ;";
    $errorText  = i18n("Editing/Showing is not possible because there is no template assigned to this category.");
    $errorTitle = i18n("FATAL ERROR");
} else {
    $article    = new cApiArticleLanguage($idartlang);
    $idart      = $article->getField('idart');
    $sql        = "
        SELECT
            a.idtplcfg
        FROM
            `" . $cfg['tab']['cat_lang'] . "` AS a,
            `" . $cfg['tab']['cat_art'] . "` AS b
        WHERE
            a.idcat = b.idcat
            AND b.idart = $idart
            AND a.idlang = $lang
        ;";
    $errorText  = 'Editing/Showing is not possible because there is no template assigned to this category.';
    $errorTitle = 'FATAL ERROR!';
}

$db = cRegistry::getDb();
$db->query($sql);

$data = [];
while ($db->nextRecord()) {
    array_push($data, $db->toArray());
}

if (isset($data[0]) && $data[0]['idtplcfg'] === '0' && !isset($_REQUEST['idart'])) {
    $tpl = new cTemplate();
    $tpl->set("s", "ERROR_TITLE", $errorTitle);
    $tpl->set("s", "ERROR_TEXT", $errorText);
    $tpl->generate($errtpl);
    exit();
}

// handling offline language
$language = cRegistry::getLanguage();
if ($language->get('active') != 1 && (!$contenido || $view != 'edit')) {
    $tpl = new cTemplate();
    $tpl->set("s", "ERROR_TITLE", "Current language is not online");
    $tpl->set("s", "ERROR_TEXT", "You try to view a page of a language, which is not online.");
    $tpl->generate($errtpl);
    exit();
}

// If mode is 'edit' and user has permission to edit articles in the current
// category
if ($inUse == false && $allow == true && $view == 'edit' && ($perm->have_perm_area_action_item('con_editcontent', 'con_editart', $idcat))) {
    cInclude('includes', 'functions.tpl.php');
    include($backendPath . $cfg['path']['includes'] . 'include.con_editcontent.php');
} else {

    // Frontend view
    // Mark submenuitem 'Preview' in the CONTENIDO Backend
    // (Area: Contenido --> Articles --> Preview)
    if ($contenido) {
        $markscript = markSubMenuItem(6, true);
    }

    // disable editmode
    // 'mode' is preview (Area: Contenido --> Articles --> Preview)
    // or article displayed in the front-end code generation
    unset($edit);

    $oCatArtColl = new cApiCategoryArticleCollection();
    $oCatArt     = $oCatArtColl->fetchByCategoryIdAndArticleId($idcat, $idart);

    if ($oCatArt == false) {
        $tpl = new cTemplate();
        $tpl->set("s", "ERROR_TITLE", "Fatal error");
        $tpl->set("s", "ERROR_TEXT", "The URL of the page you have tried to visit seems to be wrong.");
        $tpl->generate($errtpl);
        exit();
    }

    if (!cFileHandler::exists($cfgClient[$client]['code']['path'] . $client . "." . $lang . "." . $idcatart . ".php")) {
        cInclude('includes', 'functions.tpl.php');
        cInclude('includes', 'functions.mod.php');
        conGenerateCode($idcat, $idart, $lang, $client);
    }

    if ($oCatArt->get('createcode') == 1 || $force) {
        cInclude('includes', 'functions.tpl.php');
        cInclude('includes', 'functions.mod.php');
        conGenerateCode($idcat, $idart, $lang, $client);
    }

    $code = cFileHandler::read($cfgClient[$client]['code']['path'] . $client . "." . $lang . "." . $idcatart . ".php");

    // Add mark Script to code if user is in the backend
    if ($contenido && !empty($markscript)) {
        $code = preg_replace("/<\/head>/i", "$markscript\n</head>", $code, 1);
    }

    // If article is in use, display notification
    if (!empty($sHtmlInUseCss) && !empty($sHtmlInUseMessage)) {
        $code = preg_replace("/<\/head>/i", "$sHtmlInUseCss\n</head>", $code, 1);
        $code = preg_replace("/(<body[^>]*)>/i", "\${1}> \n $sHtmlInUseMessage", $code, 1);
    }

    // Check if category is public
    $oCatLang = new cApiCategoryLanguage();
    $oCatLang->loadByCategoryIdAndLanguageId($idcat, $lang);
    $public = $oCatLang->get('public');

    // Protected categories
    if ($public == 0) {
        if ($auth->auth['uid'] == 'nobody') {
            $userPropColl   = new cApiUserPropertyCollection($auth->auth['uid']);
            $userProperties = $userPropColl->fetchByTypeName('frontend', 'allowed_ip');
            foreach ($userProperties as $userProperty) {
                $user_id = $userProperty->get('user_id');
                $range   = $userProperty->f('value');
                $slash   = cString::findFirstPos($range, '/');

                if ($slash == false) {
                    $netmask = '255.255.255.255';
                    $network = $range;
                } else {
                    $network = cString::getPartOfString($range, 0, $slash);
                    $netmask = cString::getPartOfString($range, $slash + 1, cString::getStringLength($range) - $slash - 1);
                }

                if (ipMatch($network, $netmask, $_SERVER['REMOTE_ADDR'])) {
                    $oRightColl = new cApiRightCollection();
                    if (true === $oRightColl->hasFrontendAccessByCatIdAndUserId($idcat, $user_id)) {
                        $auth->auth['uid'] = $user_id;
                        $validated         = 1;
                    }
                }
            }
            if ($validated != 1) {
                // CEC to check category access
                // break at 'true', default value 'false'
                cApiCecHook::setBreakCondition(true, false);
                $allow = cApiCecHook::executeWhileBreakCondition('Contenido.Frontend.CategoryAccess', $lang, $idcat, $auth->auth['uid']);
                if (!$allow) {
                    $auth->restart();
                }
            }
        } else {
            // CEC to check category access
            // break at 'true', default value 'false'
            cApiCecHook::setBreakCondition(true, false);
            $allow = cApiCecHook::executeWhileBreakCondition('Contenido.Frontend.CategoryAccess', $lang, $idcat, $auth->auth['uid']);

            // In backendeditmode also check if logged in backenduser has
            // permission to view preview of page
            if ($allow == false && $contenido && $perm->have_perm_area_action_item('con_editcontent', 'con_editart', $idcat)) {
                $allow = true;
            }

            if (!$allow) {
                header($errsite);
                exit();
            }
        }
    }

    /**
     * @deprecated Since 4.10.2, `$cApiClient` was used for tracking in earlier
     *     times and is not needed anymore. Frontend modules/plugins should
     *     rather create their own instance if needed, instead of relying on
     *     the global instance.
     */
    $cApiClient = new cApiClient($client);


    // Don't track page hit if tracking off
    if (getSystemProperty('stats', 'tracking') != 'disabled' && cRegistry::isTrackingAllowed()) {
        // Statistic, track page hit
        $oStatColl = new cApiStatCollection();
        $oStatColl->trackVisit($idcatart, $lang, $client);
    }

    // Check if an article is start article of the category
    $oCatLang = new cApiCategoryLanguage();
    $oCatLang->loadByCategoryIdAndLanguageId($idcat, $lang);
    $isstart = ($oCatLang->get('startidartlang') == $idartlang) ? 1 : 0;

    // Time management, redirect
    $oArtLang = new cApiArticleLanguage();
    $oArtLang->loadByArticleAndLanguageId($idart, $lang);
    $online       = cSecurity::toInteger($oArtLang->get('online'));
    $redirect     = $oArtLang->get('redirect');
    $redirect_url = $oArtLang->get('redirect_url');

    @eval("\$" . "redirect_url = \"$redirect_url\";");

    if ($oArtLang->get('timemgmt') == '1' && $isstart != 1) {
        $online    = 0;
        $dateStart = $oArtLang->get('datestart');
        $dateEnd   = $oArtLang->get('dateend');

        if ($dateStart != '0000-00-00 00:00:00' && $dateEnd != '0000-00-00 00:00:00' && (strtotime($dateStart) <= time() || strtotime($dateEnd) > time()) && strtotime($dateStart) < strtotime($dateEnd)) {
            $online = 1;
        } elseif ($dateStart != '0000-00-00 00:00:00' && $dateEnd == '0000-00-00 00:00:00' && strtotime($dateStart) <= time()) {
            $online = 1;
        } elseif ($dateStart == '0000-00-00 00:00:00' && $dateEnd != '0000-00-00 00:00:00' && strtotime($dateEnd) > time()) {
            $online = 1;
        }
    }

    // transform variables Generate base url
    $insertBaseHref = getEffectiveSetting('generator', 'basehref', 'true');
    if ($insertBaseHref == 'true') {
        $baseHref = cRegistry::getFrontendUrl();

        // CEC for base href generation
        $baseHref = cApiCecHook::executeAndReturn('Contenido.Frontend.BaseHrefGeneration', $baseHref);

        $isXhtml = getEffectiveSetting('generator', 'xhtml', 'false');
        if ($isXhtml == 'true') {
            $baseCode = '<base href="' . $baseHref . '" />';
        } else {
            $baseCode = '<base href="' . $baseHref . '">';
        }

        $code = cString::iReplaceOnce("<head>", "<head>\n" . $baseCode, $code);
    }

    // Handle online (offline) articles
    if ($online) {
        if ($redirect == '1' && $redirect_url != '') {
            // Redirect to the URL defined in article properties
            $oUrl = cUri::getInstance();
            if ($oUrl->isIdentifiableFrontContentUrl($redirect_url)) {
                // CON-1990: append GET parameters to redirect url
                $redirect_url = $oUrl->appendParameters($redirect_url, $_GET);

                // Perform urlbuilding only for identified internal urls
                $aUrl = $oUrl->parse($redirect_url);
                if (!isset($aUrl['params']['lang'])) {
                    $aUrl['params']['lang'] = $lang;
                }
                $redirect_url = $oUrl->buildRedirect($aUrl['params']);
            }

            // Encode to punycode/IDNA (Internationalized Domain Name)
            $IDN = new idna_convert();

            $redirect_url  = $IDN->encode($redirect_url);
            $redirect_mode = $oArtLang->get('redirect_mode');

            // default redirection is temporary
            // with status code 302 or 307 (since HTTP/1.1)
            if ($redirect_mode === 'permanently') {
                if ($_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.1') {
                    $redirect_code = 308; // Permanent Redirect
                } else {
                    $redirect_code = 301; // Moved Permanently
                }
            } else {
                if ($_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.1') {
                    $redirect_code = 307; // Temporary Redirect
                } else {
                    $redirect_code = 302; // Found (Moved Temporarily)
                }
            }

            header('Location: ' . $redirect_url, true, $redirect_code);
            cRegistry::shutdown();
            exit();
        } else {
            if ($cfg['debug']['codeoutput']) {
                echo '<textarea>' . conHtmlSpecialChars($code) . '</textarea>';
            }

            // That's it! The code of an article will be evaluated.
            // The code of an article is basically a PHP script which is
            // cached in the database.
            // Layout and Modules are merged depending on the Container
            // definitions of the Template.
            $aExclude = explode(',', getEffectiveSetting('frontend.no_outputbuffer', 'idart', ''));
            if (in_array(cSecurity::toInteger($idart), $aExclude)) {
                eval("?>\n" . $code . "\n<?php\n");
            } else {
                // Write html output into output buffer and assign it to an
                // variable
                ob_start();
                eval("?>\n" . $code . "\n<?php\n");
                $htmlCode = ob_get_contents();
                ob_end_clean();

                // Process CEC to do some preparations before output
                $htmlCode = cApiCecHook::executeAndReturn('Contenido.Frontend.HTMLCodeOutput', $htmlCode);

                // Print output
                echo $htmlCode;
            }
        }
    } else {
        // If user is in the backend display offline articles
        if ($contenido) {
            eval("?>\n" . $code . "\n<?php\n");
        } else {
            if ($error == 1) {
                $tpl = new cTemplate();
                $tpl->set("s", "ERROR_TITLE", "Fatal error");
                $tpl->set("s", "ERROR_TEXT", "No CONTENIDO session variable set. Probable error cause: Start article in this category is not set on-line.");
                $tpl->generate($errtpl);
                exit();
            } else {
                header($errsite);
                exit();
            }
        }
    }
}

// End page cache, if enabled
if ($cfg['cache']['disable'] != '1') {
    $oCacheHandler->end();
    // echo $oCacheHandler->getInfo();
}

// Configuration settings after the site is displayed.
if (file_exists($cfgClient[$client]['config']['path'] . 'config.after.php')) {
    @include($cfgClient[$client]['config']['path'] . 'config.after.php');
}

if (isset($savedlang)) {
    $lang = $savedlang;
}

cRegistry::shutdown();

?>
