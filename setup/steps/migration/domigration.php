<?php
checkAndInclude("steps/forms/installer.php");

$cSetupInstaller = new cSetupInstaller(7);
$cSetupInstaller->render();
?>