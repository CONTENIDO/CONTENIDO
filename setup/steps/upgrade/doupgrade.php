<?php
checkAndInclude("steps/forms/installer.php");

$cSetupInstaller = new cSetupInstaller(5);
$cSetupInstaller->render();
?>