<?php
checkAndInclude("steps/forms/setupsummary.php");

$cSetupSetupSummary = new cSetupSetupSummary(7, "migration6", "domigration");
$cSetupSetupSummary->render();
?>