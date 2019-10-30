<?php
/**
 * This file contains the cronjob to advance workflow.
 * Advances to the next step if the time limit is 'over'
 *
 * @package    Plugin
 * @subpackage Workflow
 *
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
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

$workflowartallocations = new WorkflowArtAllocations();
$workflowusersequences = new WorkflowUserSequences();

$workflowartallocations->select();

while ($obj = $workflowartallocations->next()) {
    $starttime = $obj->get('starttime');
    $idartlang = $obj->get('idartlang');
    $lastidusersequence = $obj->get('lastusersequence');

    $usersequence = getCurrentUserSequence($idartlang, 0);
    if (false === $usersequence) {
        continue;
    }

    if ($usersequence != $lastidusersequence) {
        $workflowusersequences->select('idusersequence=' . $usersequence);

        if ($wfobj = $workflowusersequences->next()) {
            $wfitem = (int) $wfobj->get('idworkflowitem');
            $pos = (int) $wfobj->get('position');
            $timeunit = $wfobj->get('timeunit');
            $timelimit = $wfobj->get('timelimit');
        }

        $starttime = strtotime(
            substr_replace(cString::getPartOfString(cString::getPartOfString($starttime, 0, 2)
            . chunk_split(cString::getPartOfString($starttime, 2, 6), 2, '-')
            . chunk_split(cString::getPartOfString($starttime, 8), 2, ':'), 0, 19), ' ', 10, 1)
        );

        switch ($timeunit) {
            case 'Seconds':
                    $maxtime = $starttime + $timelimit;
                    break;
            case 'Minutes':
                    $maxtime = $starttime + ($timelimit * 60);
                    break;
            case 'Hours':
                    $maxtime = $starttime + ($timelimit * 3600);
                    break;
            case 'Days':
                    $maxtime = $starttime + ($timelimit * 86400);
                    break;
            case 'Weeks':
                    $maxtime = $starttime + ($timelimit * 604800);
                    break;
            case 'Months':
                    $maxtime = $starttime + ($timelimit * 2678400);
                    break;
            case 'Years':
                    $maxtime = $starttime + ($timelimit * 31536000);
                    break;
            default:
                    $maxtime = $starttime + $timelimit;
        }

        if ($maxtime < time()) {
            $pos = $pos + 1;
            $workflowusersequences->select('idworkflowitem=' . $wfitem . ' AND position=' . $pos);

            if ($wfobj = $workflowusersequences->next()) {
                $obj->set('idusersequence', $wfobj->get('idusersequence'));
                $obj->store();
            }
        }
    }
}

?>