<?php
checkAndInclude("steps/forms/setupsummary.php");

$cSetupSetupSummary = new cSetupSetupSummary(7, "setup6", "doinstall");
$cSetupSetupSummary->render();
?>