<?php
checkAndInclude("steps/forms/setupsummary.php");

$cSetupSetupSummary = new cSetupSetupSummary(6, "setup5", "doinstall");
$cSetupSetupSummary->render();
?>