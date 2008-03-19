<?php
checkAndInclude("steps/forms/systemdata.php");

$cSetupSystemData = new cSetupSystemData(1, "setuptype", "setup2");
$cSetupSystemData->render();

?>