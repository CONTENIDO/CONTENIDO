<?php
checkAndInclude("steps/forms/setupsummary.php");

$cSetupSetupSummary = new cSetupSetupSummary(6, "migration5", "domigration");
$cSetupSetupSummary->render();
?>