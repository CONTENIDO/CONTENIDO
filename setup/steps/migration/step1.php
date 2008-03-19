<?php
checkAndInclude("steps/forms/systemdata.php");

$cSetupSystemData = new cSetupSystemData(1, "setuptype", "migration2");
$cSetupSystemData->render();

?>