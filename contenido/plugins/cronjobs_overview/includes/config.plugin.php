<?php
/**
 * Configuration file for the plugin content allocation.
 *
 * @package    Plugin
 * @subpackage CronjobOverview
 * @author     Rusmir Jusufovic
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

//include cronjobs class
include_once(dirname(__FILE__).'/../classes/class.cronjobs.php');

//dir plugin
$dir_plugin = dirname(__FILE__).'/../';

global $lngAct;
$lngAct['cronjob']['cronjob_overview'] = i18n('Cronjob overview', 'cronjobs_overview');
$lngAct['cronjob']['crontab_edit'] = i18n('Edit cronjob', 'cronjobs_overview');
$lngAct['cronjob']['cronjob_execute'] = i18n('Execute cronjob', 'cronjobs_overview');

?>
