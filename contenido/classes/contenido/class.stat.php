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
     * @param int $categoryArticleId
     * @param int $languageId
     * @param int $clientId
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function trackVisit($categoryArticleId, $languageId, $clientId)
    {
        $oStat = $this->fetchByCatArtAndLang($categoryArticleId, $languageId);
        if (is_object($oStat)) {
            $oStat->increment();
        } else {
            $this->create($categoryArticleId, $languageId, $clientId);
        }
    }

    /**
     * Creates a stat entry.
     *
     * @param int $categoryArticleId
     * @param int $languageId
     * @param int $clientId
     * @param int $visited [optional]
     * @return cApiStat
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($categoryArticleId, $languageId, $clientId, $visited = 1)
    {
        $oItem = $this->createNewItem();

        $oItem->set('visited', $visited);
        $oItem->set('idcatart', $categoryArticleId);
        $oItem->set('idlang', $languageId);
        $oItem->set('idclient', $clientId);
        $oItem->store();

        return $oItem;
    }

    /**
     * Returns a stat entry by category article and language.
     *
     * @param int $categoryArticleId
     * @param int $languageId
     * @throws cDbException|cException
     */
    public function fetchByCatArtAndLang($categoryArticleId, $languageId): ?cApiStat
    {
        $this->select($this->db->prepare(
            '`idcatart` = %d AND `idlang` = %d',
            $categoryArticleId,
            $languageId
        ));

        return (($item = $this->next()) instanceof cApiStat) ? $item : null;
    }

    /**
     * Deletes statistics entries by category article id and language id.
     *
     * @param int $categoryArticleId
     * @param int $languageId
     * @return int Number of deleted items
     * @throws cDbException|cInvalidArgumentException
     */
    public function deleteByCategoryArticleAndLanguage($categoryArticleId, $languageId): int
    {
        return $this->deleteByWhereClause($this->db->prepare(
            'idcatart = %d AND idlang = %d',
            $categoryArticleId,
            $languageId
        ));
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
    public function increment(): bool
    {
        $this->set('visited', $this->get('visited') + 1);

        return $this->store();
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
