<?php

/**
 * This file contains various to-do classes.
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
 * This class uses the communication collection to serve a special collection
 * for to-do entries.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class TODOCollection extends cApiCommunicationCollection
{

    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct();
        $this->_setItemClass('TODOItem');
    }

    /**
     * Selects all entries from the database.
     * Objects are loaded using their primary key.
     *
     * @param string $where    [optional]
     *                         Specifies the where clause.
     * @param string $group_by [optional]
     *                         Specifies the group by clause.
     * @param string $order_by [optional]
     *                         Specifies the order by clause.
     * @param string $limit    [optional]
     *                         Specifies the limit by clause.
     *
     * @return bool
     *         True on success, otherwise false
     *
     * @throws cDbException
     */
    public function select($where = '', $group_by = '', $order_by = '', $limit = '') {
        if ($where == '') {
            $where = "`comtype` = 'todo'";
        } else {
            $where .= " AND `comtype`= 'todo'";
        }

        return parent::select($where, $group_by, $order_by, $limit);
    }

    /**
     * Creates a new communication item
     *
     * @param string     $itemtype
     * @param int|string $itemid
     * @param int|string $reminderdate
     *          if not given as timestamp it is expected to be a string
     *          using the English date format
     * @param string     $subject
     * @param string     $content
     * @param string     $notimail
     * @param string     $notibackend
     * @param string     $recipient
     *
     * @return cApiCommunication
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function createItem(
        $itemtype, $itemid, $reminderdate, $subject, $content, $notimail, $notibackend, $recipient
    ): cApiCommunication
    {
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
     * @return array
     * @throws cException
     */
    public function getStatusTypes(): array
    {
        return [
            'new'      => i18n('New'),
            'progress' => i18n('In progress'),
            'done'     => i18n('Done'),
            'waiting'  => i18n('Waiting for action'),
            'deferred' => i18n('Deferred'),
        ];
    }

    /**
     * @return array
     * @throws cException
     */
    public function getPriorityTypes(): array
    {
        return [
            'low'         => i18n('Low'),
            'medium'      => i18n('Medium'),
            'high'        => i18n('High'),
            'immediately' => i18n('Immediately'),
        ];
    }
}

/**
 * This class uses the communication collection to serve a special collection
 * for to-do entries.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class TODOItem extends cApiCommunication
{

    /**
     * Sets a custom property.
     *
     * @see Item::setProperty()
     *
     * @param string $type
     *                       Specifies the type
     * @param string $name
     *                       Specifies the name
     * @param mixed  $value
     *                       Specifies the value
     * @param int    $client [optional]
     *                       unused (should be "Id of client to set property for")
     *
     * @return bool
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function setProperty($type, $name, $value, $client = 0): bool
    {
        if ($type == 'todo' && $name == 'emailnoti') {
            if ($value) {
                parent::setProperty('todo', 'emailnoti-sent', false);
                $value = true;
            } else {
                $value = false;
            }
        }

        return parent::setProperty($type, $name, $value);
    }

}

/**
 * This class uses the link GUI class to serve a special link for to-do entries.
 *
 * @package    Core
 * @subpackage GUI
 */
class TODOLink extends cHTMLLink
{

    /**
     * Constructor to create an instance of this class.
     *
     * @param string $itemtype
     * @param int|string $itemid
     * @param string $subject
     * @param string $message
     * @throws cException
     */
    public function __construct($itemtype, $itemid, $subject, $message)
    {
        parent::__construct();

        $subject = urlencode($subject);
        $message = urlencode($message);

        $sess = cRegistry::getSession();
        $url =  $sess->url("main.php?subject=$subject&message=$message&area=todo&frame=1&itemtype=$itemtype&itemid=$itemid");
        $this->setEvent('click', 'javascript:window.open(' . "'" . $url . "', 'todo', 'scrollbars=yes,resizable=yes,height=350,width=625');");

        $img = new cHTMLImage('images/but_setreminder.gif');
        $img->setAlt(i18n('Set reminder / add to todo list'));

        // Don't set 'javascript:void(0)' here, it will remove the click handler from above!
        $this->setLink('#');
        $this->setClass('con_img_button');
        $this->setContent($img->render());
        $this->setAlt(i18n('Set reminder / add to todo list'));
    }

}
