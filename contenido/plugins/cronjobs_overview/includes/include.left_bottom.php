<?php
/**
 * This file contains the left bottom frame backend page for the plugin cronjob overview.
 *
 * @package    Plugin
 * @subpackage CronjobOverview
 * @author     Rusmir Jusufovic
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

//Has the user permission for view the cronjobs
if (!$perm->have_perm_area_action($area, 'cronjob_overview')) {
    $notification->displayNotification('error', i18n('Permission denied', 'cronjobs_overview'));
    return -1;
}

// TODO: this should not be necessary
include_once(dirname(__FILE__).'/config.plugin.php');

$tpl = new cTemplate();
$cronjobs = new Cronjobs();

//include(cRegistry::getBackendPath() . $cfg['path']['templates'] . 'template.left_top_blank.html');
foreach ($cronjobs->getAllCronjobs() as $row) {
    $tpl->set('d','FILE', $row);
    $file = urlencode($row);
    $tpl->set('d', 'ROW', 'javascript:Con.multiLink(\'right_bottom\', \''.$sess->url("main.php?area=cronjob&frame=4&action=cronjob_overview&file=$file").'\');');
    $tpl->next();
}

$tpl->generate($dir_plugin.'templates/left_bottom.html');

?>