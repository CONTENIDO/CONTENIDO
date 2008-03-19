<?php
cInclude("classes", "widgets/class.widgets.page.php");
cInclude("classes", "class.ui.php");
cInclude("classes", "class.todo.php");
cInclude("classes", "class.note.php");
cInclude("classes", "class.htmlelements.php");

$cpage = new cPage;

if ($action == "note_save_item")
{
	$notes = new NoteCollection;
	
	$note = stripslashes(nl2br($note));
	
	if ($category != "")
	{
		$categoryname = $category;	
	}
	
	if ($categoryentry != "")
	{
		$categoryname = $categoryentry;	
	}
	
	$item = $notes->create($itemtype, $itemid, $lang, $note, $categoryname);
	$item->store();
	
	$cpage->setContent("<script>window.close();</script>");
} else {
	$list = new NoteView($itemtype, $itemid);
	$list->setWidth("100%");
	$list->setStyleDefinition("margin-bottom", "10px");
    $ui = new UI_Table_Form("note");
    $ui->addHeader(i18n("Add note"));
    
    $ui->setVar("area",$area);
    $ui->setVar("frame", $frame);
    $ui->setVar("action", "note_save_item");
    $ui->setVar("itemtype", $itemtype);
    $ui->setVar("itemid", $itemid);
    
    /* Fetch all note categors */
    $dbprop = new DB_Contenido;
    $dbprop->query("SELECT DISTINCT value FROM ".$cfg["tab"]["properties"] .' where type="note" AND name="category"');
    
    $categories = array("" => i18n("No category"));
    
    $oNoteItem = new NoteItem;
    
    while ($dbprop->next_record())
    {
    	$sValue = $oNoteItem->_outFilter($dbprop->f("value"));
    	$categories[$sValue] = $sValue;	
    }
    
    $cselect = new cHTMLSelectElement("category");
    $cselect->autoFill($categories);
    
    $centry = new cHTMLTextbox("categoryentry", "", 30);
    
    $message = new cHTMLTextarea("note");
    $message->setStyle("width: 100%");
    $ui->add(i18n("Note"), $message->render());
    $ui->add(i18n("Category"), array($cselect, $centry));
    $ui->setWidth("100%");
    
	$cpage->setcontent($list->render().$ui->render());
}
$cpage->render();

?>