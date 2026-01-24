<?php

/**
 * This file contains the collections and items for search tracking
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @author     Mischa Holz
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Tracking collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiSearchTracking>
 */
class cApiSearchTrackingCollection extends ItemCollection
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
        parent::__construct(cDb::getTableName('search_tracking'), 'idsearchtracking');

        $this->_setItemClass('cApiSearchTracking');
    }

    /**
     * Create a new tracking row
     *
     * @param string $searchTerm Term the user searched for
     * @param int $searchResults Number of results
     * @param string $timestamp [optional] Timestamp of the search
     * @param int $clientId [optional] Client
     * @param int $languageId [optional] Language
     * @return bool
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($searchTerm, $searchResults, $timestamp = '', $clientId = 0, $languageId = 0)
    {
        $item = $this->createNewItem();
        $item->set('searchterm', $searchTerm);
        $item->set('results', $searchResults);
        $item->set('datesearched', ($timestamp == '') ? date('Y-m-d H:i:s') : $timestamp);
        $item->set('idclient', $clientId == 0 ? cRegistry::getClientId() : $clientId);
        $item->set('idlang', $languageId == 0 ? cRegistry::getLanguageId() : $languageId);

        return $item->store();
    }

    /**
     * Track a search if the setting allows it.
     *
     * @param string $searchTerm Term the user searched for
     * @param int $resultCount Number of results
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function trackSearch($searchTerm, $resultCount): bool
    {
        if (getEffectiveSetting('search', 'term_tracking', 'on') != 'on') {
            return false;
        }

        return $this->create($searchTerm, $resultCount);
    }

    /**
     * @deprecated [2019-03-27] Since CONTENIDO 4.10.1, We can't use fields created by AVG or COUNT here! Result sets received by this function will contain all search term entries, not the cumulated ones.
     */
    public function selectPopularSearchTerms($clientId = 0, $languageId = 0)
    {
        return $this->select('idclient=' . ($clientId == 0 ? cRegistry::getClientId() : $clientId)
            . ' AND idlang=' . ($languageId == 0 ? cRegistry::getLanguageId() : $languageId),
            'searchterm, idsearchtracking, idclient, idlang, results, datesearched',
            'COUNT(searchterm) DESC'
        );
    }

    /**
     * Query all search terms of this client and language, group them by search term and sort them by popularity.
     *
     * The record sets created by this query contain following fields:
     * - searchterm = The search term
     * - avgresults = Average result of the search term
     * - countsearchterm = The number of search for the search term
     *
     * @param int $clientId [optional] Use this client instead of the current one
     * @param int $languageId [optional] Use this language instead of the current one
     * @throws cDbException
     */
    public function queryPopularSearchTerms($clientId = 0, $languageId = 0): cDb
    {
        $db = cRegistry::getDb(); // Don't use own db instance, use a new one!
        $db->query(
            "SELECT `searchterm`, AVG(`results`) AS `avgresults`, COUNT(`searchterm`) AS `countsearchterm`
            FROM `%s`
            WHERE `idclient` = %d AND `idlang` = %d
            GROUP BY `searchterm`
            ORDER BY COUNT(`searchterm`) DESC"
            , $this->table,
            $clientId == 0 ? cRegistry::getClientId() : $clientId,
            $languageId == 0 ? cRegistry::getLanguageId() : $clientId
        );

        return $db;
    }

    /**
     * Select all entries about one search term for this client and language sorted by the date
     *
     * @param string $term Term the user searched for
     * @param int $clientId [optional] Use this client instead of the current one
     * @param int $languageId [optional] Use this language instead of the current one
     * @throws cDbException
     */
    public function selectSearchTerm($term, $clientId = 0, $languageId = 0): bool
    {
        return $this->select(
            $this->db->prepare(
                "`searchterm` = '%s' AND `idclient` = %d  AND `idlang` = %d",
                $term,
                ($clientId == 0 ? cRegistry::getClientId() : $clientId),
                ($languageId == 0 ? cRegistry::getLanguageId() : $languageId)
            ),
            '',
            '`datesearched` DESC'
        );
    }

}

/**
 * SearchTracking item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiSearchTracking extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id [optional] Item Id
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('search_tracking'), 'idsearchtracking');
        $this->setFilters(['addslashes'], ['stripslashes']);
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

}
