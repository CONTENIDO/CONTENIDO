<?php

/**
 * This file contains the stat collection and item class.
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
 * Statistic collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiStat>
 */
class cApiStatCollection extends ItemCollection
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
        parent::__construct(cDb::getTableName('stat'), 'idstat');
        $this->_setItemClass('cApiStat');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiCategoryArticleCollection');
        $this->_setJoinPartner('cApiLanguageCollection');
        $this->_setJoinPartner('cApiClientCollection');
    }

    /**
     * Tracks a visit.
     * Increments an existing entry or creates a new one.
     *
     * @param int $idCatArt
     * @param int $idLang
     * @param int $idClient
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function trackVisit($idCatArt, $idLang, $idClient)
    {
        $oStat = $this->fetchByCatArtAndLang($idCatArt, $idLang);
        if (is_object($oStat)) {
            $oStat->increment();
        } else {
            $this->create($idCatArt, $idLang, $idClient);
        }
    }

    /**
     * Creates a stat entry.
     *
     * @param int $idCatArt
     * @param int $idLang
     * @param int $idClient
     * @param int $visited [optional]
     * @return cApiStat
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($idCatArt, $idLang, $idClient, $visited = 1)
    {
        $oItem = $this->createNewItem();

        $oItem->set('visited', $visited);
        $oItem->set('idcatart', $idCatArt);
        $oItem->set('idlang', $idLang);
        $oItem->set('idclient', $idClient);
        $oItem->store();

        return $oItem;
    }

    /**
     * Returns a stat entry by category article and language.
     *
     * @param int $idCatArt
     * @param int $idLang
     * @return cApiStat|NULL
     * @throws cDbException|cException
     */
    public function fetchByCatArtAndLang($idCatArt, $idLang)
    {
        $where = $this->db->prepare('idcatart = %d AND idlang = %d', $idCatArt, $idLang);
        $this->select($where);
        return $this->next();
    }

    /**
     * Deletes statistics entries by category article id and language id.
     *
     * @param int $idCatArt
     * @param int $idLang
     * @return int Number of deleted items
     * @throws cDbException|cInvalidArgumentException
     */
    public function deleteByCategoryArticleAndLanguage($idCatArt, $idLang): int
    {
        $where = $this->db->prepare('idcatart = %d AND idlang = %d', $idCatArt, $idLang);
        return $this->deleteByWhereClause($where);
    }
}

/**
 * Statistic item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiStat extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id The ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('stat'), 'idstat');
        $this->setFilters();
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Increment and store property 'visited'.
     *
     * @throws cDbException|cInvalidArgumentException
     */
    public function increment()
    {
        $this->set('visited', $this->get('visited') + 1);
        $this->store();
    }

    /**
     * User-defined setter for stat fields.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'idcatart':
            case 'idlang':
            case 'idclient':
            case 'visited':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $safe);
    }

}
