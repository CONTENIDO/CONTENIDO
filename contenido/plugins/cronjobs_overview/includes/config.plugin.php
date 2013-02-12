<?php
if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}
//include cronjobs class
include_once(dirname(__FILE__).'/../classes/class.cronjobs.php');
//dir plugin
$dir_plugin = dirname(__FILE__).'/../';


global $lngAct;

$lngAct['cronjob']['cronjob_overview'] = i18n('Cronjob overview', 'cronjobs_overview');
$lngAct['cronjob']['crontab_edit'] = i18n('Edit cronjob', 'cronjobs_overview');
$lngAct['cronjob']['cronjob_execute'] = i18n('Execute cronjob', 'cronjobs_overview');

?>
