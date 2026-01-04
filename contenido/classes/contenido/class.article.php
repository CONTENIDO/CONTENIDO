<?php

/**
 * This file contains the article collection and item class.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.str.php');

/**
 * Article collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiArticle>
 */
class cApiArticleCollection extends ItemCollection
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
     * @param string|false $select [optional] Where clause to use for selection {@see ItemCollection::select()}
     * @throws cDbException|cInvalidArgumentException
     */
    public function __construct($select = false)
    {
        parent::__construct(cDb::getTableName('art'), 'idart');
        $this->_setItemClass('cApiArticle');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiClientCollection');

        if ($select !== false) {
            $this->select($select);
        }
    }

    /**
     * Creates an article item entry
     *
     * @param int $idclient
     * @return cApiArticle
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($idclient)
    {
        $item = $this->createNewItem();

        $item->set('idclient', $idclient);
        $item->store();

        return $item;
    }

    /**
     * Returns list of article ids by given client id.
     *
     * @param int $idclient
     * @return array
     * @throws cDbException
     */
    public function getIdsByClientId($idclient): array
    {
        $sql = "SELECT `idart` FROM `%s` WHERE `idclient` = %d";
        $this->db->query($sql, $this->table, $idclient);
        $list = [];
        while ($this->db->nextRecord()) {
            $list[] = $this->db->f('idart');
        }
        return $list;
    }
}

/**
 * Article item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiArticle extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id Specifies the ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('art'), 'idart');
        $this->setFilters();
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Returns the link to the current object.
     *
     * @param int $changeLangId [optional] Change language id for URL (optional)
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function getLink($changeLangId = 0): string
    {
        if ($this->isLoaded() === false) {
            return '';
        }

        $options = [];
        $options['idart'] = $this->get('idart');
        $options['lang'] = ($changeLangId == 0) ? cRegistry::getLanguageId() : $changeLangId;
        if ($changeLangId > 0) {
            $options['changelang'] = $changeLangId;
        }

        return cUri::getInstance()->build($options);
    }

    /**
     * User-defined setter for article fields.
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
