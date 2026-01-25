<?php

/**
 * This file contains the actionlog collection and item class.
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
 * Action log collection.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiActionlog>
 */
class cApiActionlogCollection extends ItemCollection
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
     * Tables user, client, language, action & category_article are allowed as join partners.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('actionlog'), 'idlog');
        $this->_setItemClass('cApiActionlog');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiUserCollection');
        $this->_setJoinPartner('cApiClientCollection');
        $this->_setJoinPartner('cApiLanguageCollection');
        $this->_setJoinPartner('cApiActionCollection');
        $this->_setJoinPartner('cApiCategoryArticleCollection');
    }

    /**
     * Creates an actionlog item.
     *
     * @param string $userId User id
     * @param int $clientId
     * @param int $languageId
     * @param int $actionId
     * @param int $categoryArticleId
     * @param string $logtimestamp [optional]
     * @return cApiActionlog
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($userId, $clientId, $languageId, $actionId, $categoryArticleId, $logtimestamp = '')
    {
        $item = $this->createNewItem();

        if (empty($logtimestamp)) {
            $logtimestamp = date('Y-m-d H:i:s');
        }

        $item->set('user_id', $userId);
        $item->set('idclient', $clientId);
        $item->set('idlang', $languageId);
        $item->set('idaction', $actionId);
        $item->set('idcatart', $categoryArticleId);
        $item->set('logtimestamp', $logtimestamp);

        $item->store();

        return $item;
    }

    /**
     * Returns the minimum and maximum action log timestamps.
     *
     * @return array{min: string, max: string}|null Array or null, if no entries where found.
     * @throws cDbException
     * @since CONTENIDO 4.10.2
     */
    public function getMinMaxLogTimestamp(): ?array
    {
        $sql = 'SELECT MIN(`logtimestamp`) AS `min`, MAX(`logtimestamp`) AS `max` FROM `%s`';
        $this->db->query($sql, $this->getTable());
        if ($this->db->nextRecord()) {
            return [
                'min' => $this->db->f('min'),
                'max' => $this->db->f('max'),
            ];
        } else {
            return null;
        }
    }

}

/**
 * Action log item.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiActionlog extends Item
{

    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id The ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('actionlog'), 'idlog');
        $this->setFilters();
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * User-defined setter for action log fields.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'idlang':
            case 'idaction':
            case 'idcatart':
            case 'idclient':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $safe);
    }

}
