<?php
checkAndInclude("steps/forms/systemtest.php");

$cSetupSystemtest = new cSetupSystemtest(4, "upgrade3", "upgrade5", true);
$cSetupSystemtest->render();

?>