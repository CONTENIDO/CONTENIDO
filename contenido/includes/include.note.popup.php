<?php

/**
 * This file contains the backend page for the note popup.
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

$cpage = new cGuiPage("note.popup");

if ($action == 'note_save_item') {
    $notes = new NoteCollection();

    $note = stripslashes(nl2br($note));

    if ($category != '') {
        $categoryname = $category;
    }

    if ($categoryentry != '') {
        $categoryname = $categoryentry;
    }

    $item = $notes->createItem($itemtype, $itemid, $lang, $note, $categoryname);
    $item->store();

    $cpage->addScript('<script type="text/javascript">window.close();</script>');
} else {
    $list = new NoteView($itemtype, $itemid);
    $list->setWidth('100%');
    $list->appendStyleDefinition('margin-bottom', '10px');
    $ui = new cGuiTableForm('note');
    $ui->setHeader(i18n('Add note'));

    $ui->setVar('area', $area);
    $ui->setVar('frame', $frame);
    $ui->setVar('action', 'note_save_item');
    $ui->setVar('itemtype', $itemtype);
    $ui->setVar('itemid', $itemid);

    // Fetch all note categories
    $propColl = new cApiPropertyCollection();
    $notesData = $propColl->getValuesOnlyByTypeName('note', 'category');
    $notesData = array_unique($notesData);

    $categories = ['' => i18n('No category')];

    $oNoteItem = new NoteItem();

    foreach ($notesData as $noteValue) {
        $sValue = $oNoteItem->outFilter($noteValue);
        $categories[$sValue] = $sValue;
    }

    $cselect = new cHTMLSelectElement('category');
    $cselect->autoFill($categories);

    $centry = new cHTMLTextbox('categoryentry', '', 30);

    $message = new cHTMLTextarea('note');
    $message->setStyle('width: 100%');
    $ui->add(i18n('Note'), $message->render());
    $ui->add(i18n('Category'), [$cselect, $centry]);
    $ui->setWidth('100%');

    $cpage->setContent([$list, $ui]);
}

$cpage->render();
