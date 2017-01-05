<?php

/**
 * This file contains the backend page for client management.
 *
 * @package Core
 * @subpackage Backend
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

if (!$perm->have_perm_area_action($area)) {
    $notification->displayNotification('error', i18n('Permission denied'));
    return;
}

$page = new cGuiPage('client_edit', '', '0');

$cApiPropertyColl = new cApiPropertyCollection();
$cApiClient = new cApiClient();

if ($action == 'client_new') {
    $new = true;
}

if (!empty($idclient) && is_numeric($idclient)) {
    $cApiClient->loadByPrimaryKey((int) $idclient);
}

// @TODO Find a general solution for this!
if (defined('CON_STRIPSLASHES')) {
    $request = cString::stripSlashes($_REQUEST);
} else {
    $request = $_REQUEST;
}

$clientname = $request['clientname'];
$htmlpath = $request['htmlpath'];
$frontendpath = $request['frontendpath'];
$clientlogo = $request['clientlogo'];

$urlscheme = parse_url($htmlpath, PHP_URL_SCHEME);
$valid = ($clientname != "" && $frontendpath != "" && ($urlscheme == 'http' || $urlscheme == 'https'));

if ($action == 'client_edit' && $perm->have_perm_area_action($area, $action) && $valid) {
    // Set $validPath = true if path could be created, else false
    $validPath = false;
    $pathExisted = false;

    if (!cFileHandler::exists($request['frontendpath'])) {
        $validPath = mkdir($request['frontendpath'], 0755);
    } else {
        $pathExisted = true;
        $validPath = true;
    }

    $sNewNotification = '';
    if ($active != '1') {
        $active = '0';
    }

    if ($new == true && ($validPath == true || cFileHandler::exists($request['frontendpath']))) {

        // Create new client entry in clients table
        $cApiClientColl = new cApiClientCollection();
        $cApiClient = $cApiClientColl->create($clientname, $errsite_cat, $errsite_art);

        $idclient = $cApiClient->get('idclient');
        $cfgClient[$idclient]["name"] = $clientname;


        $sLangNotification = i18n('Notice: In order to use this client, you must create a new language for it.');
        $sTarget = $sess->url('frameset.php?area=lang&targetclient=' . $idclient);
        $sJsLink = "Con.getFrame('header').Con.HeaderMenu.markActive(Con.getFrame('header').document.getElementById('sub_lang'));
                    var url_right_bottom  = Con.UtilUrl.build('main.php', {area: 'lang', frame: 4});
                        url_right_top  = Con.UtilUrl.build('main.php', {area: 'lang', frame: 3});
                        url_left_bottom  = Con.UtilUrl.build('main.php', {area: 'lang', frame: 2, targetclient: " . $idclient . "});
                        url_left_top  = Con.UtilUrl.build('main.php', {area: 'lang', frame: 1, targetclient: " . $idclient . "});
                    Con.multiLink('right_top', url_right_top, 'right_bottom', url_right_bottom, 'left_top', url_left_top, 'left_bottom', url_left_bottom);";
        $sLangNotificationLink = sprintf(i18n('Please click %shere%s to create a new language.'), '<a href="javascript://" onclick="' . $sJsLink . '">', '</a>');
        $sNewNotification = '<br>' . $sLangNotification . '<br>' . $sLangNotificationLink;
        if (cString::getPartOfString($frontendpath, cString::getStringLength($frontendpath) - 1) != '/') {
            $frontendpath .= '/';
        }

        if (cString::getPartOfString($htmlpath, cString::getStringLength($htmlpath) - 1) != '/') {
            $htmlpath .= '/';
        }


        $cApiPropertyColl->setValue('idclient', $idclient, 'backend', 'clientimage', $clientlogo);

        $backendPath = cRegistry::getBackendPath();

        // Copy the client template to the real location
        $destPath = $frontendpath;
        $sourcePath = $backendPath . $cfg['path']['frontendtemplate'];
        $dataPath = 'data/config/' . CON_ENVIRONMENT . '/';

        if ($copytemplate) {
            if ($validPath && !$pathExisted) {
                cDirHandler::recursiveCopy($sourcePath, $destPath);
                $buffer = cFileHandler::read($destPath . $dataPath . 'config.php');
                $outbuf = str_replace('!CLIENT!', $idclient, $buffer);
                $outbuf = str_replace('!PATH!', $backendPath, $outbuf);
                if (!cFileHandler::write($destPath . $dataPath . 'config.php.new', $outbuf)) {
                    cRegistry::addErrorMessage(i18n("Couldn't write the file config.php."));
                }

                cFileHandler::remove($destPath . $dataPath . 'config.php');
                cFileHandler::rename($destPath . $dataPath . 'config.php.new', 'config.php');
            } else {
                $message = sprintf(i18n("The directory %s already exists. The client was created, but you have to copy the frontend-template yourself"), $destPath);
                cRegistry::addWarningMessage(i18n($message));
            }
        }
    } else {
        $pathwithoutslash = $frontendpath;
        if (cString::getPartOfString($frontendpath, cString::getStringLength($frontendpath) - 1) != '/') {
            $frontendpath .= '/';
        }

        if (!$validPath) {
            cRegistry::addWarningMessage(i18n("Path could not be created. Please ensure that there are only valid characters and no new nested folders."));
        }

        if (cString::getPartOfString($htmlpath, cString::getStringLength($htmlpath) - 1) != '/') {
            $htmlpath .= '/';
        }

        if (($oldpath != $frontendpath) && ($oldpath != $pathwithoutslash) && ($validPath)) {
            cRegistry::addWarningMessage(i18n("You changed the client path. You might need to copy the frontend to the new location"));
        }

        if ($cApiClient->isLoaded()) {
            $cApiClient->set('name', $clientname);
            $cApiClient->set('errsite_cat', $errsite_cat);
            $cApiClient->set('errsite_art', $errsite_art);
            $cApiClient->store();
        }
    }

    $new = false;

    //error_log("updateClientCache($idclient, $htmlpath, $frontendpath)");

    $cfgClient = updateClientCache($idclient, $htmlpath, $frontendpath);

    $cApiPropertyColl->setValue('idclient', $idclient, 'backend', 'clientimage', $clientlogo);

    // Clear the code cache
    if (cFileHandler::exists($cfgClient[$idclient]['code']['path']) === true) {
        /* @var $file SplFileInfo */
        foreach (new DirectoryIterator($cfgClient[$idclient]['code']['path']) as $file) {
            if ($file->isFile() === false) {
                continue;
            }

            $extension = cString::getPartOfString($file, cString::findLastPos($file->getBasename(), '.') + 1);
            if ($extension != 'php') {
                continue;
            }

            cFileHandler::remove($cfgClient[$idclient]['code']['path'] . '/' . $file->getFilename());
        }
    }

    if ($validPath || cFileHandler::exists($destPath)) {
        cRegistry::addOkMessage(i18n("Changes saved") . $sNewNotification);
    }

    $cApiClient->loadByPrimaryKey($idclient);

    if ($request['generate_xhtml'] == 'no') {
        $cApiClient->setProperty('generator', 'xhtml', 'false');
    } else {
        $cApiClient->setProperty('generator', 'xhtml', 'true');
    }

    // Is statistic on/off
    if ($request['statistic'] == 'on') {
        $cApiClient->setProperty('stats', 'tracking', 'on');
    } else {
        $cApiClient->setProperty('stats', 'tracking', 'off');
    }
}

if (isset($idclient)) {
    $htmlpath = $cfgClient[$idclient]['path']['htmlpath'];
    $serverpath = $cfgClient[$idclient]['path']['frontend'];
}

$form = '<form name="client_properties" method="post" action="' . $sess->url("main.php?") . '">
             <input type="hidden" name="area" value="' . $area . '">
             <input type="hidden" name="action" value="client_edit">
             <input type="hidden" name="frame" value="' . $frame . '">
             <input type="hidden" name="new" value="' . $new . '">
             <input type="hidden" name="oldpath" value="' . $serverpath . '">
             <input type="hidden" name="idclient" value="' . $idclient . '">';

$page->set('s', 'FORM', $form);
$page->set('s', 'SUBMITTEXT', i18n("Save changes"));
$page->set('s', 'CANCELTEXT', i18n("Discard changes"));
$page->set('s', 'CANCELLINK', $sess->url("main.php?area=$area&frame=4&idclient=$idclient"));
$page->set('s', 'PROPERTY', i18n("Property"));
$page->set('s', 'VALUE', i18n("Value"));
$page->set('d', 'BRDRT', 1);
$page->set('d', 'BRDRB', 0);

$page->set('d', 'CATNAME', i18n("Client name"));
$oTxtClient = new cHTMLTextbox("clientname", conHtmlSpecialChars(str_replace(array(
    '*/',
    '/*',
    '//',
    '\\',
    '"'
), '', ($cApiClient->isLoaded())? $cApiClient->get("name") : $clientname)), 75, 255, "clientname");
$page->set('d', 'CATFIELD', $oTxtClient->render());
$page->set('d', 'BRDRT', 0);
$page->set('d', 'BRDRB', 1);
$page->next();

// if no serverpath set use default root server path where all frontends reside
if (false === isset($serverpath)) {
    $serverpath = $cfg['path']['frontend'];
}

$page->set('d', 'CATNAME', i18n("Server path"));
$oTxtServer = new cHTMLTextbox("frontendpath", conHtmlSpecialChars($serverpath), 75, 255, "frontendpath");
$page->set('d', 'CATFIELD', $oTxtServer->render());
$page->set('d', 'BRDRT', 0);
$page->set('d', 'BRDRB', 1);
$page->next();

if ($htmlpath == '') {
    $htmlpath = 'http://';
}

$page->set('d', 'CATNAME', i18n("Web address"));
$oTxtWeb = new cHTMLTextbox("htmlpath", conHtmlSpecialChars($htmlpath), 75, 255, "htmlpath");
$page->set('d', 'CATFIELD', $oTxtWeb->render());
$page->set('d', 'BRDRT', 0);
$page->set('d', 'BRDRB', 1);
$page->next();

$page->set('d', 'CATNAME', i18n("Error page category"));
$oTxtErrorCat = new cHTMLTextbox("errsite_cat", $cApiClient->get("errsite_cat"), 10, 10);
$page->set('d', 'CATFIELD', $oTxtErrorCat->render());
$page->set('d', 'BRDRT', 0);
$page->set('d', 'BRDRB', 1);
$page->next();

$page->set('d', 'CATNAME', i18n("Error page article"));
$oTxtErrorArt = new cHTMLTextbox("errsite_art", $cApiClient->get("errsite_art"), 10, 10);
$page->set('d', 'CATFIELD', $oTxtErrorArt->render());
$page->set('d', 'BRDRT', 0);
$page->set('d', 'BRDRB', 1);
$page->next();

$clientLogo = $cApiPropertyColl->getValue('idclient', $idclient, 'backend', 'clientimage');
$page->set('d', 'CATNAME', i18n("Client logo"));
$oTxtLogo = new cHTMLTextbox("clientlogo", $clientLogo, 75, 255);
$page->set('d', 'CATFIELD', $oTxtLogo->render());
$page->set('d', 'BRDRT', 0);
$page->set('d', 'BRDRB', 1);
$page->next();

// Flag to generate XHTML
$aChoices = array(
    'no' => i18n('No'),
    'yes' => i18n('Yes')
);

$oXHTMLSelect = new cHTMLSelectElement('generate_xhtml');
$oXHTMLSelect->autoFill($aChoices);

if ($cApiClient->getProperty('generator', 'xhtml') == 'true') {
    $oXHTMLSelect->setDefault('yes');
} else {
    $oXHTMLSelect->setDefault('no');
}

$page->set('d', 'CATNAME', i18n('Generate XHTML'));
$page->set('d', 'CATFIELD', $oXHTMLSelect->render());
$page->set('d', 'BRDRT', 0);
$page->set('d', 'BRDRB', 1);
$page->next();

// Flag to enable tracking
$aChoices = array(
    'on' => i18n('On'),
    'off' => i18n('Off')
);

$oXHTMLSelect = new cHTMLSelectElement('statistic');
$oXHTMLSelect->autoFill($aChoices);

if ($cApiClient->getProperty('stats', 'tracking') == 'off') {
    $oXHTMLSelect->setDefault('off');
} else {
    $oXHTMLSelect->setDefault('on');
}

$page->set('d', 'CATNAME', i18n('Statistic'));
$page->set('d', 'CATFIELD', $oXHTMLSelect->render());
$page->set('d', 'BRDRT', 0);
$page->set('d', 'BRDRB', 1);
$page->next();

// Show checkbox to copy frontend templates for new clients
if ($new == true) {
    $page->set('d', 'CATNAME', i18n('Copy frontend template'));
    $defaultform = new cHTMLCheckbox('copytemplate', 'checked', 'copytemplatechecked', true);
    $page->set('d', 'CATFIELD', $defaultform->toHtml(false));
    $page->next();
}
$page->set('s', 'IDCLIENT', $idclient);

// Generate template
//$tpl->generate($cfg['path']['templates'] . $cfg['templates']['client_edit']);
$page->render();
