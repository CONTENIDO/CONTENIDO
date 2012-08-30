<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * External grouprights
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.2
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

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
