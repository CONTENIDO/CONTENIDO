<?php

/**
 * This file contains the backend page for displaying a note.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

if ($action == 'note_delete') {
    $oNoteCollection = new NoteCollection();
    $oNoteCollection->delete($deleteitem);
}

$page = new cGuiPage("note.display");

$oNoteList = new NoteList($itemtype, $itemid);
$oNoteList->setDeleteable(true);

$page->setContent($oNoteList);
$page->render();
