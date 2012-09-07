<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * MyContenido tasks overview
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.1
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id: include.mycontenido.tasks.php 504 2008-07-02 11:59:49Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "widgets/class.widgets.page.php");
cInclude("classes", "class.ui.php");
cInclude("classes", "class.todo.php");
cInclude("classes", "class.htmlelements.php");

if (!isset($sortmode))
{
	$sortmode = $currentuser->getUserProperty("system","tasks_sortmode");
	$sortby   = $currentuser->getUserProperty("system","tasks_sortby");	
}

$dateformat = getEffectiveSetting("backend", "timeformat", "Y-m-d H:i:s");

if (isset($_REQUEST["listsubmit"]))
{
	if (isset($c_restrict))
	{
		$c_restrict = true;
		$currentuser->setUserProperty("mycontenido", "hidedonetasks", "true");
	} else {
		$c_restrict = false;
		$currentuser->setUserProperty("mycontenido", "hidedonetasks", "false");
	}
} else {
	if ($currentuser->getUserProperty("mycontenido", "hidedonetasks") == "true")
	{
		$c_restrict = true;	
	} else {
		$c_restrict = false;	
	}
}

class TODOBackendList extends cScrollList
{
	var $statustypes;
	function TODOBackendList ()
	{
		global $todoitems;
		
		parent::cScrollList();
		
		$this->statustypes = $todoitems->getStatusTypes();
		$this->prioritytypes = $todoitems->getPriorityTypes();
	}
	
	function onRenderColumn ($column)
	{
		if ($column == 6 || $column == 5)
		{
			$this->objItem->updateAttributes(array("align" => "center"));	
		} else {
			$this->objItem->updateAttributes(array("align" => "left"));	
		}
	}
	
	function convert ($key, $value, $hidden)
	{
		global $link, $dateformat, $cfg;
		
		if ($key == 2)
		{
			$link->setCustom("idcommunication", $hidden[1]);
			$link->setContent($value);
			return $link->render();
		}
		
		if ($key == 3)
		{
			return date($dateformat,strtotime($value));	
		}
		
		if ($key == 5)
		{

			switch ($value)
			{
				case "new":
						$img = "status_new.gif";
						break;
				case "progress":
						$img = "status_inprogress.gif";
						break;
				case "done":
						$img = "status_done.gif";
						break;
				case "deferred":
						$img = "status_deferred.gif";
						break;
				case "waiting":
						$img = "status_waiting.gif";
						break;
						
				default: break;
			}

			if (!array_key_exists($value, $this->statustypes))
			{
				return i18n("No status type set");
			}						
			$image = new cHTMLImage($cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."reminder/".$img);
			$image->setAlt($this->statustypes[$value]);
			
            //Do not display statuicon, only show statustext
			//return $image->render();
			return $this->statustypes[$value];
		}
		
		if ($key == 7)
		{
			$amount = $value / 20;
			
			if ($amount < 0)
			{
				$amount  = 0;
			}
			
			if ($amount  > 5)
			{
				$amount  = 5;
			}
			
			$amount = round($amount);
			
			$image = new cHTMLImage($cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."reminder/progress.gif");
			$image->setAlt(sprintf(i18n("%d %% complete"), $value));
			$ret = "";
			
			for ($i=0;$i<$amount;$i++)
			{
				$ret .= $image->render();	
			}
			
			return $ret;
		}
		
		if ($key == 6)
		{
			switch ($value)
			{
				case 0: $img = "prio_low.gif";
						$p = "low";
						break;
				case 1: $img = "prio_medium.gif";
						$p = "medium";
						break;
				case 2: $img = "prio_high.gif";
						$p = "high";
						break;
				case 3: $img = "prio_veryhigh.gif";
						$p = "immediately";
						break;
				default: break;
			}
			
			$image = new cHTMLImage($cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."reminder/".$img);
			$image->setAlt($this->prioritytypes[$p]);
			return $image->render();
		}
		if ($key == 8)
		{
			if ($value !== "")
			{
				
				if (round($value,2) == 0)
				{
					return i18n("Today");
				} else {
					
    				if ($value < 0)
    				{
    					return number_format(0-$value, 2, ',', '') . " ".i18n("Day(s)");
    				} else {
    					return '<font color="red">'. number_format(0-$value, 2, ',', '') . " ".i18n("Day(s)").'</font>';
    				}
				}
			}	
		}
		return $value;
	}	
}

if ($action == "todo_save_item")
{
	
	$subject = stripslashes($subject);
	$message = stripslashes($message);
		
	$todoitem = new TODOItem;
	$todoitem->loadByPrimaryKey($idcommunication);
	
	$todoitem->set("subject", $subject);
	$todoitem->set("message", $message);
	$todoitem->set("recipient", $userassignment);
	
	if (isset($reminderdate))
	{
		$todoitem->setProperty("todo", "reminderdate", strtotime($reminderdate));
	}
	
	if (isset($notibackend))
	{
		$todoitem->setProperty("todo", "backendnoti", $notibackend);
	}
	
	if (isset($notiemail))
	{	
		$todoitem->setProperty("todo", "emailnoti", $notiemail);	
	}
	
	$todoitem->setProperty("todo", "status", $status);
	
	if ($priority < 0)
	{
		$priority = 0;
	}
	
	if ($priority > 100)
	{
		$priority = 100;
	}
	
	$todoitem->setProperty("todo", "priority", $priority);
	$todoitem->setProperty("todo", "progress", $progress);
	
	$todoitem->setProperty("todo", "enddate", $enddate);
	
	$todoitem->store();
	
		
}
$cpage = new cPage;

$todoitems = new TODOCollection;

if ($action == "mycontenido_tasks_delete")
{
	$todoitems->delete($idcommunication);
}

$recipient = $auth->auth["uid"];

$todoitems->select("recipient = '$recipient' AND idclient='$client'");

$list = new TODOBackendList;

$list->setHeader("",i18n("Subject"),i18n("Created"),i18n("End Date"),i18n("Status"), i18n("Priority"), sprintf(i18n("%% complete")), i18n("Due in"), i18n("Actions"));


$lcount = 0;


$link = new cHTMLLink;
$link->setCLink("mycontenido_tasks_edit", 4, "");
$link->setCustom("sortmode", $sortmode);
$link->setCustom("sortby", $sortby);

while ($todo = $todoitems->next())
{
	if ((($todo->getProperty("todo", "status") != "done") && ($c_restrict == true))||($c_restrict == '')){
	{
    	$subject = $todo->get("subject");
    	$created = $todo->get("created");
    
    
    	$reminder = $todo->getProperty("todo", "enddate");
    	$status = $todo->getProperty("todo", "status");
    	$priority = $todo->getProperty("todo", "priority");
    	$complete = $todo->getProperty("todo", "progress");
    	
    	if (trim($subject) == "")
    	{
    		$subject = i18n("Unnamed item");
    	}
    
    	if (trim($reminder) == "")
    	{
    		$reminder = i18n("No end date set");
    	} else {
    		$reminder = date($dateformat,strtotime($reminder));
    	}
    	
    	if (trim($status) == "")
    	{
    		$status = i18n("No status set");
    	}
    
    	$link->setCustom("idcommunication", $todo->get("idcommunication"));
    	
    	
    	$link->setContent('<img src="images/but_todo.gif" border="0" style="padding-top: 2px; padding-bottom: 2px;">');
    	
    	$mimg = $link->render();
    	
    	$link->setContent($subject);
    	
    	$msubject = $link->render();
    	
    	$idcommunication = $todo->get("idcommunication");
    	
    	$delete = new cHTMLLink;
    	
    	$delete->setCLink("mycontenido_tasks", 4, "mycontenido_tasks_delete");
    	$delete->setCustom("idcommunication", $idcommunication);
    	$delete->setCustom("sortby", $sortby);
    	$delete->setCustom("sortmode", $sortmode);
    	
    	$img = new cHTMLImage("images/delete.gif");
    	$img->setAlt(i18n("Delete item"));
    	
    	$delete->setContent($img->render());
    	
    	$properties = $link;
    	
    	$img = new cHTMLImage("images/but_art_conf2.gif");
    	$img->setAlt(i18n("Edit item"));
		$img->setStyle("padding-right: 4px;");
	   	$properties->setContent($img);
    	    	
    	$actions = $properties->render() . $delete->render();
    	
    	if ($todo->getProperty("todo", "enddate") != "")
    	{
    		$duein = round((time() - strtotime($todo->getProperty("todo", "enddate"))) / 86400,2);
    	} else {
    		$duein = "";
    	}
    	
    	switch ($priority)
    	{
    		case "low": $p = 0;break;
    		case "medium": $p = 1;break;
    		case "high": $p = 2;break;
    		case "immediately": $p = 3;break;
    		default: break;		
    	}
    	
    	$list->setData($lcount, $mimg, $subject, $created, $reminder, $status, $p, $complete, $duein, $actions);
    	$list->setHiddenData($lcount, $idcommunication, $idcommunication);
    	
    	$lcount++;
	}
	}
}

$form = new UI_Table_Form("restrict");
$form->addHeader(i18n("Restrict display"));
$form->setVar("listsubmit", "true");

$form->unsetActionButton("submit");
$form->setActionButton("submit", "images/but_refresh.gif", i18n("Refresh"), "s");

$form->setVar("area", $area);
$form->setVar("frame", $frame);

$restrict = new cHTMLCheckbox("c_restrict", "true");
$restrict->setLabelText(i18n("Hide done tasks"));

if ($c_restrict == true)
{
	$restrict->setChecked(true);
}

$submit = new cHTMLButton("submit");
$submit->setMode("image");
$submit->setImageSource("images/submit.gif");

$form->add(i18n("Options"), $restrict->render());

if ($lcount == 0)
{
	$cpage->setContent($form->render()."<br>".i18n("No tasks found")."<br>".markSubMenuItem(2, true));
} else {
	if (!isset($sortby))
	{
		$sortby = 1;
	}
	
	if (!isset($sortmode))
	{
		$sortmode = "ASC";
	}
	
    $list->setSortable(1,true);
    $list->setSortable(2,true);
    $list->setSortable(3,true);
    $list->setSortable(4,true);
    $list->setSortable(5,true);
    $list->setSortable(6,true);
    $list->setSortable(7,true);
    $list->sort($sortby, $sortmode);
    
    $cpage->setContent($form->render()."<br>".$list->render(). markSubMenuItem(2, true));
}
$cpage->render();

$currentuser->setUserProperty("system","tasks_sortby", $sortby);
$currentuser->setUserProperty("system","tasks_sortmode", $sortmode);
?>