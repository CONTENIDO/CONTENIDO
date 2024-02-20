<?php

/**
 * This file contains the backend page for external rights management.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// @TODO: check if the code beneath is necessary
if (isset($_REQUEST['sAreaFilename'])) {
    die ('Illegal call!');
}

$_cecIterator = $_cecRegistry->getIterator("Contenido.Permissions.User.GetAreaEditFilename");

while (($chainEntry = $_cecIterator->next()) !== false) {
    $aInfo = $chainEntry->execute($_REQUEST["external_area"]);
    if ($aInfo !== false) {
        $sAreaFilename = $aInfo;
        break;
    }
}

if ($sAreaFilename !== false) {
    include($sAreaFilename);
}
