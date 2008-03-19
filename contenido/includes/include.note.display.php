<?php
cInclude("classes", "widgets/class.widgets.page.php");
cInclude("classes", "class.ui.php");
cInclude("classes", "class.todo.php");
cInclude("classes", "class.note.php");
cInclude("classes", "class.htmlelements.php");

if ($action == "note_delete")
{
	$oNoteCollection = new NoteCollection;	
	$oNoteCollection->delete($deleteitem);
}

$page = new cPage;

$oNoteList = new NoteList($itemtype, $itemid);
$oNoteList->setDeleteable(true);

$page->setExtra('background: '.$cfg["color"]["table_light"]);
$page->setMargin(0);
$page->setContent($oNoteList);
$page->render();
?>