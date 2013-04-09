<?php
defined('CON_FRAMEWORK') or die('Illegal call');
//$oUi = new UI_Left_Top();
//$oUi->render();

$oUi = new cTemplate();
$oUi->set("s", "ACTION", '');
$oUi->generate($cfg["path"]["templates"] . $cfg["templates"]["left_top"]);

?>