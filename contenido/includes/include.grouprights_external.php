<?php
/**
 * Grouprights
 *
 * @version $Revision$
 * @copyright four for business AG
 *
 * @internal {
 *  modified 2008-06-16, H. Librenz - Hotfix: added check for invalid calls
 *
 *  $Id$
 * }
 */
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