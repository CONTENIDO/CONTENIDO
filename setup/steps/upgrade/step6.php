<?php
checkAndInclude("steps/forms/setupresults.php");

$cSetupResults = new cSetupResults(6);
$cSetupResults->render();
?>