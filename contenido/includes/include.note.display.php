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

/**
 * @var int $deleteitem
 * @var string $itemtype
 * @var string|int $itemid
 */

$action = cRegistry::getAction();

if ($action == 'note_delete') {
    $noteCollection = new NoteCollection();
    $noteCollection->delete($deleteitem);
}

$page = new cGuiPage('note.display');

$noteList = new NoteList($itemtype, $itemid);
$noteList->setDeleteable(true);

$page->setContent($noteList);
$page->render();
