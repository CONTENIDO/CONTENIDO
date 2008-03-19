<?php

include_once ($cfg["path"]["classes"] . 'class.ui.php');

$create = new Link;
$create->setMultiLink("workflow","","workflow_common","workflow_create");
//$create->setCLink("workflow_common",4,"workflow_create");
$create->setContent(i18n("Create workflow", "workflow"));
$create->setCustom("idworkflow","-1");

$aAttributes = array();
$aAttributes['class'] = "addfunction";
$create->updateAttributes($aAttributes);

$ui = new UI_Left_Top;
$ui->setLink($create);
$ui->render();

?>