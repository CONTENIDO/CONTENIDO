<?php
checkAndInclude("steps/forms/setupresults.php");

$cSetupResults = new cSetupResults(8);
$cSetupResults->render();
?>