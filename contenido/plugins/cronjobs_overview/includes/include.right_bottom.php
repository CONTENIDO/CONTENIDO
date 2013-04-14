<?php
/**
 * This file contains the right bottom frame backend page for the plugin cronjob overview.
 *
 * @package    Plugin
 * @subpackage CronjobOverview
 * @version    SVN Revision $Rev:$
 *
 * @author     Rusmir Jusufovic
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

// TODO: this should not be necessary
include_once(dirname(__FILE__).'/config.plugin.php');

$tpl = new cTemplate();
$contenidoVars = array('cfg' => $cfg);

$cronjobs = new Cronjobs($contenidoVars, $_REQUEST['file']);
$notification = new cGuiNotification();

switch ($_REQUEST['action']) {
    case 'cronjob_overview':
        if (!$perm->have_perm_area_action($area, 'cronjob_overview')) {
            $notification->displayNotification('error', i18n('Permission denied', 'cronjobs_overview'));
            return -1;
        }

        if ($cronjobs->existFile()) {
            $tpl->set('s', 'HEADER', i18n('Cronjob overview: ', 'cronjobs_overview').$cronjobs->getFile());
            $tpl->set('s', 'LABLE_DIRECTORY',i18n("Location", 'cronjobs_overview'));
            $tpl->set('s', 'DIRECTORY', $cronjobs->getCronjobDirectory());
            $tpl->set('s', 'LABLE_EXECUTION_TIME', i18n("Last time executed: ", 'cronjobs_overview'));
            $tpl->set('s', 'LABLE_LOG', i18n('Log', 'cronjobs_overview'));
            $tpl->set('s', 'LOG', $cronjobs->getLastLines());
            $tpl->set('s', 'EXECUTION_TIME',$cronjobs->getDateLastExecute());
            $tpl->set('s', 'CONTENIDO', $contenido);
            $tpl->set('s', 'LABLE_EXECUTE',i18n("Execute cronjob:", 'cronjobs_overview'));
            $tpl->set('s', 'ALT_TITLE', i18n('Execute cronjob', 'cronjobs_overview'));
            $tpl->set('s', 'FILE', $cronjobs->getFile());
            $tpl->generate($dir_plugin.'templates/right_bottom_overview.html');
        }
        break;

    case 'crontab_edit':
        if (!$perm->have_perm_area_action($area, 'crontab_edit')) {
            $notification->displayNotification("error", i18n("Permission denied", 'cronjobs_overview'));
            return -1;
        }
        if (!empty($_POST['crontab_contents'])) {
            //save contents
            if ($cronjobs->saveCrontabFile($_POST['crontab_contents']) === FALSE) {
                $notification-> displayNotification('info', i18n('Could not be saved!', 'cronjobs_overview'));
            } else {
                $notification-> displayNotification('info', i18n('Successfully saved!', 'cronjobs_overview'));
            }
        }
        $tpl->set('s', 'HEADER', i18n('Edit cronjob: ', 'cronjobs_overview').$cronjobs->getCronlogDirectory(). Cronjobs::$CRONTAB_FILE);
        $tpl->set('s', 'CONTENTS', $cronjobs->getContentsCrontabFile());
        $tpl->set('s', 'CONTENIDO', $contenido);
        $tpl->set('s', 'ALT_TITLE', i18n('Save changes', 'cronjobs_overview'));
        $tpl->generate($dir_plugin.'templates/right_bottom_crontab_edit.html');
        break;

    case 'cronjob_execute':
        if (!$perm->have_perm_area_action($area, 'cronjob_execute')) {
            $notification->displayNotification("error", i18n("Permission denied", 'cronjobs_overview'));
            return -1;
        }

        if ($cronjobs->existFile()) {
            $area = 'cronjobs';
            include($cronjobs->getCronjobDirectory().$cronjobs->getFile());
            $cronjobs->setRunTime(time());
            //$cronjobs->executeCronjob();
            $notification->displayNotification('info', i18n('File has been included!', 'cronjobs_overview'));
        }
        break;
}

?>