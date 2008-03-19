<?php
checkAndInclude("steps/forms/systemdata.php");

$cSetupSystemData = new cSetupSystemData(1, "setuptype", "upgrade2");
$cSetupSystemData->render();

?>