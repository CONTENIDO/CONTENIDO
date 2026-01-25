<?php

/**
 * This file contains the right collection and item class.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Right collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiRight>
 */
class cApiRightCollection extends ItemCollection
{

    use cItemCollectionIdsByClientIdAndLanguageIdTrait;

    /**
     * @var string Client id foreign key field name
     * @since CONTENIDO 4.10.2
     */
    private $fkClientIdName = 'idclient';

    /**
     * @var string Language id foreign key field name
     * @since CONTENIDO 4.10.2
     */
    private $fkLanguageIdName = 'idlang';

    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('rights'), 'idright');
        $this->_setItemClass('cApiRight');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiUserCollection');
        $this->_setJoinPartner('cApiAreaCollection');
        $this->_setJoinPartner('cApiActionCollection');
        $this->_setJoinPartner('cApiCategoryCollection');
        $this->_setJoinPartner('cApiClientCollection');
        $this->_setJoinPartner('cApiLanguageCollection');
    }

    /**
     * Creates a right entry.
     *
     * @param string $userId
     * @param int $areaId
     * @param int $actionId
     * @param int $categoryId
     * @param int $clientId
     * @param int $languageId
     * @param int $type
     * @return cApiRight
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($userId, $areaId, $actionId, $categoryId, $clientId, $languageId, $type)
    {
        $oItem = $this->createNewItem();

        $oItem->set('user_id', $userId);
        $oItem->set('idarea', $areaId);
        $oItem->set('idaction', $actionId);
        $oItem->set('idcat', $categoryId);
        $oItem->set('idclient', $clientId);
        $oItem->set('idlang', $languageId);
        $oItem->set('type', $type);

        $oItem->store();

        return $oItem;
    }

    /**
     * Checks if a specific user has frontend access to a protected category.
     *
     * @param int $categoryId
     * @param string $userId
     * @throws cDbException
     */
    public function hasFrontendAccessByCatIdAndUserId($categoryId, $userId): bool
    {
        $sql = "SELECT :pk FROM `:rights` AS A, `:actions` AS B, `:area` AS C
                WHERE B.name = 'front_allow' AND C.name = 'str' AND A.user_id = ':userid'
                    AND A.idcat = :idcat AND A.idarea = C.idarea AND B.idaction = A.idaction
                LIMIT 1";

        $params = [
            'pk' => $this->getPrimaryKeyName(),
            'rights' => $this->table,
            'actions' => cDb::getTableName('actions'),
            'area' => cDb::getTableName('area'),
            'userid' => $userId,
            'idcat' => (int)$categoryId,
        ];

        $sql = $this->db->prepare($sql, $params);
        $this->db->query($sql);
        return $this->db->nextRecord();
    }

    /**
     * Deletes right entries by user id.
     *
     * @param string $userId
     * @throws cDbException|cInvalidArgumentException
     * @todo Implement functions to delete rights by area, action, cat, client, language.
     */
    public function deleteByUserId($userId)
    {
        return $this->deleteBy('user_id', $userId) > 0;
    }

}

/**
 * Right item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiRight extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id The ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('rights'), 'idright');
        $this->setFilters();
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * User-defined setter for right fields.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'idaction':
            case 'idcat':
            case 'idclient':
            case 'idlang':
            case 'type':
            case 'idarea':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $safe);
    }

}
