<?php
checkAndInclude("steps/forms/systemtest.php");

$cSetupSystemtest = new cSetupSystemtest(4, "setup3", "setup5", true);
$cSetupSystemtest->render();

?>