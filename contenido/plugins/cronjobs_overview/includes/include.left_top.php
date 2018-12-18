<?php
/**
 * This file contains the left top frame backend page for the plugin cronjob overview.
 *
 * @package    Plugin
 * @subpackage CronjobOverview
 * @author     Rusmir Jusufovic
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

//Has the user permission for crontab_edit
if (!$perm->have_perm_area_action($area, 'crontab_edit'))
{
    $notification->displayNotification('error', i18n('Permission denied', 'cronjobs_overview'));
    return -1;
}

$tpl = new cTemplate();


$tpl->set('s', 'LABLE_CRONJOB_EDIT', i18n('Edit cronjob', 'cronjobs_overview'));
$tpl->set('s', 'ROW', 'javascript:Con.multiLink(\'right_bottom\', \''.$sess->url("main.php?area=cronjob&frame=4&action=crontab_edit&file=$file").'\', \'left_bottom\',\''.$sess->url("main.php?area=cronjob&frame=2").'\');');
$tpl->generate(cRegistry::getBackendPath() .  $cfg['path']['plugins'] . "cronjobs_overview/templates/left_top.html");

?>