<?php
checkAndInclude("steps/forms/setupsummary.php");

$cSetupSetupSummary = new cSetupSetupSummary(5, "upgrade4", "doupgrade");
$cSetupSetupSummary->render();
?>