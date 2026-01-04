<?php

/**
 * This file contains the communication collection and item class.
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
 * Communication collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiCommunication>
 */
class cApiCommunicationCollection extends ItemCollection
{

    use cItemCollectionIdsByClientIdTrait;

    /**
     * @var string Client id foreign key field name
     * @since CONTENIDO 4.10.2
     */
    private $fkClientIdName = 'idclient';

    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('communications'), 'idcommunication');
        $this->_setItemClass('cApiCommunication');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiClientCollection');
    }

    /**
     * Creates a new communication item.
     *
     * @return cApiCommunication
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create()
    {
        $auth = cRegistry::getAuth();
        $client = cRegistry::getClientId();

        $item = $this->createNewItem();

        $item->set('idclient', $client);
        $item->set('author', $auth->getUserId());
        $item->set('created', date('Y-m-d H:i:s'), false);

        return $item;
    }

}

/**
 * Communication item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiCommunication extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id Specifies the ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('communications'), 'idcommunication');
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Saves a communication item
     *
     * @inheritDoc
     */
    public function store()
    {
        $auth = cRegistry::getAuth();
        $this->set('modifiedby', $auth->getUserId());
        $this->set('modified', date('Y-m-d H:i:s'), false);

        return parent::store();
    }

    /**
     * User-defined setter for communication fields.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'idclient':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $safe);
    }

}
