<?php

/*****************************************
* File      :   $RCSfile: advance_workflow.php,v $
* Project   :   Contenido Wirkflow
* Descr     :   Advances to the next step if the time limit is "over"
*
* Author    :   $Author: timo.hummel $
*               
* Created   :   26.05.2003
* Modified  :   $Date: 2004/06/17 15:06:08 $
*
* © four for business AG, www.4fb.de
*
* $Id: advance_workflow.php,v 1.5 2004/06/17 15:06:08 timo.hummel Exp $
******************************************/

include_once ('../../../includes/startup.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["classes"] . 'class.user.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["classes"] . 'class.xml.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["classes"] . 'class.navigation.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["classes"] . 'class.template.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["classes"] . 'class.backend.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["classes"] . 'class.table.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["classes"] . 'class.notification.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["classes"] . 'class.area.php');

include_once ($cfg['path']['contenido'].$cfg["path"]["classes"] . 'class.layout.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["classes"] . 'class.client.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["classes"] . 'class.cat.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["classes"] . 'class.treeitem.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["includes"] . 'cfg_sql.inc.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["includes"] . 'cfg_language_de.inc.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["includes"] . 'functions.general.php');
include_once ('../../../includes/functions.con.php');
include_once ($cfg["path"]['contenido'] . $cfg["path"]["plugins"] . "workflow/classes/class.workflow.php"); 
include_once ($cfg["path"]['contenido'] . $cfg["path"]["plugins"] . "workflow/includes/functions.workflow.php");


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
    		$workflowusersequences->select("idworkflowitem = '$wfitem' AND position = '$pos'");
    		
    		if ($wfobj = $workflowusersequences->next())
    		{
    			$obj->set("idusersequence", $wfobj->get("idusersequence"));
    			$obj->store();
    
    		}
    	}
			
	}	
}

?>