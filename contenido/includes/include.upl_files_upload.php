<?php
/**
 * This file contains the backend page for uploading a new file.
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

cInclude("includes", "functions.upl.php");

$page = new cGuiPage("upl_files_upload");

if(!$perm->have_perm_area_action($area, "upl_upload")) {
    $page->displayCriticalError(i18n("Permission denied"));
    $page->render();
    die();
}

$maxUploadSize = 0;
$maxPostSize = 0;

if (ini_get("max_upload_size") == "") {
    $maxUploadSize = (double) 99999999999999;
} else {
    $maxUploadSize = machineReadableSize(ini_get("max_upload_size"));
}
if (ini_get("post_max_size") == "") {
    $maxPostSize = (double) 99999999999999;
} else {
    $maxPostSize = machineReadableSize(ini_get("post_max_size"));
}

if ((cFileHandler::writeable($cfgClient[$client]["upl"]["path"] . $path) || cApiDbfs::isDbfs($path)) && (int) $client > 0) {
    $page->displayWarning(sprintf(i18n("Please note that you can only upload files up to a size of %s"), humanReadableSize(min($maxUploadSize, $maxPostSize))));

    if (cApiDbfs::isDbfs($path)) {
        $mpath = $path . "/";
    } else {
        $mpath = "upload/" . $path;
    }
    $sDisplayPath = generateDisplayFilePath($mpath, 85);
    $page->set("s", "DISPLAY_PATH", $sDisplayPath);

    if ($_REQUEST['appendparameters'] == "imagebrowser") {
        $page->set("s", "APPENDPARAMETERS", "imagebrowser");
    }

    if ($_REQUEST['appendparameters'] == "filebrowser") {
        $page->set("s", "APPENDPARAMETERS", "filebrowser");
    }

    $page->set("s", "PATH", $path);
    $page->set("s", "MAX_FILE_SIZE", min($maxUploadSize, $maxPostSize));
} else {
    $page->displayCriticalError(i18n("Directory not writable") . ' (' . $cfgClient[$client]["upl"]["path"] . $path . ')');
}

$page->render();

?>