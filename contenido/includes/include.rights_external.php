<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Rights external
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.3
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created  unknown
 *   modified 2008-06-16, Holger Librenz, Hotfix: added check for invalid calls
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2010-05-20, Murat Purc, removed request check during processing ticket [#CON-307]
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

// @TODO: check if the code beneath is necessary
if (isset($_REQUEST['sAreaFilename'])) {
    die ('Illegal call!');
}

$_cecIterator = $_cecRegistry->getIterator("Contenido.Permissions.User.GetAreaEditFilename");

while ($chainEntry = $_cecIterator->next())
{
    $aInfo = $chainEntry->execute($_REQUEST["external_area"]);
    if ($aInfo !== false)
    {
        $sAreaFilename = $aInfo;
        break;
    }
}

if ($sAreaFilename !== false)
{
    include($sAreaFilename);
}
?>