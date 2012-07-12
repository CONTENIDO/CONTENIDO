<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Upload files
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.7.1
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created 2003-12-30
 *   $Id$:
 * }}
 */
if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude("includes", "functions.upl.php");

$page = new cGuiPage("upl_files_upload");

if ((cFileHandler::writeable($cfgClient[$client]["upl"]["path"].$path) || cApiDbfs::isDbfs($path)) && (int) $client > 0)
{
    if (cApiDbfs::isDbfs($path)) {
        $mpath = $path."/";
    } else {
        $mpath = "upload/".$path;
    }
    $sDisplayPath = generateDisplayFilePath($mpath, 85);
    $page->set("s", "DISPLAY_PATH", $sDisplayPath);

    $page->set("s", "PATH", $path);
} else {
    $page->displayCriticalError(i18n("Directory not writable") . ' (' . $cfgClient[$client]["upl"]["path"].$path . ')');
}
$page->render();
?>