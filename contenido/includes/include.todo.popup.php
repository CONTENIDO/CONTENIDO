<?php
cInclude("classes", "widgets/class.widgets.page.php");
cInclude("classes", "class.ui.php");
cInclude("classes", "class.todo.php");
cInclude("classes", "class.htmlelements.php");

$cpage = new cPage;

if ($action == "todo_save_item")
{
	$todo = new TODOCollection;
	
	$subject = stripslashes($subject);
	$message = stripslashes($message);
	
	if (is_array($userassignment)) {
		foreach ($userassignment as $key => $value) {
			$item = $todo->create($itemtype, $itemid, strtotime($reminderdate), $subject, $message, $notiemail, $notibackend, $auth->auth["uid"]);
			$item->set("recipient", $value);
			$item->setProperty("todo", "enddate", $enddate);
			$item->store();
		}
	}

	$cpage->setContent("<script>window.close();</script>");
} else {
    $ui = new UI_Table_Form("reminder");
    $ui->addHeader(i18n("Add TODO item"));
    
    $ui->setVar("area",$area);
    $ui->setVar("frame", $frame);
    $ui->setVar("action", "todo_save_item");
    $ui->setVar("itemtype", $itemtype);
    $ui->setVar("itemid", $itemid);
    
    $subject = new cHTMLTextbox("subject", htmldecode(stripslashes(urldecode($subject))),60);
    $ui->add(i18n("Subject"), $subject->render());
    
    $message = new cHTMLTextarea("message", htmldecode(stripslashes(urldecode($message))));
    $ui->add(i18n("Description"), $message->render());
    
    $reminderdate = new cHTMLTextbox("reminderdate", '', '', '', "reminderdate");
    
    $datepopup = ' <img src="images/calendar.gif" width="16" height="16" alt="Endzeitpunkt wählen" id="reminder_date" style="vertical-align:middle;">';
    $ui->add(i18n("Reminder date"),$reminderdate->render().$datepopup);
    
	$reminderdue = new cHTMLTextbox("enddate", '', '', '', "enddate");
    $duepopup = ' <img src="images/calendar.gif" width="16" height="16" alt="Endzeitpunkt wählen" id="end_date" style="vertical-align:middle;">';
    $ui->add(i18n("End date"),$reminderdue->render().$duepopup);    
    //$notibackend = new cHTMLCheckbox("notibackend", i18n("Backend notification"));
    $notiemail = new cHTMLCheckbox("notiemail", i18n("eMail notification"));
    
    $ui->add(i18n("Reminder options"), $notiemail->toHTML());
    $calscript = '<script language="JavaScript">'.'
    
        Calendar.setup(
            {
                inputField  : "enddate",
                ifFormat    : "%Y-%m-%d %H:%M",
                button      : "end_date",
                weekNumbers	: true,
                firstDay	:	1,
                showsTime	: true
            }
        );
        
        Calendar.setup(
            {
                inputField  : "reminderdate",
                ifFormat    : "%Y-%m-%d %H:%M",
                button      : "reminder_date",
                weekNumbers	: true,
                firstDay	:	1,
                showsTime	: true
            }
        );
    </script>';
    
    $userselect = new cHTMLSelectElement("userassignment[]");
    
    
	$UsersClass = new Users; 
    foreach ($UsersClass->getAccessibleUsers(split(',',$auth->auth["perm"]), true) as $key => $value) 
    { 
       $acusers[$key] = $value["username"]." (".$value["realname"].")"; 
    } 
    
    asort($acusers);
    
    $userselect->autoFill($acusers);
    $userselect->setDefault($auth->auth["uid"]);
    $userselect->setMultiselect();
    $userselect->setSize(5);
    
    $ui->add(i18n("Assigned to"), $userselect->render());    
	$cpage->setcontent($ui->render().$calscript);



	$cpage->addScript("cal", '<style type="text/css">@import url(scripts/jscalendar/calendar-contenido.css);</style>
	                          <script type="text/javascript" src="scripts/jscalendar/calendar.js"></script>
	                          <script type="text/javascript" src="scripts/jscalendar/lang/calendar-'.substr(strtolower($belang), 0, 2).'.js"></script>
	                          <script type="text/javascript" src="scripts/jscalendar/calendar-setup.js"></script>');
}
$cpage->render();

?>
