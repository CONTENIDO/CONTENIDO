<?php
checkAndInclude("steps/forms/configmode.php");

$cSetupConfigMode = new cSetupConfigMode(3, "upgrade2", "upgrade4");
$cSetupConfigMode->render();


?>