<?php

/**
 * This file contains the cronjob to advance workflow.
 * Advances to the next step if the time limit is 'over'
 *
 * @package    Plugin
 * @subpackage Workflow
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

global $cfg;

// CONTENIDO path
$contenidoPath = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../')) . '/';

// CONTENIDO startup process
include_once($contenidoPath . 'includes/startup.php');

require_once($cfg['path']['contenido_config'] . 'cfg_actions.inc.php');
cInclude('includes', 'functions.con.php');

plugin_include('workflow', 'classes/class.workflow.php');
plugin_include('workflow', 'includes/functions.workflow.php');

$workflowArtAllocations = new WorkflowArtAllocations();
$workflowUserSequences = new WorkflowUserSequences();

$workflowArtAllocations->select();

while ($obj = $workflowArtAllocations->next()) {
    $startTime = $obj->get('starttime');
    $idArtLang = $obj->get('idartlang');
    $lastIdUserSequence = $obj->get('lastusersequence');

    $userSequence = getCurrentUserSequence($idArtLang, 0);
    if (false === $userSequence) {
        continue;
    }

    if ($userSequence != $lastIdUserSequence) {
        $workflowUserSequences->select('idusersequence=' . $userSequence);

        if ($wfObj = $workflowUserSequences->next()) {
            $idWorkflowItem = (int) $wfObj->get('idworkflowitem');
            $pos = (int) $wfObj->get('position');
            $timeUnit = $wfObj->get('timeunit');
            $timeLimit = $wfObj->get('timelimit');
        } else {
            continue;
        }

        $startTime = strtotime(
            substr_replace(cString::getPartOfString(cString::getPartOfString($startTime, 0, 2)
            . chunk_split(cString::getPartOfString($startTime, 2, 6), 2, '-')
            . chunk_split(cString::getPartOfString($startTime, 8), 2, ':'), 0, 19), ' ', 10, 1)
        );

        switch ($timeUnit) {
            case 'Seconds':
                $maxTme = $startTime + $timeLimit;
                break;
            case 'Minutes':
                $maxTme = $startTime + ($timeLimit * 60);
                break;
            case 'Hours':
                $maxTme = $startTime + ($timeLimit * 3600);
                break;
            case 'Days':
                $maxTme = $startTime + ($timeLimit * 86400);
                break;
            case 'Weeks':
                $maxTme = $startTime + ($timeLimit * 604800);
                break;
            case 'Months':
                $maxTme = $startTime + ($timeLimit * 2678400);
                break;
            case 'Years':
                $maxTme = $startTime + ($timeLimit * 31536000);
                break;
            default:
                $maxTme = $startTime + $timeLimit;
        }

        if ($maxTme < time()) {
            $pos = $pos + 1;
            $workflowUserSequences->select('idworkflowitem=' . $idWorkflowItem . ' AND position=' . $pos);

            if ($wfObj = $workflowUserSequences->next()) {
                $obj->set('idusersequence', $wfObj->get('idusersequence'));
                $obj->store();
            }
        }
    }
}
