<?php
checkAndInclude("steps/forms/systemtest.php");

$cSetupSystemtest = new cSetupSystemtest(4, "migration3", "migration5", true);
$cSetupSystemtest->render();

?>