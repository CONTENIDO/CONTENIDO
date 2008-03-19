<?php
checkAndInclude("steps/forms/clientmode.php");

$cSetupClientMode = new cSetupClientMode(5, "setup4", "setup6", true);
$cSetupClientMode->render();

?>