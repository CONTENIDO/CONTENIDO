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
$contenidoPath = str_replace('\\', '/', realpath(__DIR__ . '/../')) . '/';

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
    $articleLanguageId = $obj->get('idartlang');
    $lastUserSequenceId = $obj->get('lastusersequence');

    $userSequence = piwf_getCurrentUserSequence($articleLanguageId, 0);
    if (false === $userSequence) {
        continue;
    }

    if ($userSequence != $lastUserSequenceId) {
        $workflowUserSequences->select('`idusersequence` = ' . $userSequence);

        if (($wfObj = $workflowUserSequences->next()) === false) {
            cWarning("Could not load workflow user sequence '$userSequence'.");
            continue;
        }

        $wfObj = $workflowUserSequences->next();
        $workflowItemId = cSecurity::toInteger($wfObj->get('idworkflowitem'));
        $pos = cSecurity::toInteger($wfObj->get('position'));
        $timeUnit = $wfObj->get('timeunit');
        $timeLimit = cSecurity::toInteger($wfObj->get('timelimit'));

        $startTime = strtotime(
            substr_replace(
                cString::getPartOfString(cString::getPartOfString($startTime, 0, 2)
                    . chunk_split(cString::getPartOfString($startTime, 2, 6), 2, '-')
                    . chunk_split(cString::getPartOfString($startTime, 8), 2, ':'), 0, 19),
                ' ',
                10,
                1
            )
        );

        // TODO Code is redundant with contenido/plugins/workflow/classes/class.workflowartallocation.php
        switch ($timeUnit) {
            case 'Seconds':
                $maxTime = $startTime + $timeLimit;
                break;
            case 'Minutes':
                $maxTime = $startTime + ($timeLimit * 60);
                break;
            case 'Hours':
                $maxTime = $startTime + ($timeLimit * 3600);
                break;
            case 'Days':
                $maxTime = $startTime + ($timeLimit * 86400);
                break;
            case 'Weeks':
                $maxTime = $startTime + ($timeLimit * 604800);
                break;
            case 'Months':
                $maxTime = $startTime + ($timeLimit * 2678400);
                break;
            case 'Years':
                $maxTime = $startTime + ($timeLimit * 31536000);
                break;
            default:
                $maxTime = $startTime + $timeLimit;
        }

        if ($maxTime < time()) {
            $pos = $pos + 1;
            $workflowUserSequences->select("`idworkflowitem` = '$workflowItemId' AND `position` = $pos");
            if ($wfObj = $workflowUserSequences->next()) {
                $obj->set('idusersequence', $wfObj->get('idusersequence'));
                $obj->store();
            }
        }
    }
}
