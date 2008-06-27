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
 * @version    1.0.1
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-16, Holger Librenz, Hotifc: added check for invalid calls
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

if (isset($_REQUEST['cfg']) || isset($_REQUEST['contenido_path']) || isset($_REQUEST['sAreaFilename'])) {
    die ('Invalid call!');
}

$_cecIterator = $_cecRegistry->getIterator("Contenido.Permissions.Group.GetAreaEditFilename");

while ($chainEntry = $_cecIterator->next())
{
    // @todo This has to be refactored because this could cause SQL-Injection, Remote-File-Inclusion ....
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