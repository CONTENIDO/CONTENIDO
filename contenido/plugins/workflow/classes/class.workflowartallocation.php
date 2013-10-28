<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 *  Workflow allocation class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.4
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2003-07-18
 *   modified : 2008-06-25 - use php mailer class instead of mail()
 *   
 *   $Id: class.workflowartallocation.php 568 2008-07-04 13:08:28Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


/**
 * Class WorkflowArtAllocations
 * Class for workflow art allocation management
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.2
 * @copyright four for business 2003
 */
class WorkflowArtAllocations extends ItemCollection {
	
	/**
     * Constructor Function
     * @param string $table The table to use as information source
     */
	function WorkflowArtAllocations()
	{
		global $cfg;
		
		parent::ItemCollection($cfg["tab"]["workflow_art_allocation"], "idartallocation");
	}
	
	function loadItem ($itemID)
	{
		$item = new WorkflowArtAllocation();
		$item->loadByPrimaryKey($itemID);
		return ($item);
	}
	
	function create ($idartlang)
	{
		global $cfg;
		
		$sql = "SELECT idartlang FROM " .$cfg["tab"]["art_lang"].
		       " WHERE idartlang = '".Contenido_Security::escapeDB($idartlang, $this->db)."'";

		$this->db->query($sql);
		if (!$this->db->next_record())
		{
			$this->lasterror = i18n("Article doesn't exist", "workflow");
			return false;
		}
		
		$this->select("idartlang = '$idartlang'");
		
		if ($this->next() !== false)
		{
			$this->lasterror = i18n("Article is already assigned to a usersequence step.", "workflow");
			return false;
		}
		
		$newitem = parent::create();
		$newitem->setField("idartlang",$idartlang);
		$newitem->store();
		
		return ($newitem);
	}
}

/**
 * Class WorkflowArtAllocation
 * Class for a single workflow allocation item
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright four for business 2003
 */
class WorkflowArtAllocation extends Item {
	
	/**
     * Constructor Function
     * @param string $table The table to use as information source
     */
	function WorkflowArtAllocation()
	{
		global $cfg;
		
		parent::Item($cfg["tab"]["workflow_art_allocation"], "idartallocation");
	}


	function getWorkflowItem ()
	{
		$userSequence = new WorkflowUserSequence;
		$userSequence->loadByPrimaryKey($this->values["idusersequence"]);
		
		return ($userSequence->getWorkflowItem());
	}
	
	/**
     * Returns the current item position
     * @param string $field Void field since we override the usual setField function
     * @param string $value Void field since we override the usual setField function
     */	
	function currentItemPosition()
	{
		$idworkflowitem = $this->get("idworkflowitem");
		
		$workflowItems = new WorkflowItems;
		$workflowItems->select("idworkflowitem = '$idworkflowitem'");
		
		if ($item = $workflowItems->next())
		{
			return ($item->get("position"));
		}
	}

	/**
     * Returns the current user position
     * @param string $field Void field since we override the usual setField function
     * @param string $value Void field since we override the usual setField function
     */	
	function currentUserPosition()
	{
		return ($this->get("position"));
	}

	/**
     * Overriden store function to send mails 
     * @param none
     */		
	function store()
	{
		global $cfg;
		
		$sMailhost = getSystemProperty('system', 'mail_host');
        if ($sMailhost == '') {
            $sMailhost = 'localhost';
        } 
		
		//modified : 2008-06-25 - use php mailer class instead of mail()
		$oMail = new PHPMailer;
        $oMail->Host = $sMailhost;
        $oMail->IsHTML(0);
		$oMail->WordWrap = 1000;
		$oMail->IsMail();
		
		if (array_key_exists("idusersequence",$this->modifiedValues))
		{
			$usersequence = new WorkflowUserSequence;
			$usersequence->loadByPrimaryKey($this->values["idusersequence"]);
			
			$email = $usersequence->get("emailnoti");
			$escal = $usersequence->get("escalationnoti");
			
			if ($email == 1 || $escal == 1)
			{
				/* Grab the required informations */
				$curEditor = getGroupOrUserName($usersequence->get("iduser"));
				$idartlang = $this->get("idartlang");
				$timeunit = $usersequence->get("timeunit");
				$timelimit = $usersequence->get("timelimit");
				
				$db = new DB_Contenido;
				$sql = "SELECT author, title, idart FROM ".$cfg["tab"]["art_lang"]." WHERE idartlang = '".Contenido_Security::escapeDB($idartlang, $db)."'";
				
				$db->query($sql);
				
				if ($db->next_record())
				{
					$idart = $db->f("idart");
					$title = $db->f("title");
					$author = $db->f("author");
				}
				
				/* Extract category */
				$sql = "SELECT idcat FROM ".$cfg["tab"]["cat_art"]." WHERE idart = '".Contenido_Security::escapeDB($idart, $db)."'";
				$db->query($sql);
				
				if ($db->next_record())
				{
					$idcat = $db->f("idcat");
				}
				
				$sql = "SELECT name FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat = '".Contenido_Security::escapeDB($idcat, $db)."'";
				$db->query($sql);
				
				if ($db->next_record())
				{
					$catname = $db->f("name");	
				}
				
				$starttime = $this->get("starttime");
				
				
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
            	
            	if ($email == 1)
            	{
                	$email = "Hello %s,\n\n".
                	         "you are assigned as the next editor for the Article %s.\n\n".
                	         "More informations:\n".
                	         "Article: %s\n".
                	         "Category: %s\n".
                	         "Editor: %s\n".
                	         "Author: %s\n".
                	         "Editable from: %s\n".
                	         "Editable to: %s\n";
                	         
                	$filledMail = sprintf(	$email,
                                			$curEditor,
                                			$title,
                                			$title,
                                			$catname,
                                			$curEditor,
                                			$author,
                                			date("Y-m-d H:i:s", $starttime),
                                			date("Y-m-d H:i:s", $maxtime));
					$user = new User;

                    if (isGroup($usersequence->get("iduser")))
                    {
                    	    $sql = "select idgroupuser, user_id FROM ". $cfg["tab"]["groupmembers"] ." WHERE
            						group_id = '".Contenido_Security::escapeDB($usersequence->get("iduser"), $db)."'";
            				$db->query($sql);
            				
            				while ($db->next_record())
            				{
            					$user->loadUserByUserID($db->f("user_id"));
								//modified : 2008-06-25 - use php mailer class instead of mail()
								$oMail->AddAddress($user->getField("email"), "");
						        $oMail->Subject = stripslashes (i18n("Workflow notification"));
						        $oMail->Body = $filledMail;
								$oMail->Send();
            				}
            				
                    } else {
                    	$user->loadUserByUserID($usersequence->get("iduser"));
						//modified : 2008-06-25 - use php mailer class instead of mail()
                    	$oMail->AddAddress($user->getField("email"), "");
				        $oMail->Subject = stripslashes (i18n("Workflow notification"));
				        $oMail->Body = $filledMail;
						$oMail->Send();
                    }
                    
            	} else {
                 	$email = "Hello %s,\n\n".
                	         "you are assigned as the escalator for the Article %s.\n\n".
                	         "More informations:\n".
                	         "Article: %s\n".
                	         "Category: %s\n".
                	         "Editor: %s\n".
                	         "Author: %s\n".
                	         "Editable from: %s\n".
                	         "Editable to: %s\n";
                	         
                	$filledMail = sprintf(	$email,	
                							$curEditor,
                							$title,                							
                                			$title,
                                			$catname,
                                			$curEditor,
                                			$author,
                                			date("Y-m-d H:i:s", $starttime),
                                			date("Y-m-d H:i:s", $maxtime));
                                			
					$user = new User;
					
                    if (isGroup($usersequence->get("iduser")))
                    {
                    		
                    	    $sql = "select idgroupuser, user_id FROM ". $cfg["tab"]["groupmembers"] ." WHERE
            						group_id = '".Contenido_Security::escapeDB($usersequence->get("iduser"), $db)."'";
            				$db->query($sql);
            				
            				while ($db->next_record())
            				{
            					$user->loadUserByUserID($db->f("user_id"));
            					echo "mail to ".$user->getField("email")."<br>";
								//modified : 2008-06-25 - use php mailer class instead of mail()
								$oMail->AddAddress($user->getField("email"), "");
						        $oMail->Subject = stripslashes (i18n("Workflow escalation"));
						        $oMail->Body = $filledMail;
								$oMail->Send();								
            				}
            				
                    } else {
                    	$user->loadUserByUserID($usersequence->get("iduser"));
                    	echo "mail to ".$user->getField("email")."<br>";
						//modified : 2008-06-25 - use php mailer class instead of mail()
						$oMail->AddAddress($user->getField("email"), "");
						$oMail->Subject = stripslashes (i18n("Workflow escalation"));
						$oMail->Body = $filledMail;
						$oMail->Send();	
                    }
            	}           		
            		

				            					
			} 	
			
			
		}
		return parent::store();		
	}
}
?>