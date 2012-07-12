<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO Note Display
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.1
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created unknown
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


if ($action == 'note_delete') {
    $oNoteCollection = new NoteCollection();
    $oNoteCollection->delete($deleteitem);
}

$page = new cGuiPage("note.display");

$oNoteList = new NoteList($itemtype, $itemid);
$oNoteList->setDeleteable(true);

$page->setContent($oNoteList);
$page->render();

?>