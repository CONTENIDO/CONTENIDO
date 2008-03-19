<?php
checkAndInclude("steps/forms/pathinfo.php");

$cSetupConfigMode = new cSetupPath(2, "upgrade1", "upgrade3");
$cSetupConfigMode->render();

?>