<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido Note Display
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.0
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-27, Dominik Ziegler, add security fix
 *
 *   $Id: include.note.display.php 365 2008-06-27 14:09:47Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

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