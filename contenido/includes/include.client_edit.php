<?php
/**
 * This file contains the backend page for client management.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Timo Hummel
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$properties = new cApiPropertyCollection();
$oClient = new cApiClient();

if ($action == 'client_new') {
    $new = true;
}


if (!$perm->have_perm_area_action($area)) {
    $notification->displayNotification('error', i18n('Permission denied'));
    return;
}

if (!empty($idclient) && is_numeric($idclient)) {
    $oClient = new cApiClient(cSecurity::toInteger($idclient));
}

if (($action == 'client_edit') && ($perm->have_perm_area_action($area, $action))) {
    $sNewNotification = '';
    if ($active != '1') {
        $active = '0';
    }

    if ($new == true) {
        $sLangNotification = i18n('Notice: In order to use this client, you must create a new language for it.');
        $sTarget = $sess->url('frameset.php?area=lang');
        $sJsLink = "parent.parent.location.href='" . $sTarget . "';
                    top.header.markActive(top.header.document.getElementById('sub_lang'));";
        $sLangNotificationLink = sprintf(i18n('Please click %shere%s to create a new language.'), '<a href="javascript://" onclick="' . $sJsLink . '">', '</a>');
        $sNewNotification = '<br>' . $sLangNotification . '<br>' . $sLangNotificationLink;
        if (substr($frontendpath, strlen($frontendpath) - 1) != '/') {
            $frontendpath .= '/';
        }

        if (substr($htmlpath, strlen($htmlpath) - 1) != '/') {
            $htmlpath .= '/';
        }

        // Create new client entry in clients table
        $oClientColl = new cApiClientCollection();
        $oClient = $oClientColl->create($clientname, $errsite_cat, $errsite_art);

        $idclient = $oClient->get('idclient');
        $cfgClient[$idclient]["name"] = $clientname;
        updateClientCache();

        $properties->setValue('idclient', $idclient, 'backend', 'clientimage', $clientlogo);

        $backendPath = cRegistry::getBackendPath();

        // Copy the client template to the real location
        $destPath = $frontendpath;
        $sourcePath = $backendPath . $cfg['path']['frontendtemplate'];
        $dataPath = 'data/config/' . CON_ENVIRONMENT . '/';

        if ($copytemplate) {
            if (!cFileHandler::exists($destPath)) {
                recursiveCopy($sourcePath, $destPath);
                $buffer = cFileHandler::read($destPath . $dataPath . 'config.php');
                $outbuf = str_replace('!CLIENT!', $idclient, $buffer);
                $outbuf = str_replace('!PATH!', $backendPath, $outbuf);
                if (!cFileHandler::write($destPath . $dataPath . 'config.php.new', $outbuf)) {
                    $notification->displayNotification('error', i18n("Couldn't write the file config.php."));
                }

                cFileHandler::remove($destPath . $dataPath . 'config.php');
                cFileHandler::rename($destPath . $dataPath . 'config.php.new', 'config.php');
            } else {
                $message = sprintf(i18n("The directory %s already exists. The client was created, but you have to copy the frontend-template yourself"), $destPath);
                $notification->displayNotification('warning', $message);
            }
        }

    } else {
        $pathwithoutslash = $frontendpath;
        if (substr($frontendpath, strlen($frontendpath) - 1) != '/') {
            $frontendpath .= '/';
        }

        if (substr($htmlpath, strlen($htmlpath) - 1) != '/') {
            $htmlpath .= '/';
        }

        if (($oldpath != $frontendpath) && ($oldpath != $pathwithoutslash)) {
            $notification->displayNotification('warning', i18n("You changed the client path. You might need to copy the frontend to the new location"));
        }

        if ($oClient->isLoaded()) {
            $oClient->set('name', $clientname);
            $oClient->set('errsite_cat', $errsite_cat);
            $oClient->set('errsite_art', $errsite_art);
            $oClient->store();
        }
    }

    $new = false;

    $cfgClient = updateClientCache($idclient, $htmlpath, $frontendpath);

    $properties->setValue('idclient', $idclient, 'backend', 'clientimage', $clientlogo);

    // Clear the code cache
    foreach (new DirectoryIterator($cfgClient[$idclient]['cache']['path']) as $file) {
        $extension = substr($file, strpos($file->getBasename(), '.') + 1);
        if ($file->getFilename() == $idclient && $extension == "php") {
            unlink($cfgClient[$idclient]['cache']['path'] . $idclient . 'php');
        }
    }

    $notification->displayNotification('info', i18n("Changes saved") . $sNewNotification);

    $cApiClient = new cApiClient;
    $cApiClient->loadByPrimaryKey($idclient);

    if ($_REQUEST['generate_xhtml'] == 'no') {
        $cApiClient->setProperty('generator', 'xhtml', 'false');
    } else {
        $cApiClient->setProperty('generator', 'xhtml', 'true');
    }

    //Is statistc on/off
    if ($_REQUEST['statistic'] == 'on') {
        $cApiClient->setProperty('stats', 'tracking', 'on');
    } else {
        $cApiClient->setProperty('stats', 'tracking', 'off');
    }
}


$tpl->reset();

$htmlpath = '';
$serverpath = '';
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

$tpl->set('s', 'FORM', $form);
$tpl->set('s', 'SUBMITTEXT', i18n("Save changes"));
$tpl->set('s', 'CANCELTEXT', i18n("Discard changes"));
$tpl->set('s', 'CANCELLINK', $sess->url("main.php?area=$area&frame=4&idclient=$idclient"));
$tpl->set('s', 'PROPERTY', i18n("Property"));
$tpl->set('s', 'VALUE', i18n("Value"));
$tpl->set('d', 'BRDRT', 1);
$tpl->set('d', 'BRDRB', 0);

$tpl->set('d', 'CATNAME', i18n("Client name"));
$oTxtClient = new cHTMLTextbox("clientname", conHtmlSpecialChars(str_replace(array('*/','/*','//','\\','"'),'',$oClient->get("name")), 75, 255));
$tpl->set('d', 'CATFIELD', $oTxtClient->render());
$tpl->set('d', 'BRDRT', 0);
$tpl->set('d', 'BRDRB', 1);
$tpl->next();

if ($serverpath == '') {
    $serverpath = $cfg['path']['frontend'];
}

$tpl->set('d', 'CATNAME', i18n("Server path"));
$oTxtServer = new cHTMLTextbox("frontendpath", conHtmlSpecialChars($serverpath), 75, 255);
$tpl->set('d', 'CATFIELD', $oTxtServer->render());
$tpl->set('d', 'BRDRT', 0);
$tpl->set('d', 'BRDRB', 1);
$tpl->next();

if ($htmlpath == '') {
    $htmlpath = 'http://';
}

$tpl->set('d', 'CATNAME', i18n("Web address"));
$oTxtWeb = new cHTMLTextbox("htmlpath", conHtmlSpecialChars($htmlpath), 75, 255);
$tpl->set('d', 'CATFIELD', $oTxtWeb->render());
$tpl->set('d', 'BRDRT', 0);
$tpl->set('d', 'BRDRB', 1);
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("Error page category"));
$oTxtErrorCat = new cHTMLTextbox("errsite_cat", $oClient->get("errsite_cat"), 10, 10);
$tpl->set('d', 'CATFIELD', $oTxtErrorCat->render());
$tpl->set('d', 'BRDRT', 0);
$tpl->set('d', 'BRDRB', 1);
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("Error page article"));
$oTxtErrorArt = new cHTMLTextbox("errsite_art", $oClient->get("errsite_art"), 10, 10);
$tpl->set('d', 'CATFIELD', $oTxtErrorArt->render());
$tpl->set('d', 'BRDRT', 0);
$tpl->set('d', 'BRDRB', 1);
$tpl->next();

$clientLogo = $properties->getValue("idclient", $idclient, "backend", "clientimage");
$tpl->set('d', 'CATNAME', i18n("Client logo"));
$oTxtLogo = new cHTMLTextbox("clientlogo", $clientLogo, 75, 255);
$tpl->set('d', 'CATFIELD', $oTxtLogo->render());
$tpl->set('d', 'BRDRT', 0);
$tpl->set('d', 'BRDRB', 1);
$tpl->next();

$aChoices = array('no' => i18n('No'), 'yes' => i18n('Yes'));

$oXHTMLSelect = new cHTMLSelectElement('generate_xhtml');
$oXHTMLSelect->autoFill($aChoices);

$cApiClient = new cApiClient;
$cApiClient->loadByPrimaryKey($idclient);
if ($cApiClient->getProperty('generator', 'xhtml') == 'true') {
    $oXHTMLSelect->setDefault('yes');
} else {
    $oXHTMLSelect->setDefault('no');
}

$tpl->set('d', 'CATNAME', i18n('Generate XHTML'));
$tpl->set('d', 'CATFIELD', $oXHTMLSelect->render());
$tpl->set('d', 'BRDRT', 0);
$tpl->set('d', 'BRDRB', 1);

$tpl->next();

$aChoices = array('on' => i18n('On'), 'off' => i18n('Off'));

$oXHTMLSelect = new cHTMLSelectElement('statistic');
$oXHTMLSelect->autoFill($aChoices);

$cApiClient->loadByPrimaryKey($idclient);
if ($cApiClient->getProperty('stats', 'tracking') == 'off') {
    $oXHTMLSelect->setDefault('off');
} else {
    $oXHTMLSelect->setDefault('on');
}


$tpl->set('d', 'CATNAME', i18n('Statistic'));
$tpl->set('d', 'CATFIELD', $oXHTMLSelect->render());
$tpl->set('d', 'BRDRT', 0);
$tpl->set('d', 'BRDRB', 1);

$tpl->next();

if ($new == true) {
    $tpl->set('d', 'CATNAME', i18n('Copy frontend template'));
    $defaultform = new cHTMLCheckbox('copytemplate', 'checked', 'copytemplatechecked', true);
    $tpl->set('d', 'CATFIELD', $defaultform->toHTML(false));
    $tpl->next();
}
$tpl->set('s', 'IDCLIENT', $idclient);

// Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['client_edit']);
