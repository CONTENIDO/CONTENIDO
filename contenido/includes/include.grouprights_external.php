<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * External grouprights
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.2
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created  unknown
 *   modified 2008-06-16, Holger Librenz, Hotifc: added check for invalid calls
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2010-05-20, Murat Purc, removed request check during processing ticket [#CON-307]
 *
 *   $Id: include.grouprights_external.php 1157 2010-05-20 14:10:43Z xmurrix $:
 * }}
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

while ($chainEntry = $_cecIterator->next())
{
    // @TODO: This has to be refactored because this could cause SQL-Injection, Remote-File-Inclusion ....
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