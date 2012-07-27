<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO Notes system
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.0.7
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created  unknown
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

class NoteCollection extends cApiCommunicationCollection {

    public function __construct() {
        parent::__construct();
        $this->_setItemClass('NoteItem');
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    function NoteCollection() {
        cDeprecated('Use __construct() instead');
        $this->__construct();
    }

    /**
     * Selects one or more items from the database
     *
     * This function only extends the where statement. See the
     * original function for the parameters.
     *
     * @see ItemCollection
     */
    public function select($where = '', $group_by = '', $order_by = '', $limit = '') {
        if ($where == '') {
            $where = "comtype='note'";
        } else {
            $where .= " AND comtype='note'";
        }

        return parent::select($where, $group_by, $order_by, $limit);
    }

    /**
     * Magic method to invoke inaccessible methods.
     * Currently it works as a fallback for the not supported method create() which
     * creates a PHP Strict warning.
     * @param  string  $name
     * @param  array   $arguments
     * @return mixed
     */
    public function __call($name, $arguments) {
        if ('create' === $name) {
            // Catch old and not supported create() method
            cDeprecated('Use NoteCollection->createItem() instead NoteCollection->create()');
            return call_user_func_array(array($this, 'createItem'), $arguments);
        }
    }

    /**
     * Creates a new note item.
     *
     * @param $itemtype  string   Item type (usually the class name)
     * @param $itemid    mixed    Item ID (usually the primary key)
     * @param $idlang    int      Language-ID
     * @param $message   string   Message to store
     * @return object    The new item
     */
    public function createItem($itemtype, $itemid, $idlang, $message, $category = '') {
        $item = parent::create();

        $item->set('subject', 'Note Item');
        $item->set('message', $message);
        $item->set('comtype', 'note');
        $item->store();

        $item->setProperty('note', 'itemtype', $itemtype);
        $item->setProperty('note', 'itemid', $itemid);
        $item->setProperty('note', 'idlang', $idlang);

        if ($category != '') {
            $item->setProperty('note', 'category', $category);
        }

        return $item;
    }

}

class NoteItem extends cApiCommunication {
    
}

class NoteView extends cHTMLIFrame {

    public function NoteView($sItemType, $sItemId) {
        global $sess, $cfg;
        cHTMLIFrame::cHTMLIFrame();
        $this->setSrc($sess->url("main.php?itemtype=$sItemType&itemid=$sItemId&area=note&frame=2"));
        $this->setBorder(0);
    }

}

class NoteList extends cHTMLDiv {

    public function NoteList($sItemType, $sItemId) {
        cHTMLDiv::cHTMLDiv();

        $this->_sItemType = $sItemType;
        $this->_sItemId = $sItemId;

        $this->appendStyleDefinition('width', '100%');
    }

    public function setDeleteable($bDeleteable) {
        $this->_bDeleteable = $bDeleteable;
    }

    public function toHTML() {
        global $cfg, $lang;

        $sItemType = $this->_sItemType;
        $sItemId = $this->_sItemId;

        $oPropertyCollection = new cApiPropertyCollection();
        $oPropertyCollection->select("itemtype = 'idcommunication' AND type = 'note' AND name = 'idlang' AND value = " . (int) $lang);

        $items = array();

        while ($oProperty = $oPropertyCollection->next()) {
            $items[] = $oProperty->get('itemid');
        }

        $oNoteItems = new NoteCollection();

        if (count($items) == 0) {
            $items[] = 0;
        }

        $oNoteItems->select('idcommunication IN (' . implode(', ', $items) . ')', '', 'created DESC');

        $i = array();
        $dark = false;
        while ($oNoteItem = $oNoteItems->next()) {
            if ($oNoteItem->getProperty('note', 'itemtype') == $sItemType && $oNoteItem->getProperty('note', 'itemid') == $sItemId) {
                $j = new NoteListItem($sItemType, $sItemId, $oNoteItem->get('idcommunication'));
                $j->setAuthor($oNoteItem->get('author'));
                $j->setDate($oNoteItem->get('created'));
                $j->setMessage($oNoteItem->get('message'));
                $j->setBackground($dark);
                $j->setDeleteable($this->_bDeleteable);
                $dark = !$dark;
                $i[] = $j;
            }
        }

        $this->setContent($i);

        $result = parent::toHTML();

        return ('<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr><td>' . $result . '</td></tr></table>');
    }

}

class NoteListItem extends cHTMLDiv {

    public function NoteListItem($sItemType, $sItemId, $iDeleteItem) {
        cHTMLDiv::cHTMLDiv();
        $this->appendStyleDefinition('padding', '2px');
        $this->setBackground();
        $this->setDeleteable(true);

        $this->_iDeleteItem = $iDeleteItem;
        $this->_sItemType = $sItemType;
        $this->_sItemId = $sItemId;
    }

    public function setDeleteable($bDeleteable) {
        $this->_bDeleteable = $bDeleteable;
    }

    public function setBackground($dark = false) {
        global $cfg;
    }

    public function setAuthor($sAuthor) {
        if (strlen($sAuthor) == 32) {
            $result = getGroupOrUserName($sAuthor);

            if ($result !== false) {
                $sAuthor = $result;
            }
        }

        $this->_sAuthor = $sAuthor;
    }

    public function setDate($iDate) {
        $dateformat = getEffectiveSetting('dateformat', 'full', 'Y-m-d H:i:s');

        if (is_string($iDate)) {
            $iDate = strtotime($iDate);
        }
        $this->_sDate = date($dateformat, $iDate);
    }

    public function setMessage($sMessage) {
        $this->_sMessage = $sMessage;
    }

    public function render() {
        global $cfg, $sess;

        $itemtype = $this->_sItemType;
        $itemid = $this->_sItemId;
        $deleteitem = $this->_iDeleteItem;

        $table = '<table border="0" cellspacing="0" cellpadding="0" width="100%"><tr><td><b>';
        $table .= $this->_sAuthor;
        $table .= '</b></td><td align="right">';
        $table .= $this->_sDate;

        if ($this->_bDeleteable == true) {
            $oDeleteable = new cHTMLLink();
            $oDeletePic = new cHTMLImage($cfg['path']['contenido_fullhtml'] . '/images/delete.gif');
            $oDeleteable->setContent($oDeletePic);
            $oDeleteable->setLink($sess->url("main.php?frame=2&area=note&itemtype=$itemtype&itemid=$itemid&action=note_delete&deleteitem=$deleteitem"));

            $table .= '</td><td style="padding-left:4px;" width="1">' . $oDeleteable->render();
        }
        $table .= '</td></tr></table>';

        $oMessage = new cHTMLDiv;
        $oMessage->setContent($this->_sMessage);
        $oMessage->setStyle("padding-bottom: 8px;");

        $this->setContent(array($table, '<hr style="margin-top:2px;margin-bottom:2px;>', $oMessage));

        return parent::render();
    }

}

class NoteLink extends cHTMLLink {

    /**
     * @var string Object type
     */
    private $_sItemType;

    /**
     * @var string Object ID
     */
    private $_sItemID;

    /**
     * @var boolean If true, shows the note history
     */
    private $_bShowHistory;

    /**
     * @var boolean If true, history items can be deleted
     */
    private $_bDeleteHistoryItems;

    /**
     * Creates a new note link item.
     *
     * This link is used to show the popup from any position within the system.
     * The link contains the note image.
     *
     * @param $sItemType    string    Item type (usually the class name)
     * @param $sItemId        mixed    Item ID (usually the primary key)
     * @return void
     */
    public function NoteLink($sItemType, $sItemID) {
        parent::__construct();

        $img = new cHTMLImage('images/note.gif');
        $img->setStyle('padding-left: 2px; padding-right: 2px;');

        $img->setAlt(i18n('View notes / add note'));
        $this->setLink('#');
        $this->setContent($img->render());
        $this->setAlt(i18n('View notes / add note'));

        $this->_sItemType = $sItemType;
        $this->_sItemID = $sItemID;
        $this->_bShowHistory = false;
        $this->_bDeleteHistoryItems = false;
    }

    /**
     * Enables the display of all note items
     *
     * @return void
     */
    public function enableHistory() {
        $this->_bShowHistory = true;
    }

    /**
     * Disables the display of all note items
     *
     * @return void
     */
    public function disableHistory() {
        $this->_bShowHistory = false;
    }

    /**
     * Enables the delete function in the history view
     *
     * @return void
     */
    public function enableHistoryDelete() {
        $this->_bDeleteHistoryItems = true;
    }

    /**
     * Disables the delete function in the history view
     *
     * @return void
     */
    public function disableHistoryDelete() {
        $this->_bDeleteHistoryItems = false;
    }

    /**
     * Renders the resulting link
     *
     * @return void
     */
    public function render($return = false) {
        global $sess;

        $itemtype = $this->_sItemType;
        $itemid = $this->_sItemID;

        $this->setEvent('click', 'javascript:window.open(' . "'" . $sess->url("main.php?area=note&frame=1&itemtype=$itemtype&itemid=$itemid") . "', 'todo', 'resizable=yes,scrollbars=yes,height=360,width=550');");
        return parent::render($return);
    }

}

?>