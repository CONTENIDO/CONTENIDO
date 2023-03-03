<?php

/**
 * This file contains the backend page for uploading a new file.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var cPermission $perm
 * @var array $cfg
 */

cInclude("includes", "functions.upl.php");

// Display critical error if client or language does not exist
$client = cSecurity::toInteger(cRegistry::getClientId());
$lang = cSecurity::toInteger(cRegistry::getLanguageId());
if (($client < 1 || !cRegistry::getClient()->isLoaded()) || ($lang < 1 || !cRegistry::getLanguage()->isLoaded())) {
    $message = $client && !cRegistry::getClient()->isLoaded() ? i18n('No Client selected') : i18n('No language selected');
    $oPage = new cGuiPage("upl_files_upload");
    $oPage->displayCriticalError($message);
    $oPage->render();
    return;
}

$page = new cGuiPage("upl_files_upload");

if (!$perm->have_perm_area_action("upl", "upl_upload")) {
    $page->displayCriticalError(i18n("Permission denied"));
    $page->render();
    die();
}

$cfgClient = cRegistry::getClientConfig();

$maxUploadSize = 0;
$maxPostSize = 0;

// max upload size
if (ini_get("max_upload_size") == "") {
    $maxUploadSize = (double) 99999999999999;
} else {
    $maxUploadSize = machineReadableSize(ini_get("max_upload_size"));
}

// max post size
if (ini_get("post_max_size") == "") {
    $maxPostSize = (double) 99999999999999;
} else {
    $maxPostSize = machineReadableSize(ini_get("post_max_size"));
}

$path = $path ?? '';

if ((cFileHandler::writeable($cfgClient[$client]["upl"]["path"] . $path) || cApiDbfs::isDbfs($path)) && $client > 0) {
    $page->displayWarning(sprintf(i18n("Please note that you can only upload files up to a size of %s"), humanReadableSize(min($maxUploadSize, $maxPostSize))));

    if (cApiDbfs::isDbfs($path)) {
        $mpath = $path . "/";
    } else {
        $mpath = "upload/" . $path;
    }
    $sDisplayPath = generateDisplayFilePath($mpath, 85);
    $page->set("s", "DISPLAY_PATH", $sDisplayPath);

    $appendparameters = $_REQUEST['appendparameters'] ?? '';
    if (!in_array($appendparameters, ['imagebrowser', 'filebrowser'])) {
        $appendparameters = '';
    }
    $page->set("s", "APPENDPARAMETERS", $appendparameters);

    $page->set("s", "PATH", $path);
    $page->set("s", "MAX_FILE_SIZE", min($maxUploadSize, $maxPostSize));
} else {
    $page->displayCriticalError(i18n("Directory not writable") . ' (' . $cfgClient[$client]["upl"]["path"] . $path . ')');
}

$page->reloadLeftBottomFrame(['action' => null, 'path' => $path]);
$page->render();
