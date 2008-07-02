<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Workflow functions
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.8
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2003-07-28
 *   
 *   $Id: functions.workflow.php,v 1.8 2006/01/13 15:54:41 timo.hummel Exp $
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "class.security.php");
cInclude("includes", "functions.con.php");

plugin_include('workflow', 'classes/class.workflowitems.php');

function getUsers ($listid, $default)
{
	global $idclient, $cfg, $auth;
	
	$userlist = new Users;
	$users = $userlist->getAccessibleUsers(split(',',$auth->auth["perm"]));
	$grouplist = new Groups;
	$groups = $grouplist->getAccessibleGroups(split(',',$auth->auth["perm"]));
	
	$tpl2 = new Template;    
	$tpl2->set('s', 'NAME', 'user'.$listid);
    $tpl2->set('s', 'CLASS', 'text_small');
    $tpl2->set('s', 'OPTIONS', 'size=1');

	$tpl2->set('d', 'VALUE', 0);
	$tpl2->set('d', 'CAPTION', '--- '.i18n("None", "workflow").' ---');
	if ($default == 0)
	{
		$tpl2->set('d', 'SELECTED', 'SELECTED');
	} else {
		$tpl2->set('d', 'SELECTED', '');
	}
	$tpl2->next();
	
    if ( is_array($users) )
    {

        foreach ($users as $key => $value)
        {

    		$tpl2->set('d', 'VALUE', $key);
    		$tpl2->set('d', 'CAPTION', $value["realname"] . " (".$value["username"].")");
    		
    		if ($default == $key)
    		{
    			$tpl2->set('d', 'SELECTED', 'SELECTED');
    		} else {
    			$tpl2->set('d', 'SELECTED', '');
    		}
    		
            $tpl2->next();
        }    
    } 

	$tpl2->set('d', 'VALUE', '0');
	$tpl2->set('d', 'CAPTION', '------------------------------------');
    $tpl2->set('d', 'SELECTED', 'disabled');
	$tpl2->next();
	
    if ( is_array($groups) )
    {

        foreach ($groups as $key => $value)
        {

    		$tpl2->set('d', 'VALUE', $key);
    		$tpl2->set('d', 'CAPTION', $value["groupname"]);
    		
    		if ($default == $key)
    		{
    			$tpl2->set('d', 'SELECTED', 'SELECTED');
    		} else {
    			$tpl2->set('d', 'SELECTED', '');
    		}
    		
            $tpl2->next();
        }
            
    } 

	return $tpl2->generate($cfg['path']['templates'].$cfg['templates']['generic_select'], true);
	
}

function isCurrentEditor ($uid)
{
	global $auth, $cfg;
   
    /* Check if the UID is a group. If yes, check if we are in it */
    $user = new User;
    if ($user->loadUserByUserID($uid) == false)
    {
    	$db2 = new DB_Contenido;
    	
    	/* Yes, it's a group. Let's try to load the group members! */
    	$sql = "SELECT user_id FROM "
    			.$cfg["tab"]["groupmembers"]."
                WHERE group_id = '".Contenido_Security::escapeDB($uid,$db2)."'";
        
        $db2->query($sql);
   
        while ($db2->next_record())
        {
        	if ($db2->f("user_id") == $auth->auth["uid"])
        	{
        		return true;
        	}
        }
    } else {
    	if ($uid == $auth->auth["uid"])
    	{
    		return true;
    	}
    }
   
    return false;
}

function getActionSelect ($idartlang, $idusersequence)
{
	global $cfg;
	
	$workflowActions = new WorkflowActions;
	
	$allActions = $workflowActions->getAvailableWorkflowActions();

	$wfSelect = new Template;
	$wfSelect->set('s', 'NAME', 'wfselect'.$idartlang);
	$wfSelect->set('s', 'CLASS', 'text_medium');
	
	$userSequence = new WorkflowUserSequence;
	$userSequence->loadByPrimaryKey($idusersequence);
	
    $workflowItem = $userSequence->getWorkflowItem();
    
    if ($workflowItem === false)
    {
    	return;	
    }
    
    $wfRights = $workflowItem->getStepRights();
    
    $artAllocation = new WorkflowArtAllocations;
    $artAllocation->select("idartlang = '$idartlang'");
    
    if ($obj = $artAllocation->next())
    {
    		$laststep = $obj->get("lastusersequence");
    }
    
    $bExistOption = false;
	if ($laststep != $idusersequence)
	{
		$wfSelect->set('d', 'VALUE', 'next');
		$wfSelect->set('d', 'CAPTION', i18n("Confirm", "workflow"));
		$wfSelect->set('d', 'SELECTED', 'SELECTED');
		$wfSelect->next();
        $bExistOption = true;
	}
    
	if ($wfRights["last"] == true)
	{
		$wfSelect->set('d', 'VALUE', 'last');
		$wfSelect->set('d', 'CAPTION', i18n("Back to last editor", "workflow"));
		$wfSelect->set('d', 'SELECTED', '');
		$wfSelect->next();
        $bExistOption = true;
	}
    
    if ($wfRights["reject"] == true)
	{
		$wfSelect->set('d', 'VALUE', 'reject');
		$wfSelect->set('d', 'CAPTION', i18n("Reject article", "workflow"));
		$wfSelect->set('d', 'SELECTED', '');
		$wfSelect->next();
        $bExistOption = true;
	}
	
	if ($wfRights["revise"] == true)
	{
		$wfSelect->set('d', 'VALUE', 'revise');
		$wfSelect->set('d', 'CAPTION', i18n("Revise article", "workflow"));
		$wfSelect->set('d', 'SELECTED', '');
		$wfSelect->next();
        $bExistOption = true;
	}
	
    if ($bExistOption)
        return ($wfSelect->generate($cfg['path']['templates'].$cfg['templates']['generic_select'], true));
    else {
        return false;
    }
}

#function for inserting todos in wokflow_art_allocation used, when a workflow is associated with a category in content->category
function setUserSequence ($idartlang, $defaultidworkflow) {
    $wfaa = new WorkflowArtAllocations;
    $wfaa->select("idartlang = '$idartlang'");
    $idusersequence = 0;
    
    if ($associatedUserSequence = $wfaa->next())
    {
        $idartallocation = $associatedUserSequence->get("idartallocation");
        $wfaa->delete($idartallocation);
    }
    
    if ($defaultidworkflow != -1) {
        $newObj = $wfaa->create($idartlang);
        
        if (!$newObj)
        {
            /* Try to load */
            $newObj = new WorkflowArtAllocation;
            
            echo $wfaa->lasterror;
            return false;
        }
 
        /* Get the first idusersequence for the new item */
        $workflowItems = new WorkflowItems;
        $workflowItems->select("idworkflow = '$defaultidworkflow' AND position = '1'");
        
        if ($obj = $workflowItems->next())
        {
            $firstitem = $obj->get("idworkflowitem");
        }
        
        $workflowUserSequences = new WorkflowUserSequences;
        $workflowUserSequences->select("idworkflowitem = '$firstitem' AND position = '1'");
        
        if ($obj = $workflowUserSequences->next())
        {
            $firstIDUserSequence = $obj->get("idusersequence");
        }
 
        $newObj->set("idusersequence", $firstIDUserSequence);
        $newObj->store();

        $idusersequence = $newObj->get("idusersequence");
        $associatedUserSequence = $newObj;
   }
}

function getCurrentUserSequence ($idartlang, $defaultidworkflow)
{
        $wfaa = new WorkflowArtAllocations;
        $wfaa->select("idartlang = '$idartlang'");
        $idusersequence = 0;
        
        if ($associatedUserSequence = $wfaa->next())
        {
			$idusersequence = $associatedUserSequence->get("idusersequence");
        }
        
        if ($idusersequence == 0)
		{
            if ($associatedUserSequence != false)
            {
                $newObj = $associatedUserSequence;
            } else {
                $newObj = $wfaa->create($idartlang);
                
                if (!$newObj)
                {
                    /* Try to load */
                    $newObj = new WorkflowArtAllocation;
                    
                    echo $wfaa->lasterror;
                    return false;
                }
            }
                
            /* Get the first idusersequence for the new item */
			$workflowItems = new WorkflowItems;
			$workflowItems->select("idworkflow = '$defaultidworkflow' AND position = '1'");
			
			if ($obj = $workflowItems->next())
			{
				$firstitem = $obj->get("idworkflowitem");
			}
			
			$workflowUserSequences = new WorkflowUserSequences;
			$workflowUserSequences->select("idworkflowitem = '$firstitem' AND position = '1'");
			
			if ($obj = $workflowUserSequences->next())
			{
				$firstIDUserSequence = $obj->get("idusersequence");
			}

            $newObj->set("idusersequence", $firstIDUserSequence);
            $newObj->store();
            
            $idusersequence = $newObj->get("idusersequence");
            $associatedUserSequence = $newObj;
        }
        
        return ($idusersequence);
}

function getLastWorkflowStatus ($idartlang)
{
        $wfaa = new WorkflowArtAllocations;
        
        $wfaa->select("idartlang = '$idartlang'");
        
        if ($associatedUserSequence = $wfaa->next())
        {
			$laststatus = $associatedUserSequence->get("laststatus");
        } else {
        	return false;
        }
        
        switch ($laststatus)
        {
        	case "reject": 
        			return (i18n("Rejected", "workflow"));
        			break;
        	case "revise":
        			return (i18n("Revised", "workflow"));
        			break;
        	case "last":
        			return (i18n("Last", "workflow"));
        			break;
        	case "confirm":
        			return (i18n("Confirmed", "workflow"));
        			break;
        	default:
        			return (i18n("None", "workflow"));
        			break;
        }
        return ("");
}

function doWorkflowAction ($idartlang, $action)
{
	global $cfg, $idcat;
	
	switch ($action)
	{
		case "last":
			$artAllocations = new WorkflowArtAllocations;
			$artAllocations->select("idartlang = '$idartlang'");
			
			if ($obj = $artAllocations->next())
			{
				$usersequence = new WorkflowUserSequence;
				$usersequence->loadByPrimaryKey($obj->get("idusersequence"));
				
				$workflowitem = $usersequence->getWorkflowItem();
				
				$idworkflow = $workflowitem->get("idworkflow");
				$newpos = $workflowitem->get("position") - 1;
				
				if ($newpos < 1)
				{
					$newpos = 1;
				}
				
				$workflowitems = new WorkflowItems;
				$workflowitems->select("idworkflow = '$idworkflow' AND position = '".Contenido_Security::escapeDB($newpos, NULL)."'");
				
				if ($nextObj = $workflowitems->next())
				{
					$userSequences = new WorkflowUserSequences;
					$idworkflowitem = $nextObj->get("idworkflowitem");
					$userSequences->select("idworkflowitem = '$idworkflowitem'");
					
					if ($nextSeqObj = $userSequences->next())
					{
						$obj->set("lastusersequence", $obj->get("idusersequence"));	
						$obj->set("idusersequence",$nextSeqObj->get("idusersequence"));
						$obj->set("laststatus", "last");
						$obj->store();
					}
				}
			}
			break;		
		case "next":
			$artAllocations = new WorkflowArtAllocations;
			$artAllocations->select("idartlang = '$idartlang'");
			
			if ($obj = $artAllocations->next())
			{
				$usersequence = new WorkflowUserSequence;
				$usersequence->loadByPrimaryKey($obj->get("idusersequence"));
				
				$workflowitem = $usersequence->getWorkflowItem();
				
				$idworkflow = $workflowitem->get("idworkflow");
				$newpos = $workflowitem->get("position") + 1;
				
				$workflowitems = new WorkflowItems;
				$workflowitems->select("idworkflow = '$idworkflow' AND position = '".Contenido_Security::escapeDB($newpos, NULL)."'");
				
				if ($nextObj = $workflowitems->next())
				{
					$userSequences = new WorkflowUserSequences;
					$idworkflowitem = $nextObj->get("idworkflowitem");
					$userSequences->select("idworkflowitem = '$idworkflowitem'");
					
					if ($nextSeqObj = $userSequences->next())
					{
						$obj->set("lastusersequence", '10');						
						$obj->set("idusersequence",$nextSeqObj->get("idusersequence"));
						$obj->set("laststatus", "confirm");
						$obj->store();
					}
				} else {
					$workflowitems->select("idworkflow = '$idworkflow' AND position = '".Contenido_Security::escapeDB($workflowitem->get("position"), NULL)."'");
					if ($nextObj = $workflowitems->next())
    				{
    					$userSequences = new WorkflowUserSequences;
    					$idworkflowitem = $nextObj->get("idworkflowitem");
    					$userSequences->select("idworkflowitem = '$idworkflowitem'");
    					
    					if ($nextSeqObj = $userSequences->next())
    					{
    						$obj->set("lastusersequence", $obj->get("idusersequence"));						
    						$obj->set("idusersequence",$nextSeqObj->get("idusersequence"));
    						$obj->set("laststatus", "confirm");
    						$obj->store();
    					}
    				}
				}
					 
			}
			break;
		case "reject":
			$artAllocations = new WorkflowArtAllocations;
			$artAllocations->select("idartlang = '$idartlang'");
			
			if ($obj = $artAllocations->next())
			{
				$usersequence = new WorkflowUserSequence;
				$usersequence->loadByPrimaryKey($obj->get("idusersequence"));
				
				$workflowitem = $usersequence->getWorkflowItem();
				
				$idworkflow = $workflowitem->get("idworkflow");
				$newpos = 1;
				
				$workflowitems = new WorkflowItems;
				$workflowitems->select("idworkflow = '$idworkflow' AND position = '".Contenido_Security::escapeDB($newpos, NULL)."'");
				
				if ($nextObj = $workflowitems->next())
				{
					$userSequences = new WorkflowUserSequences;
					$idworkflowitem = $nextObj->get("idworkflowitem");
					$userSequences->select("idworkflowitem = '$idworkflowitem'");
					
					if ($nextSeqObj = $userSequences->next())
					{
						$obj->set("lastusersequence", $obj->get("idusersequence"));
						$obj->set("idusersequence",$nextSeqObj->get("idusersequence"));
						$obj->set("laststatus", "reject");
						$obj->store();
					}
				}
			}
			break;
						
		case "revise":
			$db = new DB_Contenido;
			$sql = "SELECT idart, idlang FROM ".$cfg["tab"]["art_lang"] ." WHERE idartlang = '".Contenido_Security::escapeDB($idartlang, $db)."'";
			$db->query($sql);
			$db->next_record();
			$idart = $db->f("idart");
			$idlang = $db->f("idlang");
			
			$newidart = conCopyArticle($idart,$idcat, "foo");
			
			break;
		default:
	}
}

function getWorkflowForUserSequence ($usersequence)
{
		$usersequences = new WorkflowUserSequences;
		$workflowitems = new WorkflowItems;
		$usersequences->select("idusersequence = '$usersequence'");
		
		if ($obj = $usersequences->next())
		{
			$idworkflowitem = $obj->get("idworkflowitem");	
		} else {
			return false;
		}
		
		$workflowitems->select("idworkflowitem = '$idworkflowitem'");
		if ($obj = $workflowitems->next())
		{
			return $obj->get("idworkflow");
		} else {
			return false;
		}
}

function workflowSelect ($listid, $default, $idcat)
{
	global $idclient, $cfg, $frame, $area, $workflowworkflows, $client, $lang, $wfcache, $workflowSelectBox;
    
    $oSelectBox = new cHTMLSelectElement('workflow');
    $oSelectBox = $workflowSelectBox;
    
    $default = (int) $default;
	$workflowSelectBox->updateAttributes(array("id" => "wfselect" . $idcat));
	$workflowSelectBox->updateAttributes(array("name" => "wfselect" . $idcat));
    $workflowSelectBox->setDefault($default);
	
    $sButton ='<a href="javascript:setWorkflow('.$idcat.', \''."wfselect".$idcat.'\')"><img src="'.$cfg["path"]["images"].'submit.gif" class="spaced"></a>';
    
    return $workflowSelectBox->render().$sButton;
}

function workflowInherit ($idcat)
{
	global $idclient, $cfg, $frame, $area, $workflowworkflows, $sess;	
	$sUrl = $sess->url("main.php?area=$area&frame=$frame&modidcat=$idcat&action=workflow_inherit_down");
    $sButton ='<a href="'.$sUrl.'"><img src="'.$cfg["path"]["images"].'pfeil_runter.gif" class="spaced"></a>';
    return $sButton;
}

?>