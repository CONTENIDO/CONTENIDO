<?php

/**
 * This file contains the system property collection and item class.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * User password request collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @method cApiUserPasswordRequest|bool next
 */
class cApiUserPasswordRequestCollection extends ItemCollection
{

    /**
     * Constructor to create an instance of this class.
     *
     * @param string|bool $where [optional]
     *                           The where clause in the select, usable to run select by creating
     *                           the instance.
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function __construct($where = false)
    {
        parent::__construct(cRegistry::getDbTableName('user_pw_request'), 'id_pwreq');
        $this->_setItemClass('cApiUserPasswordRequest');
        if ($where !== false) {
            $this->select($where);
        }
    }

    /**
     * @deprecated [2023-02-02] Since 4.10.2, use {@see cApiUserPasswordRequestCollection::create} instead
     */
    public function createNewItem($data = NULL)
    {
        cDeprecated("The function createNewItem() is deprecated since CONTENIDO 4.10.2, use cApiUserPasswordRequestCollection::create() instead.");
        return $this->create($data);
    }

    /**
     * Create a user password request by user id.
     *
     * @param string|array $data [optional]
     *                           optional parameter for direct input of primary key value
     *                           (string) or multiple column name - value pairs
     *
     * @return cApiUserPasswordRequest|Item
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function create($data = NULL)
    {
        $item = parent::createNewItem($data);

        $expiration = cPasswordRequest::getExpirationSetting();
        $time = new DateTime($expiration, new DateTimeZone('UTC'));
        $item->set('expiration', $this->escape($time->format('Y-m-d H:i:s')));

        return $item;
    }

    /**
     * Removes the specified entries from the database by user's id.
     *
     * @param int $userid
     *         Specifies the user id
     *
     * @return bool
     *         True if the deletion was successful
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function deleteByUserId($userid)
    {
        $result = $this->deleteBy('user_id', $userid);
        return $result > 0;
    }

    /**
     * Removes the specified entries from the database by token.
     *
     * @param $token
     *
     * @return bool
     *         True if the deletion was successful
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function deleteByToken($token)
    {
        $result = $this->deleteBy('validation_token', $token);
        return $result > 0;
    }

    /**
     * Deletes expired password requests from the corresponding table.
     * the outdated threshold setting for password requests is one day
     * by default (see setting 'pw_request' > 'outdated_threshold'),
     * older password requests will be deleted.
     *
     * @return int The number of deleted records
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function deleteExpired(): int
    {
        // Get the outdated threshold setting for password requests,
        // by default 1 day old requests are outdated
        $outdatedStr = cPasswordRequest::getOutdatedThresholdSetting();

        // Convert times to DateTime objects for comparison
        // force all data to be compared using UTC timezone
        $outdated = new DateTime('now', new DateTimeZone('UTC'));
        $outdated->modify($outdatedStr);

        $where = "`expiration` IS NULL OR `expiration` < '%s'";
        $where = $this->prepare($where, $outdated->format('Y-m-d H:i:s'));

        $ids = $this->getIdsByWhereClause($where);

        return $this->_deleteMultiple($ids);
    }

    /**
     * Returns all password requests available in the system
     *
     * @param string|int|bool $userid [optional]
     *                        search for a specific user id
     * @param string $orderBy [optional]
     *                        SQL order by part
     * @return cApiUserPasswordRequest[]
     * @throws cDbException
     * @throws cException
     */
    public function fetchAvailableRequests($userid = false, $orderBy = '`id_pwreq` ASC')
    {
        if (false === $userid) {
            $this->select('', '', $this->escape($orderBy));
        } else {
            $this->select('user_id = \'' . $this->escape($userid) . '\'', '', $this->escape($orderBy));
        }

        $requests = [];
        while (($oItem = $this->next()) !== false) {
            $requests[] = clone $oItem;
        }

        return $requests;
    }

    /**
     * Returns all non expired password requests
     *
     * @param string|int|bool $userid [optional]
     *                     search for a specific user id
     * @return array
     * @throws cDbException
     * @throws cException
     */
    public function fetchCurrentRequests($userid = false)
    {
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $this->select('`expiration` > \'' . $this->escape($now->format('Y-m-d H:i:s')) . '\'');

        $requests = [];
        while (($oItem = $this->next()) !== false) {
            if (false === $userid) {
                $requests[] = clone $oItem;
            } elseif ($oItem->get('user_id') === $userid) {
                $requests[] = clone $oItem;
            }
        }

        return $requests;
    }

    /**
     * Returns the last (newest) password request time of a specific user.
     *
     * @param string|int $userid
     * @return string The time in string format, empty string if no entry could found.
     * @throws cDbException
     * @throws cException
     * @since CONTENIDO 4.10.2
     */
    public function getLastPasswordRequestTimeByUserIId($userid): string
    {
        // Get the last (newest) password request of the user
        $oUserPwRequestCol = new self();
        $oUserPwRequestCol->addResultField('request');
        $oUserPwRequestCol->setWhere('user_id', $userid);
        $oUserPwRequestCol->setOrder('`request` DESC');
        $oUserPwRequestCol->setLimit(0, 1);
        $oUserPwRequestCol->query();
        $data = $oUserPwRequestCol->fetchTable(['request']);
        return !empty($data) ? cSecurity::toString($data[1]['request']) : '';
    }

    /**
     * Returns the number of made password request for a specific user.
     *
     * @param string|int $userid
     * @return int The number of password requests
     * @throws cDbException
     * @throws cException
     * @since CONTENIDO 4.10.2
     */
    public function getPasswordRequestsCountByUserIId($userid): int
    {
        // Get the last (newest) password request of the user
        $sql = "SELECT COUNT(*) AS `count` FROM `%s` WHERE `user_id` = '%s'";
        $sql = $this->prepare($sql, $this->getTable(), $userid);
        $this->db->query($sql, $this->getTable(), $userid);
        return ($this->db->nextRecord()) ? cSecurity::toInteger($this->db->f('count')) : 0;
    }

}

/**
 * User password request item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiUserPasswordRequest extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $mId [optional]
     *                   Specifies the ID of item to load
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct($mId = false)
    {
        parent::__construct(cRegistry::getDbTableName('user_pw_request'), 'id_pwreq');
        $this->setFilters([], []);
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

}
