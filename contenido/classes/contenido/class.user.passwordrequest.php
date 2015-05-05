<?php
/**
 * This file contains the system property collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @version SVN Revision $Rev:$
 *
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * User password request collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiUserPasswordRequestCollection extends ItemCollection {

    /**
     * Constructor function.
     *
     * @global array $cfg
     * @param string|bool $where
     *         The where clause in the select, usable to run select by creating
     *         the instance.
     */
    public function __construct($where = false) {
        global $cfg;
        parent::__construct($cfg['tab']['user_pw_request'], 'id_pwreq');
        $this->_setItemClass('cApiUserPasswordRequest');
        if ($where !== false) {
            $this->select($where);
        }
    }

    /**
     * Create a user password request by user id.
     *
     * @param int $userid
     * @return cApiUserPasswordRequest
     */
    public function createNewItem($data = NULL) {
        $item = parent::createNewItem($data);

        // check configuration setting for different password expiration
        // value must be valid string for DateTime's time variable in its constructor
        if (false === ($expiration = getEffectiveSetting('pw_request', 'user_password_reset_expiration'))
        || 0 === strlen($expiration)) {
            $expiration = '+4 hour';
        }
        $time = new DateTime('+' . $expiration, new DateTimeZone('UTC'));
        $item->set('expiration', $this->escape($time->format('Y-m-d H:i:s')));

        return $item;
    }

    /**
     * Removes the specified entries from the database by user's id.
     *
     * @param int $userid
     *         Specifies the user id
     * @return bool
     *         True if the delete was successful
     */
    public function deleteByUserId($userid) {
        $result = $this->deleteBy('user_id', $userid);
        return ($result > 0) ? true : false;
    }

    /**
     * Removes the specified entries from the database by token.
     *
     * @param int $userid
     *         Specifies the user id
     * @return bool
     *         True if the delete was successful
     */
    public function deleteByToken($token) {
        $result = $this->deleteBy('validation_token', $token);
        return ($result > 0) ? true : false;
    }

    /**
     * Returns all password requests available in the system
     *
     * @param string $userid
     *         search for a specific user id
     * @param string $orderBy
     *         SQL order by part
     * @return array
     */
    public function fetchAvailableRequests($userid = false, $orderBy = 'id_pwreq ASC') {
        $requests = array();

        if (false === $userid) {
            $this->select('', '', $this->escape($orderBy));
        } else {
            $this->select('user_id = \'' . $this->escape($userid) . '\'', '', $this->escape($orderBy));
        }
        while (($oItem = $this->next()) !== false) {
            $requests[] = clone $oItem;
        }

        return $requests;
    }

    /**
     * Returns all non expired password requests
     *
     * @param string $userid
     *         search for a specific user id
     * @return array
     */
    public function fetchCurrentRequests($userid = false) {
        $requests = array();

        $now = new DateTime('now', new DateTimeZone('UTC'));
        $this->select('expiration > \'' . $this->escape($now->format('Y-m-d H:i:s')) . '\'');
        while (($oItem = $this->next()) !== false) {
            if (false === $userid) {
                $requests[] = clone $oItem;
            } elseif ($oItem->get('user_id') === $userid) {
                $requests[] = clone $oItem;
            }
        }

        return $requests;
    }
}

/**
 * User password request item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiUserPasswordRequest extends Item {

    /**
     * Constructor function
     *
     * @param mixed $mId
     *         Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['user_pw_request'], 'id_pwreq');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }
}
