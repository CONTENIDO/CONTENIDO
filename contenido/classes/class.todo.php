<?php
/**
 * This file contains various to-do classes.
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
 * for to-do entries.
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class TODOCollection extends cApiCommunicationCollection {

    /**
     */
    public function __construct() {
        parent::__construct();
        $this->_setItemClass('TODOItem');
    }

    /**
     * (non-PHPdoc)
     *
     * @see ItemCollection::select()
     */
    public function select($where = '', $group_by = '', $order_by = '', $limit = '') {
        if ($where == '') {
            $where = "comtype='todo'";
        } else {
            $where .= " AND comtype='todo'";
        }

        return parent::select($where, $group_by, $order_by, $limit);
    }

    /**
     * Creates a new communication item
     *
     * @param unknown_type $itemtype
     * @param unknown_type $itemid
     * @param unknown_type $reminderdate
     * @param string $subject
     * @param string $content
     * @param unknown_type $notimail
     * @param unknown_type $notibackend
     * @param string $recipient
     * @return Ambigous <cApiCommunication, Item, object>
     */
    public function createItem($itemtype, $itemid, $reminderdate, $subject, $content, $notimail, $notibackend, $recipient) {
        $item = parent::create();

        $item->set('comtype', 'todo');
        $item->set('subject', $subject);
        $item->set('message', $content);
        $item->set('recipient', $recipient);
        $item->store();

        if ($notimail === true) {
            $notimail = 1;
        }

        // Is the date passed as string?
        if (!is_numeric($reminderdate)) {
            // Convert to timestamp
            $reminderdate = strtotime($reminderdate);
        }

        $item->setProperty('todo', 'reminderdate', $reminderdate);
        $item->setProperty('todo', 'itemtype', $itemtype);
        $item->setProperty('todo', 'itemid', $itemid);
        $item->setProperty('todo', 'emailnoti', $notimail);
        $item->setProperty('todo', 'backendnoti', $notibackend);
        $item->setProperty('todo', 'status', 'new');
        $item->setProperty('todo', 'priority', 'medium');
        $item->setProperty('todo', 'progress', '0');

        return $item;
    }

    /**
     *
     * @return array
     */
    public function getStatusTypes() {
        return array(
            'new' => i18n('New'),
            'progress' => i18n('In progress'),
            'done' => i18n('Done'),
            'waiting' => i18n('Waiting for action'),
            'deferred' => i18n('Deferred')
        );
    }

    /**
     *
     * @return array
     */
    public function getPriorityTypes() {
        return array(
            'low' => i18n('Low'),
            'medium' => i18n('Medium'),
            'high' => i18n('High'),
            'immediately' => i18n('Immediately')
        );
    }
}

/**
 * This class uses the communication collection to serve a special collection
 * for to-do entries.
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class TODOItem extends cApiCommunication {

    /**
     * (non-PHPdoc)
     *
     * @see Item::setProperty()
     * @todo should return return value of overloaded method
     */
    public function setProperty($type, $name, $value, $client = 0) {
        if ($type == 'todo' && $name == 'emailnoti') {
            if ($value) {
                parent::setProperty('todo', 'emailnoti-sent', false);
                $value = true;
            } else {
                $value = false;
            }
        }

        parent::setProperty($type, $name, $value);
    }
}

/**
 * This class uses the link GUI class to serve a special link for to-do entries.
 *
 * @package Core
 * @subpackage GUI
 */
class TODOLink extends cHTMLLink {

    /**
     *
     * @param unknown_type $itemtype
     * @param unknown_type $itemid
     * @param unknown_type $subject
     * @param unknown_type $message
     */
    public function __construct($itemtype, $itemid, $subject, $message) {
        global $sess;
        parent::__construct();

        $subject = urlencode($subject);
        $message = urlencode($message);

        $this->setEvent('click', 'javascript:window.open(' . "'" . $sess->url("main.php?subject=$subject&message=$message&area=todo&frame=1&itemtype=$itemtype&itemid=$itemid") . "', 'todo', 'scrollbars=yes,resizable=yes,height=350,width=625');");
        $this->setEvent('mouseover', "this.style.cursor='pointer'");

        $img = new cHTMLImage('images/but_setreminder.gif');
        $img->setClass("vAlignMiddle tableElement");

        $img->setAlt(i18n('Set reminder / add to todo list'));
        $this->setLink('#');
        $this->setContent($img->render());
        $this->setAlt(i18n('Set reminder / add to todo list'));
    }
}

?>