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
 * @extends ItemCollection<cApiUserPasswordRequest>
 */
class cApiUserPasswordRequestCollection extends ItemCollection
{

    /**
     * Constructor to create an instance of this class.
     *
     * @param string|bool $where [optional] The where clause in the select, usable to run select by creating
     *      the instance.
     * @throws cDbException|cInvalidArgumentException
     */
    public function __construct($where = false)
    {
        parent::__construct(cDb::getTableName('user_pw_request'), 'id_pwreq');
        $this->_setItemClass('cApiUserPasswordRequest');
        if ($where !== false) {
            $this->select($where);
        }
    }

    /**
     * Create a user password request by user id.
     *
     * @param string|array $data [optional] optional parameter for direct input of primary key value
     *      (string) or multiple column name - value pairs
     * @return cApiUserPasswordRequest
     * @throws cDbException|cException|cInvalidArgumentException|Exception
     */
    public function create($data = NULL)
    {
        $item = $this->createNewItem($data);

        $expiration = cPasswordRequest::getExpirationSetting();
        $time = new DateTime($expiration, new DateTimeZone('UTC'));
        $item->set('expiration', $this->escape($time->format('Y-m-d H:i:s')));

        return $item;
    }

    /**
     * Removes the specified entries from the database by user's id.
     *
     * @param string $userId Specifies the user id
     * @return bool True if the deletion was successful
     * @throws cDbException|cInvalidArgumentException
     */
    public function deleteByUserId(string $userId): bool
    {
        return $this->deleteBy('user_id', $userId) > 0;
    }

    /**
     * Removes the specified entries from the database by token.
     *
     * @param string $token
     * @return bool True if the deletion was successful
     * @throws cDbException|cInvalidArgumentException
     */
    public function deleteByToken(string $token): bool
    {
        return $this->deleteBy('validation_token', $token) > 0;
    }

    /**
     * Deletes expired password requests from the corresponding table.
     * the outdated threshold setting for password requests is one day by default
     * (see setting 'pw_request' > 'outdated_threshold'), older password requests will be deleted.
     *
     * @return int The number of deleted records
     * @throws cDbException|cException|cInvalidArgumentException|Exception
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
     * @param string|false $userId [optional] Search for a specific user id
     * @param string $orderBy [optional] SQL order by part
     * @return cApiUserPasswordRequest[]
     * @throws cDbException|cException
     */
    public function fetchAvailableRequests($userId = false, string $orderBy = '`id_pwreq` ASC'): array
    {
        if (!$userId) {
            $this->select('', '', $this->escape($orderBy));
        } else {
            $this->select(
                sprintf("user_id = '%s'", $this->escape($userId)),
                '',
                $this->escape($orderBy)
            );
        }

        $requests = [];
        while ($item = $this->next()) {
            $requests[] = clone $item;
        }

        return $requests;
    }

    /**
     * Returns all non expired password requests
     *
     * @param string|false $userId [optional] Search for a specific user id
     * @return cApiUserPasswordRequest[]
     * @throws cDbException|cException|DateMalformedStringException
     */
    public function fetchCurrentRequests($userId = false): array
    {
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $this->select(sprintf("`expiration` > '%s'", $now->format('Y-m-d H:i:s')));

        $requests = [];
        while ($item = $this->next()) {
            if (!$userId) {
                $requests[] = clone $item;
            } elseif ($item->get('user_id') === $userId) {
                $requests[] = clone $item;
            }
        }

        return $requests;
    }

    /**
     * Returns the last (newest) password request time of a specific user.
     *
     * @param string $userId
     * @return string The time in string format, empty string if no entry could found.
     * @throws cDbException|cException
     * @since CONTENIDO 4.10.2
     */
    public function getLastPasswordRequestTimeByUserIId(string $userId): string
    {
        // Get the last (newest) password request of the user
        $oUserPwRequestCol = new self();
        $oUserPwRequestCol->addResultField('request');
        $oUserPwRequestCol->setWhere('user_id', $userId);
        $oUserPwRequestCol->setOrder('`request` DESC');
        $oUserPwRequestCol->setLimit(0, 1);
        $oUserPwRequestCol->query();
        $data = $oUserPwRequestCol->fetchTable(['request']);

        return !empty($data) ? cSecurity::toString($data[1]['request']) : '';
    }

    /**
     * Returns the number of made password request for a specific user.
     *
     * @return int The number of password requests
     * @throws cDbException|cException
     * @since CONTENIDO 4.10.2
     */
    public function getPasswordRequestsCountByUserIId(string $userId): int
    {
        // Get the last (newest) password request of the user
        $this->db->query(
            "SELECT COUNT(*) AS `count` FROM `%s` WHERE `user_id` = '%s'",
            $this->getTable(),
            $this->escape($userId)
        );

        return $this->db->nextRecord() ? cSecurity::toInteger($this->db->f('count')) : 0;
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
     * @param mixed $id The ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('user_pw_request'), 'id_pwreq');
        $this->setFilters();
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

}
