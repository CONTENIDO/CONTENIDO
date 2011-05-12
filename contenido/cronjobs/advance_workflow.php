<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Advances to the next step if the time limit is "over"
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.5.2
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created  2003-05-26
 *   modified 2010-05-20, Murat Purc, standardized Contenido startup and security check invocations, see [#CON-307]
 *   modified 2011-05-12, Dominik Ziegler, forced include of startup.php [#CON-390]
 *   
 *   $Id: advance_workflow.php,v 1.5 2004/06/17 15:06:08 timo.hummel Exp $
 * }}
 * 
 */

if (!defined("CON_FRAMEWORK")) {
    define("CON_FRAMEWORK", true);
}

// Contenido startup process
include_once ('../includes/startup.php');

cInclude("includes", "cfg_language_de.inc.php");
cInclude("includes", "functions.con.php");

plugin_include('workflow', 'classes/class.workflow.php');
plugin_include('workflow', 'includes/functions.workflow.php');

$workflowartallocations = new WorkflowArtAllocations;
$workflowusersequences = new WorkflowUserSequences;

$workflowartallocations->select();

while ($obj = $workflowartallocations->next())
{
	$starttime = $obj->get("starttime");
	$idartlang = $obj->get("idartlang");
	$lastidusersequence = $obj->get("lastusersequence");
	
	$usersequence = getCurrentUserSequence($idartlang,0);
	
	if ($usersequence != $lastidusersequence)
	{
    		
    	$workflowusersequences->select("idusersequence = '$usersequence'");
    	
    	if ($wfobj = $workflowusersequences->next())
    	{
    		$wfitem = $wfobj->get("idworkflowitem");
    		$pos = $wfobj->get("position");
    		$timeunit = $wfobj->get("timeunit");
    		$timelimit = $wfobj->get("timelimit");
    	}
    	
    	$starttime = strtotime (substr_replace (substr (substr ($starttime,0,2).chunk_split (substr ($starttime,2,6),2,"-").chunk_split (substr ($starttime,8),2,":"),0,19)," ",10,1));
    	switch ($timeunit)
    	{
    		case "Seconds":
    				$maxtime = $starttime + $timelimit;
    				break;
    		case "Minutes":
    				$maxtime = $starttime + ($timelimit * 60);
    				break;
    		case "Hours":
    				$maxtime = $starttime + ($timelimit * 3600);
    				break;
    		case "Days":
    				$maxtime = $starttime + ($timelimit * 86400);
    				break;
    		case "Weeks":
    				$maxtime = $starttime + ($timelimit * 604800);
    				break;
    		case "Months":
    				$maxtime = $starttime + ($timelimit * 2678400);
    				break;
    		case "Years":
    				$maxtime = $starttime + ($timelimit * 31536000);
    				break;
    		default:
    				$maxtime = $starttime + $timelimit;
    	}
    	
    	
    	
    	if ($maxtime < time())
    	{
    		$pos = $pos + 1;
    		$workflowusersequences->select("idworkflowitem = '$wfitem' AND position = '".Contenido_Security::escapeDB($pos, NULL)."'");
    		
    		if ($wfobj = $workflowusersequences->next())
    		{
    			$obj->set("idusersequence", $wfobj->get("idusersequence"));
    			$obj->store();
    
    		}
    	}
			
	}	
}

?>
