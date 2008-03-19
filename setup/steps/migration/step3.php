<?php
checkAndInclude("steps/forms/configmode.php");

$cSetupConfigMode = new cSetupConfigMode(3, "migration2", "migration4");
$cSetupConfigMode->render();


?>