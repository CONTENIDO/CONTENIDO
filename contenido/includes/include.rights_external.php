<?php
/**
 * Rights External
 *
 * @version $Revision$
 * @copyright four for business AG <www.4fb.de>
 *
 * @internal {
 *  modified 2008-06-16, H. Librenz - Hotfix: Added check for invalid calls.
 *
 *  $Id$
 * }
 */
if (isset($_REQUEST['cfg']) || isset($_REQUEST['contenido_path']) || isset($_REQUEST['sAreaFilename'])) {
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