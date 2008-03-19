<?php
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