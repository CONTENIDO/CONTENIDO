<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * This file handles the view of an article in frontend and in backend.
 *
 * To handle the page we use the Database Abstraction Layer, the Session, Authentication and Permissions Handler of the
 * PHPLIB application development toolkit.
 *
 * The Client Id and the Language Id of an article will be determined depending on file __FRONTEND_PATH__/config.php where
 * $load_lang and $load_client are defined.
 * Depending on http globals via e.g. front_content.php?idcat=41&idart=34
 * the most important CONTENIDO globals $idcat (Category Id), $idart (Article Id), $idcatart, $idartlang will be determined.
 *
 * The article can be displayed and edited in the Backend or the Frontend.
 * The attributes of an article will be considered (an article can be online, offline or protected ...).
 *
 * It is possible to customize the behavior by including the file __FRONTEND_PATH__/config.local.php or
 * the file __FRONTEND_PATH__/config.after.php
 *
 * If you use 'Frontend User' for protected areas, the category access permission will by handled via the
 * CONTENIDO Extension Chainer.
 *
 * Finally the 'code' of an article will by evaluated and displayed.
 *
 * NOTE:
 * This file has to run in clients frontend directory!
 *
 * Requirements:
 * @con_php_req 5.0
 *
 * @package    CONTENIDO Frontend
 * @version    4.9
 * @author     Olaf Niemann, Jan Lengowski, Timo A. Hummel et al.
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release = 4.9
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

// Clients local configuration
if (file_exists('config.local.php')) {
    @include('config.local.php');
}

cInclude('includes', 'functions.con.php');
cInclude('includes', 'functions.con2.php');
cInclude('includes', 'functions.api.php');
cInclude('includes', 'functions.pathresolver.php');

if ($cfg['use_pseudocron'] == true) {
    // Include cronjob-Emulator
    $oldpwd = getcwd();
    chdir($cfg['path']['contenido'] . $cfg['path']['cronjobs']);
    cInclude('includes', 'pseudo-cron.inc.php');
    chdir($oldpwd);
}

// Initialize the Database Abstraction Layer, the Session, Authentication and Permissions
// Handler of the PHPLIB application development toolkit
// @see http://sourceforge.net/projects/phplib
if ($contenido) {
    // Backend
    cRegistry::bootstrap(array(
        'sess' => 'Contenido_Session',
        'auth' => 'Contenido_Challenge_Crypt_Auth',
        'perm' => 'Contenido_Perm'
    ));
    i18nInit($cfg['path']['contenido'].$cfg['path']['locale'], $belang);
} else {
    // Frontend
    cRegistry::bootstrap(array(
        'sess' => 'Contenido_Frontend_Session',
        'auth' => 'Contenido_Frontend_Challenge_Crypt_Auth',
        'perm' => 'Contenido_Perm'
    ));
}

// Include plugins
require_once($cfg['path']['contenido'] . $cfg['path']['includes'] . 'functions.includePluginConf.php');

// Call hook after plugins are loaded
CEC_Hook::execute('Contenido.Frontend.AfterLoadPlugins');

$db = new DB_Contenido();

$sess->register('cfgClient');
$sess->register('errsite_idcat');
$sess->register('errsite_idart');
$sess->register('encoding');

if ($cfgClient['set'] != 'set') {
    rereadClients();
}

// Initialize encodings
if (!isset($encoding) || !is_array($encoding) || count($encoding) == 0) {
    // Get encodings of all languages
    $encoding = array();
    $oLangColl = new cApiLanguageCollection();
    $oLangColl->select('');
    while ($oLang = $oLangColl->next()) {
        $encoding[$oLang->get('idlang')] = $oLang->get('encoding');
    }
}

// Check frontend globals
// @TODO: Should be outsourced into startup process but requires a better detection (frontend or backend)
Contenido_Security::checkFrontendGlobals();

// Update urlbuilder, set http base path
Contenido_Url::getInstance()->getUrlBuilder()->setHttpBasePath($cfgClient[$client]['htmlpath']['frontend']);

// Initialize language
if (!isset($lang)) {
    // If there is an entry load_lang in frontend/config.php use it, else use the first language of this client
    if (isset($load_lang)) {
        // load_client is set in frontend/config.php
        $lang = $load_lang;
    } else {
        $oClientLang = new cApiClientLanguageCollection();
        $lang = $oClientLang->getFirstLanguageIdByClient($client);
    }
}

if (!$sess->is_registered('lang')) {
    $sess->register('lang');
}
if (!$sess->is_registered('client')) {
    $sess->register('client');
}

if (isset($username)) {
    $auth->login_if(true);
}

// Send HTTP header with encoding
header("Content-Type: text/html; charset={$encoding[$lang]}");

// If http global logout is set e.g. front_content.php?logout=true, log out the current user.
if (isset($logout)) {
    $auth->logout(true);
    $auth->unauth(true);
    $auth->auth['uname'] = 'nobody';
}

// If the path variable was passed, try to resolve it to a category id,
// e.g. front_content.php?path=/company/products/
if (isset($path) && strlen($path) > 1) {
    // Which resolve method is configured?
    if ($cfg['urlpathresolve'] == true) {
        $iLangCheck = 0;
        $idcat = prResolvePathViaURLNames($path, $iLangCheck);
    } else {
        $iLangCheck = 0;
        $idcat = prResolvePathViaCategoryNames($path, $iLangCheck);
        if (($lang != $iLangCheck) && ((int)$iLangCheck != 0)) {
            $lang = $iLangCheck;
        }
    }
}

// Error page
$aParams = array(
    'client' => $client, 'idcat' => $errsite_idcat[$client], 'idart' => $errsite_idart[$client],
    'lang' => $lang, 'error'=> '1'
);
$errsite = 'Location: ' . Contenido_Url::getInstance()->buildRedirect($aParams);


// Try to initialize variables $idcat, $idart, $idcatart, $idartlang
// Note: These variables can be set via http globals e.g. front_content.php?idcat=41&idart=34&idcatart=35&idartlang=42
// If not the values will be computed.
if ($idart && !$idcat && !$idcatart) {
    // Try to fetch the idcat by idart
    $oCatArt = new cApiCategoryArticle();
    if ($oCatArt->loadBy('idart', (int) $idart)) {
        $idcat = $oCatArt->get('idcat');
    }
}

unset($code, $markscript);

if (!$idcatart) {
    if (!$idart) {
        if (!$idcat) {
            // Try to get caetgory and article id of first item in current clients tree structure
            $oCatArtColl = new cApiCategoryArticleCollection();
            $oCatArt = $oCatArtColl->fetchFirstFromTreeByClientIdAndLangId($client, $lang);
            if ($oCatArt) {
                $idart = $oCatArt->get('idart');
                $idcat = $oCatArt->get('idcat');
            } else {
                if ($contenido) {
                    cInclude('includes', 'functions.i18n.php');
                    die(i18n('No start article for this category'));
                } else {
                    if ($error == 1) {
                        echo "Fatal error: Could not display error page. Error to display was: 'No start article in this category'";
                    } else {
                        header($errsite);
                        exit;
                    }
                }
            }
        } else {
            $idart = -1;

            // Try to fetch article by category and language
            $oCatLang = new cApiCategoryLanguage();
            if ($oCatLang->loadByCategoryIdAndLanguageId($idcat, $lang)) {
                if ($oCatLang->get('startidartlang') != 0) {
                    $oArtLang = new cApiArticleLanguage($oCatLang->get('startidartlang'));
                    $idart = $oArtLang->get('idart');
                }
            }

            if ($idart != -1) {
                // donut
            } else {
                // Error message in backend
                if ($contenido) {
                    cInclude('includes', 'functions.i18n.php');
                    die(i18n('No start article for this category'));
                } else {
                    if ($error == 1) {
                        echo "Fatal error: Could not display error page. Error to display was: 'No start article in this category'";
                    } else {
                        header($errsite);
                        exit;
                    }
                }
            }
        }
    }
} else {
    // Try to fetch article and category id by idcatart
    $oCatArt = new cApiCategoryArticle((int) $idcatart);
    if ($oCatArt->isLoaded()) {
        $idcat = $oCatArt->get('idcat');
        $idart = $oCatArt->get('idart');
    }
}

// Get idcatart
if (0 != $idart && 0 != $idcat) {
    $oCatArtColl = new cApiCategoryArticleCollection();
    if ($oCatArt = $oCatArtColl->fetchByCategoryIdAndArticleId($idcat, $idart)) {
        $idcatart = $oCatArt->get('idcatart');
    }
}

$idartlang = getArtLang($idart, $lang);
if ($idartlang === false) {
    if($_GET['display_errorpage']) {
    	//show only if $idart > 0  
    	if($idart > 0) {
    		$tpl = new Template();
    		$tpl->set('s', 'CONTENIDO_PATH', $cfg['path']['contenido_fullhtml']);
    		$tpl->set('s', 'ERROR_TITLE', i18n('Error page'));
    		$tpl->set('s', 'ERROR_TEXT', i18n('Error article/category not found!'));
    		$tpl->generate($cfg['path']['contenido']. $cfg['path']['templates'].'template.error_page.html');
    	}
    	exit;
    }else {
    	header($errsite. '&display_errorpage=1');
    }
    exit;
}

// Start page cache, if enabled
if ($cfg['cache']['disable'] != '1') {
    cInclude('classes', 'cache/concache.php');
    $oCacheHandler = new cOutputCacheHandler($GLOBALS['cfgConCache'], $db);
    $oCacheHandler->start($iStartTime); // $iStartTime ist optional und ist die startzeit des scriptes, z. b. am anfang von fron_content.php
}


// Backend / Frontend editing

/**
 * If user has CONTENIDO-backend rights.
 * $contenido <==> the cotenido backend session as http global
 * In Backend: e.g. contenido/index.php?contenido=dac651142d6a6076247d3afe58c8f8f2
 * Can also be set via front_content.php?contenido=dac651142d6a6076247d3afe58c8f8f2
 *
 * Note: In backend the file contenido/external/backendedit/front_content.php is included!
 * The reason is to avoid cross-site scripting errors in the backend, if the backend domain differs from
 * the frontend domain.
 */
if ($contenido) {
    $perm->load_permissions();

    // Change mode edit / view
    if (isset($changeview)) {
        $sess->register('view');
        $view = $changeview;
    }

    $col = new cApiInUseCollection();

    if ($overrideid != '' && $overridetype != '') {
        $col->removeItemMarks($overridetype, $overrideid);
    }
    // Remove all own marks
    $col->removeSessionMarks($sess->id);
    // If the override flag is set, override a specific cApiInUse

    $inUseUrl = $cfg['path']['contenido_fullhtml'] . "external/backendedit/front_content.php?changeview=edit&action=con_editart&idartlang=$idartlang&type=$type&typenr=$typenr&idart=$idart&idcat=$idcat&idcatart=$idcatart&client=$client&lang=$lang";
    list($inUse, $message) = $col->checkAndMark('article', $idartlang, true, i18n('Article is in use by %s (%s)'), true, $inUseUrl);

    $sHtmlInUse = '';
    $sHtmlInUseMessage = '';
    if ($inUse == true) {
        $disabled = 'disabled="disabled"';
        $sHtmlInUseCss = '<link rel="stylesheet" type="text/css" href="' . $cfg['path']['contenido_fullhtml'] . 'styles/inuse.css" />';
        $sHtmlInUseMessage = $message;
    }

    // Is article locked?
    $oArtLang = new cApiArticleLanguage();
    $oArtLang->loadByArticleAndLanguageId($idart, $lang);
    $locked = $oArtLang->get('locked');
    if ($locked == 1) {
        $inUse = true;
        $disabled = 'disabled="disabled"';
    }

    // CEC to check if the user has permission to edit articles in this category
    CEC_Hook::setBreakCondition(false, true); // break at 'false', default value 'true'
    $allow = CEC_Hook::executeWhileBreakCondition(
        'Contenido.Frontend.AllowEdit', $lang, $idcat, $idart, $auth->auth['uid']
    );

    if ($perm->have_perm_area_action_item('con_editcontent', 'con_editart', $idcat) && $inUse == false && $allow == true) {
        // Start editing table
        $edit_preview = '<table cellspacing="0" cellpadding="4" border="0">';

        // Create buttons for editing
        if ($view == 'edit') {
            $edit_preview = '<tr>
                                <td width="18">
                                    <a title="Preview" style="font-family:verdana;font-size:10px;color:#000;text-decoration:none" href="' . $sess->url("front_content.php?changeview=prev&idcat=$idcat&idart=$idart") . '"><img src="' . $cfg['path']['contenido_fullhtml'] . $cfg['path']['images'] . 'but_preview.gif" alt="Preview" title="Preview" border="0"></a>
                                </td>
                                <td width="18">
                                    <a title="Preview" style="font-family:verdana;font-size:10px;color:#000;text-decoration:none" href="' . $sess->url("front_content.php?changeview=prev&idcat=$idcat&idart=$idart") . '">Preview</a>
                                </td>
                            </tr>';
        } else {
            $edit_preview = '<tr>
                                <td width="18">
                                    <a title="Preview" style="font-family:verdana;font-size:10px;color:#000;text-decoration:none" href="' . $sess->url("front_content.php?changeview=edit&idcat=$idcat&idart=$idart") . '"><img src="' . $cfg['path']['contenido_fullhtml'] . $cfg['path']['images'] . 'but_edit.gif" alt="Preview" title="Preview" border="0"></a>
                                </td>
                                <td width="18">
                                    <a title="Preview" style="font-family:verdana;font-size:10px;color:#000;text-decoration:none" href="' . $sess->url("front_content.php?changeview=edit&idcat=$idcat&idart=$idart") . '">Edit</a>
                                </td>
                            </tr>';
        }

        // List category articles
        $a = 1;
        $edit_preview .= '<tr><td colspan="2"><table cellspacing="0" cellpadding="2" border="0"></tr><td style="font-family:verdana;font-size:10;color:#000;text-decoration:none">Articles in category:<br>';

        $oCatArtColl = new cApiCategoryArticleCollection();
        if ($oCatArtColl->select('idcat = ' . (int) $idcat, '', 'idart')) {
            while ($oCatArtItem = $oCatArtColl->next()) {
                $class = 'font-family:verdana;font-size:10;color:#000;text-decoration:underline;font-weight:normal';
                if (!isset($idart)) {
                    if (isStartArticle(getArtLang($idart, $lang), $idcat, $lang)) {
                        $class = 'font-family:verdana;font-size:10;color:#000;text-decoration:underline;font-weight:bold';
                    }
                } else {
                    if ($idart == $oCatArtItem->get('idart')) {
                        $class = 'font-family:verdana;font-size:10;color:#000;text-decoration:underline;font-weight:bold';
                    }
                }
                $edit_preview .= '<a style="' . $class . '" href="' . $sess->url('front_content.php?idart=' . $oCatArtItem->get('idart') . "&idcat=$idcat") . '">' . $a . '</a>&nbsp;';
                $a++;
            }
        }

        // End editing table
        $edit_preview .= '</td></tr></table></td></tr></table>';
    }
}


// If mode is 'edit' and user has permission to edit articles in the current category
if ($inUse == false && $allow == true && $view == 'edit' && ($perm->have_perm_area_action_item('con_editcontent', 'con_editart', $idcat))) {
    cInclude('includes', 'functions.tpl.php');
    include($cfg['path']['contenido'] . $cfg['path']['includes'] . 'include.con_editcontent.php');
} else {

    // Frontend view

    // Mark submenuitem 'Preview' in the CONTENIDO Backend (Area: Contenido --> Articles --> Preview)
    if ($contenido) {
        $markscript = markSubMenuItem(5, true);
    }

    unset($edit); // disable editmode

    // 'mode' is preview (Area: Contenido --> Articles --> Preview) or article displayed in the front-end

    // Code generation
    $oCodeColl = new cApiCodeCollection();

    $oCatArtColl = new cApiCategoryArticleCollection();
    $oCatArt = $oCatArtColl->fetchByCategoryIdAndArticleId($idcat, $idart);

    // Check if code is expired, create new code if needed
    if ($oCatArt->get('createcode') == 0 && $force == 0) {
        $oCode = $oCodeColl->fetchByCatArtAndLang($idcatart, $lang);
        if (!is_object($oCode)) {
            cInclude('includes', 'functions.tpl.php');
            conGenerateCode($idcat, $idart, $lang, $client);
            $oCode = $oCodeColl->fetchByCatArtAndLang($idcatart, $lang);
        }

        if (is_object($oCode)) {
            $code = $oCode->get('code', false);
        } else {
            if ($contenido) {
                $code = "echo \"No code available.\";";
            } else {
                if ($error == 1) {
                    echo "Fatal error: Could not display error page. Error to display was: 'No code available'";
                } else {
                    header($errsite);
                    exit;
                }
            }
        }
    } else {
        $oCodeColl->deleteByCatArt($idcatart);
        cInclude('includes', 'functions.tpl.php');
        cInclude('includes', 'functions.mod.php');
        conGenerateCode($idcat, $idart, $lang, $client);
        $oCode = $oCodeColl->fetchByCatArtAndLang($idcatart, $lang);
        $code = $oCode->get('code', false);
    }

    // Add mark Script to code if user is in the backend
    $code = preg_replace("/<\/head>/i", "$markscript\n</head>", $code, 1);

    // If article is in use, display notification
    if ($sHtmlInUseCss && $sHtmlInUseMessage) {
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
            $userPropColl = new cApiUserPropertyCollection();
            $userProperties = $userPropColl->fetchByTypeName('frontend', 'allowed_ip');
            foreach ($userProperties as $userProperty) {
                $user_id = $userProperty->get('user_id');
                $range = urldecode($userProperty->f('value'));
                $slash = strpos($range, '/');

                if ($slash == false) {
                    $netmask = '255.255.255.255';
                    $network = $range;
                } else {
                    $network = substr($range, 0, $slash);
                    $netmask = substr($range, $slash +1, strlen($range) - $slash -1);
                }

                if (IP_match($network, $netmask, $_SERVER['REMOTE_ADDR'])) {
                    $oRightColl = new cApiRightCollection();
                    if (true === $oRightColl->hasFrontendAccessByCatIdAndUserId($idcat, $user_id)) {
                        $auth->auth['uid'] = $user_id;
                        $validated = 1;
                    }
                }
            }
            if ($validated != 1) {
                // CEC to check category access
                CEC_Hook::setBreakCondition(true, false); // break at 'true', default value 'false'
                $allow = CEC_Hook::executeWhileBreakCondition(
                    'Contenido.Frontend.CategoryAccess', $lang, $idcat, $auth->auth['uid']
                );
                $auth->login_if(!$allow);
            }
        } else {
            // CEC to check category access
            CEC_Hook::setBreakCondition(true, false); // break at 'true', default value 'false'
            $allow = CEC_Hook::executeWhileBreakCondition(
                'Contenido.Frontend.CategoryAccess', $lang, $idcat, $auth->auth['uid']
            );

            // In backendeditmode also check if logged in backenduser has permission to view preview of page
            if ($allow == false && $contenido && $perm->have_perm_area_action_item('con_editcontent', 'con_editart', $idcat)) {
                $allow = true;
            }

            if (!$allow) {
                header($errsite);
                exit;
            }
        }
    }

    // Statistic, track page hit
    $oStatColl = new cApiStatCollection();
    $oStat = $oStatColl->trackVisit($idcatart, $lang, $client);

    // Check if an article is start article of the category
    $oCatLang = new cApiCategoryLanguage();
    $oCatLang->loadByCategoryIdAndLanguageId($idcat, $lang);
    $isstart = ($oCatLang->get('idartlang') == $idartlang) ? 1 : 0;

    // Time management, redirect
    $oArtLang = new cApiArticleLanguage();
    $oArtLang->loadByArticleAndLanguageId($idart, $lang);

    $online = $oArtLang->get('online');
    $redirect = $oArtLang->get('redirect');
    $redirect_url = $oArtLang->get('redirect_url');

    if ($oArtLang->get('timemgmt') == '1' && $isstart != 1) {
        $online = 0;
        $dateStart = $oArtLang->get('datestart');
        $dateEnd = $oArtLang->get('dateend');

        if ($dateStart != '0000-00-00 00:00:00' && $dateEnd != '0000-00-00 00:00:00'
            && (strtotime($dateStart) <= time() || strtotime($dateEnd) > time()) && strtotime($dateStart) < strtotime($dateEnd)) {
            $online = 1;
        } elseif ($dateStart != '0000-00-00 00:00:00' && $dateEnd == '0000-00-00 00:00:00' && strtotime($dateStart) <= time()) {
            $online = 1;
        } elseif ($dateStart == '0000-00-00 00:00:00' && $dateEnd != '0000-00-00 00:00:00' && strtotime($dateEnd) > time()) {
            $online = 1;
        }
    }

    @eval("\$" . "redirect_url = \"$redirect_url\";"); // transform variables

    // Generate base url
    $insertBaseHref = getEffectiveSetting('generator', 'basehref', 'true');
    if ($insertBaseHref == 'true') {
        $baseHref = $cfgClient[$client]['path']['htmlpath'];

        // CEC for base href generation
        $baseHref = CEC_Hook::executeAndReturn('Contenido.Frontend.BaseHrefGeneration', $baseHref);

        $isXhtml = getEffectiveSetting('generator', 'xhtml', 'false');
        if ($isXhtml == 'true') {
            $baseCode = '<base href="' . $baseHref . '" />';
        } else {
            $baseCode = '<base href="' . $baseHref . '">';
        }

        $code = str_ireplace_once("<head>", "<head>\n" . $baseCode, $code);
    }

    // Handle online (offline) articles
    if ($online) {
        if ($redirect == '1' && $redirect_url != '') {
            cRegistry::shutdown();
            // Redirect to the URL defined in article properties
            $oUrl = Contenido_Url::getInstance();
            if ($oUrl->isIdentifiableFrontContentUrl($redirect_url)) {
                // Perform urlbuilding only for identified internal urls
                $aUrl = $oUrl->parse($redirect_url);
                if (!isset($aUrl['params']['lang'])) {
                    $aUrl['params']['lang'] = $lang;
                }
                $redirect_url = $oUrl->buildRedirect($aUrl['params']);
            }
            header('Location: ' . $redirect_url);
            exit;
        } else {
            if ($cfg['debug']['codeoutput']) {
                echo '<textarea>'.htmlspecialchars($code).'</textarea>';
            }

            // That's it! The code of an article will be evaluated.
            // The code of an article is basically a PHP script which is cached in the database.
            // Layout and Modules are merged depending on the Container definitions of the Template.

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
            if ($error == 1) {
                echo "Fatal error: Could not display error page. Error to display was: 'No CONTENIDO session variable set. Probable error cause: Start article in this category is not set on-line.'";
            } else {
                header($errsite);
                exit;
            }
        }
    }
}

// End page cache, if enabled
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

cRegistry::shutdown();

?>