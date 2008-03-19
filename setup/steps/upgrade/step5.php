<?php
/**
* $RCSfile$
*
* Description: Step 6 of installation
*
* @version 1.0.0
* @author Rudi Bieller
* @copyright four for business AG <www.4fb.de>
*
* {@internal
* created 2008-03-14
* }}
*
* $Id$
*/
checkAndInclude("steps/forms/additionalplugins.php");

$cSetupSetupSummary = new cSetupAdditionalPlugins(5, "upgrade4", "upgrade6");
$cSetupSetupSummary->render();
?>