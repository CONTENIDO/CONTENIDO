<?php
checkAndInclude("steps/forms/installer.php");

$cSetupInstaller = new cSetupInstaller(6);
$cSetupInstaller->render();
?>