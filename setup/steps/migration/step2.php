<?php
checkAndInclude("steps/forms/pathinfo.php");

$cSetupConfigMode = new cSetupPath(2, "migration1", "migration3");
$cSetupConfigMode->render();

?>