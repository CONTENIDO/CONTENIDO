<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * TODO / Reminder System
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.1.1
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

class TODOCollection extends cApiCommunicationCollection {

    public function __construct() {
        parent::__construct();
        $this->_setItemClass('TODOItem');
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function TODOCollection() {
        cDeprecated('Use __construct() instead');
        $this->__construct();
    }

    public function select($where = '', $group_by = '', $order_by = '', $limit = '') {
        if ($where == '') {
            $where = "comtype='todo'";
        } else {
            $where .= " AND comtype='todo'";
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
            cDeprecated('Use TODOCollection->createItem() instead TODOCollection->create()');
            return call_user_func_array(array($this, 'createItem'), $arguments);
        }
    }

    /**
     * Creates a new communication item
     */
    public function createItem($itemtype, $itemid, $reminderdate, $subject, $content, $notimail, $notibackend, $recipient) {
        $item = parent::create();

        $item->set('subject', $subject);
        $item->set('message', $content);
        $item->set('comtype', 'todo');
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

    public function getStatusTypes() {
        $statusTypes = array(
            'new' => i18n('New'),
            'progress' => i18n('In progress'),
            'done' => i18n('Done'),
            'waiting' => i18n('Waiting for action'),
            'deferred' => i18n('Deferred')
        );
        return ($statusTypes);
    }

    public function getPriorityTypes() {
        $priorityTypes = array(
            'low' => i18n('Low'),
            'medium' => i18n('Medium'),
            'high' => i18n('High'),
            'immediately' => i18n('Immediately')
        );
        return ($priorityTypes);
    }

}

class TODOItem extends cApiCommunication {

    public function setProperty($type, $name, $value) {
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

class TODOLink extends cHTMLLink {

    public function __construct($itemtype, $itemid, $subject, $message) {
        global $sess;
        parent::__construct();

        $subject = urlencode($subject);
        $message = urlencode($message);

        $this->setEvent('click', 'javascript:window.open(' . "'" . $sess->url("main.php?subject=$subject&message=$message&area=todo&frame=1&itemtype=$itemtype&itemid=$itemid") . "', 'todo', 'scrollbars=yes,resizable=yes,height=350,width=550');");
        $this->setEvent('mouseover', "this.style.cursor='pointer'");

        $img = new cHTMLImage('images/but_setreminder.gif');
        $img->setStyle('padding-left:2px;padding-right:2px;');

        $img->setAlt(i18n('Set reminder / add to todo list'));
        $this->setLink('#');
        $this->setContent($img->render());
        $this->setAlt(i18n('Set reminder / add to todo list'));
    }

}

?>