<?php
checkAndInclude("steps/forms/setupresults.php");

$cSetupResults = new cSetupResults(7);
$cSetupResults->render();
?>