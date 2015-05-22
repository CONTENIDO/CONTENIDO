<?php
/**
 * This file contains various note classes.
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Unknown
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class uses the communication collection to serve a special collection
 * for notes.
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class NoteCollection extends cApiCommunicationCollection {

    /**
     */
    public function __construct() {
        parent::__construct();
        $this->_setItemClass('NoteItem');
    }

    /**
     * Selects one or more items from the database
     *
     * This function only extends the where statement. See the
     * original function for the parameters.
     *
     * @see ItemCollection::select()
     * @param string $sWhere [optional]
     *         Specifies the where clause.
     * @param string $sGroupBy [optional]
     *         Specifies the group by clause.
     * @param string $sOrderBy [optional]
     *         Specifies the order by clause.
     * @param string $sLimit [optional]
     *         Specifies the limit by clause.
     * @return bool
     *         True on success, otherwhise false
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
     * Creates a new note item.
     *
     * @param string $itemtype
     *         Item type (usually the class name)
     * @param mixed $itemid
     *         Item ID (usually the primary key)
     * @param int $idlang
     *         Language-ID
     * @param string $message
     *         Message to store
     * @param string $category [optional]
     * @return object
     *         The new item
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

/**
 * This class uses the communication item to serve a special item for notes.
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class NoteItem extends cApiCommunication {
}

/**
 * This class uses the iframe GUI class to serve a special iframe for notes.
 *
 * @package Core
 * @subpackage GUI
 */
class NoteView extends cHTMLIFrame {

    /**
     *
     * @param string $sItemType
     * @param string $sItemId
     */
    public function NoteView($sItemType, $sItemId) {
        global $sess, $cfg;
        cHTMLIFrame::cHTMLIFrame();
        $this->setSrc($sess->url("main.php?itemtype=$sItemType&itemid=$sItemId&area=note&frame=2"));
        $this->setBorder(0);
    }
}

/**
 * This class uses the div GUI class to serve a special div for note lists.
 *
 * @package Core
 * @subpackage GUI
 */
class NoteList extends cHTMLDiv {
    protected $_bDeleteable;

    /**
     *
     * @param string $sItemType
     * @param string $sItemId
     */
    public function __construct($sItemType, $sItemId) {
        parent::__construct();

        $this->_sItemType = $sItemType;
        $this->_sItemId = $sItemId;

        $this->appendStyleDefinition('width', '100%');
    }

    /**
     *
     * @param bool $bDeleteable
     */
    public function setDeleteable($bDeleteable) {
        $this->_bDeleteable = $bDeleteable;
    }

    /**
     * (non-PHPdoc)
     *
     * @see cHTML::toHTML()
     * @return string
     *     generated markup
     */
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

        return '<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr><td>' . $result . '</td></tr></table>';
    }
}

/**
 * This class uses the div GUI class to serve a special div for note list items.
 *
 * @package Core
 * @subpackage GUI
 */
class NoteListItem extends cHTMLDiv {

    /**
     *
     * @param string $sItemType
     * @param string $sItemId
     * @param int $iDeleteItem
     */
    public function __construct($sItemType, $sItemId, $iDeleteItem) {
        parent::__construct();
        $this->appendStyleDefinition('padding', '2px');
        $this->setBackground();
        $this->setDeleteable(true);

        $this->_iDeleteItem = $iDeleteItem;
        $this->_sItemType = $sItemType;
        $this->_sItemId = $sItemId;
    }

    /**
     *
     * @param bool $bDeleteable
     */
    public function setDeleteable($bDeleteable) {
        $this->_bDeleteable = $bDeleteable;
    }

    /**
     *
     * @param string $dark [optional]
     */
    public function setBackground($dark = false) {
    }

    /**
     *
     * @param string $sAuthor
     */
    public function setAuthor($sAuthor) {
        if (strlen($sAuthor) == 32) {
            $result = getGroupOrUserName($sAuthor);

            if ($result !== false) {
                $sAuthor = $result;
            }
        }

        $this->_sAuthor = $sAuthor;
    }

    /**
     *
     * @param string|int $iDate
     */
    public function setDate($iDate) {
        $dateformat = getEffectiveSetting('dateformat', 'full', 'Y-m-d H:i:s');

        if (is_string($iDate)) {
            $iDate = strtotime($iDate);
        }
        $this->_sDate = date($dateformat, $iDate);
    }

    /**
     *
     * @param string $sMessage
     */
    public function setMessage($sMessage) {
        $this->_sMessage = $sMessage;
    }

    /**
     *
     * @see cHTML::render()
     * @return string
     *         Generated markup
     */
    public function render() {
        global $sess;
        $itemtype = $this->_sItemType;
        $itemid = $this->_sItemId;
        $deleteitem = $this->_iDeleteItem;

        $table = '<table border="0" cellspacing="0" cellpadding="0" width="100%"><tr><td><b>';
        $table .= $this->_sAuthor;
        $table .= '</b></td><td align="right">';
        $table .= $this->_sDate;

        if ($this->_bDeleteable == true) {
            $oDeleteable = new cHTMLLink();
            $oDeleteable->setClass("vAlignMiddle tableElement");
            $oDeletePic = new cHTMLImage(cRegistry::getBackendUrl() . '/images/delete.gif');
            $oDeleteable->setContent($oDeletePic);
            $oDeleteable->setLink($sess->url("main.php?frame=2&area=note&itemtype=$itemtype&itemid=$itemid&action=note_delete&deleteitem=$deleteitem"));

            $table .= '</td><td width="1">' . $oDeleteable->render();
        }
        $table .= '</td></tr></table>';

        $oMessage = new cHTMLDiv();
        $oMessage->setContent($this->_sMessage);
        $oMessage->setStyle("padding-bottom: 8px; margin-top: 4px;");

        $this->setContent(array(
            $table,
            $oMessage
        ));

        return parent::render();
    }
}

/**
 * This class uses the link GUI class to serve a special link for notes.
 *
 * @package Core
 * @subpackage GUI
 */
class NoteLink extends cHTMLLink {

    /**
     *
     * @var string Object type
     */
    private $_sItemType;

    /**
     *
     * @var string Object ID
     */
    private $_sItemID;

    /**
     *
     * @var bool If true, shows the note history
     */
    private $_bShowHistory;

    /**
     *
     * @var bool If true, history items can be deleted
     */
    private $_bDeleteHistoryItems;

    /**
     * Creates a new note link item.
     *
     * This link is used to show the popup from any position within the system.
     * The link contains the note image.
     *
     * @param string $sItemType
     *         Item type (usually the class name)
     * @param mixed $sItemID
     *         Item ID (usually the primary key)
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
     */
    public function enableHistory() {
        $this->_bShowHistory = true;
    }

    /**
     * Disables the display of all note items
     */
    public function disableHistory() {
        $this->_bShowHistory = false;
    }

    /**
     * Enables the delete function in the history view
     */
    public function enableHistoryDelete() {
        $this->_bDeleteHistoryItems = true;
    }

    /**
     * Disables the delete function in the history view
     */
    public function disableHistoryDelete() {
        $this->_bDeleteHistoryItems = false;
    }

    /**
     * @see cHTML::render()
     * @todo fix unused param $return
     * @param bool $return [optional]
     *         this param is unused
     * @return string
     *         Generated markup
     */
    public function render($return = false) {
        global $sess;

        $itemtype = $this->_sItemType;
        $itemid = $this->_sItemID;

        $this->setEvent('click', 'javascript:window.open(' . "'" . $sess->url("main.php?area=note&frame=1&itemtype=$itemtype&itemid=$itemid") . "', 'todo', 'resizable=yes,scrollbars=yes,height=360,width=550');");
        return parent::render($return);
    }
}
