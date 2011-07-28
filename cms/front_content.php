<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * This file handles the view of an article.
 *
 * To handle the page we use the Database Abstraction Layer, the Session, Authentication and Permissions Handler of the
 * PHPLIB application development toolkit.
 *
 * The Client Id and the Language Id of an article will be determined depending on file __FRONTEND_PATH__/config.php where
 * $load_lang and $load_client are defined.
 * Depending on http globals via e.g. front_content.php?idcat=41&idart=34
 * the most important Contenido globals $idcat (Category Id), $idart (Article Id), $idcatart, $idartlang will be determined.
 *
 * The article can be displayed and edited in the Backend or the Frontend.
 * The attributes of an article will be considered (an article can be online, offline or protected ...).
 *
 * It is possible to customize the behavior by including the file __FRONTEND_PATH__/config.local.php or
 * the file __FRONTEND_PATH__/config.after.php
 *
 * If you use 'Frontend User' for protected areas, the category access permission will by handled via the
 * Contenido Extension Chainer.
 *
 * Finally the 'code' of an article will by evaluated and displayed.
 *
 * Requirements:
 * @con_php_req 5.0
 * @con_notice If you edit this file you must synchronise the files
 * - ./cms/front_content.php
 * - ./contenido/external/backendedit/front_content.php
 * - ./contenido/external/frontend/front_content.php
 *
 *
 * @package    Contenido Frontend
 * @version    4.8
 * @author     Olaf Niemann, Jan Lengowski, Timo A. Hummel et al.
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 *
 * {@internal
 *   created 2003-01-21
 *   modified 2008-06-16, H. Librenz, Hotfix: checking for potential unsecure call
 *   modified 2008-06-26, Frederic Schneider, add security fix
 *   modified 2008-07-02, Frederic Schneider, add more security fixes and include security_class
 *   modified 2008-08-29, Murat Purc, new way to execute chains
 *   modified 2008-09-07, Murat Purc, new chain 'Contenido.Frontend.AfterLoadPlugins'
 *   modified 2008-11-11, Andreas Lindner, added additional option to CEC_Hook::setConditions for frontend user acccess
 *   modified 2008-11-11, Andreas Lindner, Fixed typo in var name $iLangCheck (missing $)
 *   modified 2008-11-11, Andreas Lindner,        
 *   modified 2008-11-18, Timo Trautmann: in backendeditmode also check if logged in backenduser has permission to view preview of page 
 *   modified 2008-11-18, Murat Purc, add usage of Contenido_Url to create urls to frontend pages
 *   modified 2008-12-23, Murat Purc, fixed problems with Contenido_Url
 *   modified 2009-01-13, Murat Purc, changed handling of internal redirects
 *   modified 2009-03-02, Andreas Lindner, prevent $lang being wrongly set to 0 
 *   modified 2009-04-16, OliverL, check return from Contenido.Frontend.HTMLCodeOutput
 *   modified 2009-10-23, Murat Purc, removed deprecated function (PHP 5.3 ready)
 *   modified 2009-10-27, Murat Purc, fixed/modified CEC_Hook, see [#CON-256]
 *   modified 2010-05-20, Murat Purc, moved security checks into startup process, see [#CON-307]
 *   modified 2010-09-23, Murat Purc, fixed $encoding handling, see [#CON-305]
 *   modified 2011-02-07, Dominik Ziegler, added exit after redirections to force their execution
 *   modified 2011-02-10, Dominik Ziegler, moved function declaration of IP_match out of front_content.php
 *   modified 2011-07-21, Murat Purc, replaced several code snippets against new implemented functions (see revision 1447)
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

$contenido_path = '';
// Include the config file of the frontend to init the Client and Language Id
include_once('config.php');

// Contenido startup process
include_once($contenido_path . 'includes/startup.php');

cInclude('includes', 'functions.con.php');
cInclude('includes', 'functions.api.php');
cInclude('includes', 'functions.pathresolver.php');

// Initialize cronjob-Emulator
frontendInitializeCronjobEmulator();

// Initialize db, session, authentication and permission
frontendPageOpen();

// Load plugins (in global scope)
require_once($cfg['path']['contenido'] . $cfg['path']['includes'] . 'functions.includePluginConf.php');

// Call hook after plugins are loaded
CEC_Hook::execute('Contenido.Frontend.AfterLoadPlugins');

// Initialize client
frontendInitializeClient();

// Initialize clients configuration
frontendInitializeCfgClient();

// Initialize encoding
frontendInitializeEncoding();

// Check frontend globals
// @TODO: Should be outsourced into startup process but requires a better detection (frontend or backend)
Contenido_Security::checkFrontendGlobals();

// Update urlbuilder set http base path 
Contenido_Url::getInstance()->getUrlBuilder()->setHttpBasePath($cfgClient[$client]['htmlpath']['frontend']);

// Initialize language
frontendInitializeLanguage();

// Initialize authentication
frontendInitializeAuth();

// Send HTTP header with encoding
header("Content-Type: text/html; charset={$encoding[$lang]}");

// Include local configuration
if (file_exists('config.local.php')) {
    @include('config.local.php');
}

// Initialize category id if path or article id was send by request
frontendInitializeCategory();

// Set error page
$errsite = 'Location: ' . frontendCreateErrorPageUrl($client, $lang);

unset($code, $markscript);

// Initialize article and category
frontendInitializeArticleAndCategory($lang);

// Start page caching if enabled
if ($cfg['cache']['disable'] != '1') {
    cInclude('frontend', 'includes/concache.php');
    $oCacheHandler = new cConCacheHandler($GLOBALS['cfgConCache'], $db);
    $oCacheHandler->start();
}

// Editing frontend from backend or with valid authentication
list($inUse, $allow, $edit_preview, $sHtmlInUseCss, $sHtmlInUseMessage) = frontendProcessBackendEditing();

// If mode is 'edit' and user has permission to edit articles in the current category
if ($inUse == false && $allow == true && $view == 'edit'
    && ($perm->have_perm_area_action_item('con_editcontent', 'con_editart', $idcat)))
{
    cInclude('includes', 'functions.tpl.php');
    include($cfg['path']['contenido'] . $cfg['path']['includes'] . 'include.con_editcontent.php');
} else {

    ##############################################
    # FRONTEND VIEW
    ##############################################

    unset($edit); // disable editmode

    // Get code of current page
    $code = frontendGetCode($idcat, $idart, $idcatart, $client, $lang, $force);

    // Adapt backend view of code
    $code = frontendProcessBackendViewCode($code, $sHtmlInUseCss, $sHtmlInUseMessage);

    // Check if category is public or is accessible by current user
    frontendCategoryAccessCheck($idcat, $lang);

    // Track visit
    $oStatColl = new cApiStatCollection();
    $oStat = $oStatColl->trackVisit($idcatart, $lang, $client);

    // Check if article is a start article of the category
    $isstart = frontendIsStartArticle($idcatart, $idcat, $lang, $idartlang);

    // Some article language data depending on time management
    $artLangData = frontendGetArticleLanguageData($idart, $lang, $isstart);
    $online = $artLangData['online'];
    $redirect = $artLangData['redirect'];
    $redirect_url = $artLangData['redirect_url'];
    unset($artLangData);

    // Transform variables in redirect_url
    @eval("\$"."redirect_url = \"$redirect_url\";");

    // Generate base url
    $code = frontendProcessBaseTag($code);

    // Handle online (offline) articles
    if ($online) {
        if ($redirect == '1' && $redirect_url != '') {
            // Redirect to the URL defined in article properties
            frontendProcessArticleRedirect($redirect_url, $lang);
        } else {
            if ($cfg['debug']['codeoutput']) {
                echo '<textarea>' . htmlspecialchars($code) . '</textarea>';
            }

            /*
             * That's it! The code of an article will be evaluated.
             * The code of an article is basically a PHP script which is cached in the database.
             * Layout and Modules are merged depending on the Container definitions of the Template.
             */

            $aExclude = explode(',', getEffectiveSetting('frontend.no_outputbuffer', 'idart', ''));
            if (in_array(Contenido_Security::toInteger($idart), $aExclude)) {
                eval("?>\n" . $code . "\n<?php\n");
            } else {
                // Write html output into output buffer and assign it to an variable
                ob_start();
                eval("?>\n" . $code . "\n<?php\n");
                $htmlCode = ob_get_contents();
                ob_end_clean();

                // Process CEC to do some preparations before output
                $htmlCode = CEC_Hook::executeAndReturn('Contenido.Frontend.HTMLCodeOutput', $htmlCode);

                // Print output
                echo $htmlCode;
            }
        }
    } else {
        // If user is in the backend display offline articles
        if ($contenido) {
            eval("?>\n" . $code . "\n<?php\n");
        } else {
            frontendOfflineArticleError();
        }
    }
}

// End page caching if enabled
if ($cfg['cache']['disable'] != '1') {
    $oCacheHandler->end();
    #echo $oCacheHandler->getInfo();
}

// Configuration settings after the site is displayed.
if (file_exists('config.after.php')) {
    @include('config.after.php');
}

if (isset($savedlang)) {
    $lang = $savedlang;
}

page_close();
?>