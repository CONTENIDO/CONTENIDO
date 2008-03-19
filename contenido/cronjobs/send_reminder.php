<?php

/*****************************************
* File      :   $RCSfile: send_reminder.php,v $
* Project   :   Contenido
* Descr     :   Cron Job to send reminder items
*
* Author    :   Timo A. Hummel
*               
* Created   :   12.02.2004
* Modified  :   $Date: 2007/10/12 13:53:00 $
*
* © four for business AG, www.4fb.de
*
* $Id: send_reminder.php,v 1.10 2006/04/28 09:20:55 timo.hummel Exp $
******************************************/

if (isset($cfg['path']['contenido'])) {
	include_once ($cfg['path']['contenido'].$cfg["path"]["includes"] . 'startup.php');
} else {
	include_once ('../includes/startup.php');
}

cInclude ("includes", "functions.general.php");
cInclude ("includes", "functions.i18n.php");
cInclude ("classes", 'class.genericdb.php');
cInclude ("classes", 'class.properties.php');
cInclude ("classes", 'class.todo.php');
cInclude ("classes", 'class.user.php');

global $cfg, $client;

$oldclient = $client;

if(!isRunningFromWeb() || function_exists("runJob") || $area == "cronjobs")
{
	$db = new DB_Contenido;
		
	$sql = "SELECT idclient FROM ".$cfg["tab"]["clients"];
	$db->query($sql);
	
	$clients = array();
	
	while ($db->next_record())
	{
		$clients[] = $db->f("idclient");
	}
	
	foreach ($clients as $client)
	{
		$mydate = time();
	
    	$props = new PropertyCollection;
    	$props->select("itemtype = 'idcommunication' AND type = 'todo' AND name = 'reminderdate' AND value < $mydate AND value != 0 AND idclient=$client");
		$pastreminders = array();
		
    	while ($prop = $props->next())
    	{
    		$pastreminders[] = $prop->get("itemid");
    	}
    	
    	$todoitem = new TODOItem;
		
 	
    	foreach ($pastreminders as $reminder)
    	{
    		
    		$todoitem->loadByPrimaryKey($reminder);
    		
    		if ($todoitem->get("idclient") == $client)
    		{
        		/* Check if email noti is active */
        		if ($todoitem->getProperty("todo", "emailnoti") == 1 && $todoitem->getProperty("todo", "emailnoti-sent") == 0)
        		{
        			$user = new User;
        			$user->loadUserByUserID($todoitem->get("recipient"));
        		
        			$recipient = $user->getField("email");
        			$realname = $user->getField("realname");
        			$subject = $todoitem->get("subject")."\n";
        			
        			$client = $todoitem->get("idclient");
        			$clientname = getClientName($client);
        			
        			$todoitem->setProperty("todo", "emailnoti-sent", "1");
        			$todoitem->setProperty("todo", "emailnoti", "0");
       			
        			$message = i18n("Hello %s,\n\nyou've got a new reminder for the client '%s' at\n%s:\n\n%s");
        			
        			$path = $cfg["path"]["contenido_fullhtml"];
        					
        			$message = sprintf($message,$realname, $clientname, $path, $todoitem->get("message"));
        			mail($recipient, $subject, $message);
        			
        		}
        		
        		$todoitem->setProperty("todo", "reminderdate", "0");        		
    		}	
    	}
		
	}
	
}

$client = $oldclient;
?>
