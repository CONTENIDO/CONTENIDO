<?php
checkAndInclude("steps/forms/setupsummary.php");

$cSetupSetupSummary = new cSetupSetupSummary(6, "upgrade5", "doupgrade");
$cSetupSetupSummary->render();
?>