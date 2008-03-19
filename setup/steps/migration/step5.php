<?php
checkAndInclude("steps/forms/clientadjust.php");

$cSetupSetupSummary = new cSetupClientAdjust(5, "migration4", "migration6");
$cSetupSetupSummary->render();
?>