<?php

/**
 * This file contains the backend page for external group rights management.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Unknown
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// @TODO: check the code beneath is necessary
if (isset($_REQUEST['sAreaFilename'])) {
    die ('Invalid call!');
}

$_cecIterator = $_cecRegistry->getIterator("Contenido.Permissions.Group.GetAreaEditFilename");

while (($chainEntry = $_cecIterator->next()) !== false) {
    // @TODO: This has to be refactored because this could cause SQL-Injection, Remote-File-Inclusion ....
    $aInfo = $chainEntry->execute($_REQUEST["external_area"]);
    if ($aInfo !== false) {
        $sAreaFilename = $aInfo;
        break;
    }
}

if ($sAreaFilename !== false) {
    include($sAreaFilename);
}
