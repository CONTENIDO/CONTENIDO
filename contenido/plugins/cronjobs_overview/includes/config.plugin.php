<?php
if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}
//include cronjobs class
include_once(dirname(__FILE__).'/../classes/class.cronjobs.php');
//dir plugin
$dir_plugin = dirname(__FILE__).'/../';


global $lngAct;

$lngAct['cronjob']['cronjob_overview'] = i18n('Cronjob overview', 'cronjob_overview');
$lngAct['cronjob']['crontab_edit'] = i18n('Edit cronjob', 'cronjob_overview');
$lngAct['cronjob']['cronjob_execute'] = i18n('Execute cronjob', 'cronjob_overview');

?>
