<?php

/**
 * This file contains the advances to the next step if the time limit is "over".
 *
 * @package    Plugin
 * @subpackage Workflow
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var array $cfg
 */

// CONTENIDO startup process
include_once('../../../includes/startup.php');

require_once($cfg['path']['contenido_config'] . 'cfg_actions.inc.php');
cInclude('includes', 'functions.con.php');

$workflowArtAllocations = new WorkflowArtAllocations();
$workflowUserSequences = new WorkflowUserSequences();

$workflowArtAllocations->select();

while ($obj = $workflowArtAllocations->next()) {
    $startTime = $obj->get('starttime');
    $idartlang = $obj->get('idartlang');
    $lastUserSequenceId = $obj->get('lastusersequence');

    $userSequence = piwf_getCurrentUserSequence($idartlang, 0);

    if ($userSequence != $lastUserSequenceId) {
        $workflowUserSequences->select("`idusersequence` = '$userSequence'");

        if (($wfObj = $workflowUserSequences->next()) === false) {
            cWarning("Could not load workflow user sequence '$userSequence'.");
            continue;
        }

        $wfObj = $workflowUserSequences->next();
        $workflowItemId = cSecurity::toInteger($wfObj->get('idworkflowitem'));
        $pos = cSecurity::toInteger($wfObj->get('position'));
        $timeUnit = $wfObj->get('timeunit');
        $timeLimit = cSecurity::toInteger($wfObj->get('timelimit'));

        $startTime = strtotime($startTime);

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
            if (($wfObj = $workflowUserSequences->next()) !== false) {
                $obj->set('idusersequence', $wfObj->get('idusersequence'));
                $obj->store();
            }
        }
    }
}
