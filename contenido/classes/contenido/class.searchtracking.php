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
 * @method cApiSearchTracking createNewItem
 * @method cApiSearchTracking|bool next
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
        parent::__construct(cRegistry::getDbTableName('search_tracking'), 'idsearchtracking');

        $this->_setItemClass('cApiSearchTracking');
    }

    /**
     * Create a new tracking row
     *
     * @param string $searchTerm
     *                          Term the user searched for
     * @param int $searchResults
     *                          Number of results
     * @param string $timestamp [optional]
     *                          Timestamp of the search
     * @param int $idclient [optional]
     *                          Client
     * @param int $idlang [optional]
     *                          Language
     *
     * @return bool
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function create($searchTerm, $searchResults, $timestamp = "", $idclient = 0, $idlang = 0)
    {
        $item = $this->createNewItem();
        $item->set("searchterm", $searchTerm);
        $item->set("results", $searchResults);
        $item->set("datesearched", ($timestamp == "") ? date('Y-m-d H:i:s') : $timestamp);
        $item->set("idclient", ($idclient == 0) ? cRegistry::getClientId() : $idclient);
        $item->set("idlang", ($idlang == 0) ? cRegistry::getLanguageId() : $idlang);

        return $item->store();
    }

    /**
     * Track a search if the setting allows it.
     *
     * @param string $searchTerm
     *         Term the user searched for
     * @param int $resultCount
     *         Number of results
     *
     * @return bool
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function trackSearch($searchTerm, $resultCount)
    {
        if (getEffectiveSetting("search", "term_tracking", "on") != "on") {
            return false;
        }

        return $this->create($searchTerm, $resultCount);
    }

    /**
     * Select all search terms of this client and language and sort them by
     * popularity
     *
     * @param int $idclient [optional]
     *                      Use this client instead of the current one
     * @param int $idlang [optional]
     *                      Use this language instead of the current one
     * @return bool
     * @throws cDbException
     * @deprecated Since 4.10.1, We can't use fields created by AVG or COUNT here! Result sets received by this function will contain all search term entries, not the cumulated ones.
     */
    public function selectPopularSearchTerms($idclient = 0, $idlang = 0)
    {
        return $this->select('idclient=' . (($idclient == 0) ? cRegistry::getClientId() : $idclient)
            . ' AND idlang=' . (($idlang == 0) ? cRegistry::getLanguageId() : $idlang),
            'searchterm, idsearchtracking, idclient, idlang, results, datesearched',
            'COUNT(searchterm) DESC'
        );
    }

    /**
     * Query all search terms of this client and language, group them by search
     * term and sort them by popularity.
     *
     * The record sets created by this query contain following fields:
     * - searchterm = The search term
     * - avgresults = Average result of the search term
     * - countsearchterm = The number of search for the search term
     *
     * @param int $idclient [optional]
     *                      Use this client instead of the current one
     * @param int $idlang [optional]
     *                      Use this language instead of the current one
     * @return cDb
     * @throws cDbException
     */
    public function queryPopularSearchTerms($idclient = 0, $idlang = 0)
    {
        $idclient = ($idclient == 0) ? cRegistry::getClientId() : $idclient;
        $idlang = ($idlang == 0) ? cRegistry::getLanguageId() : $idclient;
        $db = cRegistry::getDb(); // Don't use own db instance, use a new one!
        $sql = 'SELECT searchterm, AVG(results) AS avgresults, COUNT(searchterm) AS countsearchterm FROM `%s` '
            . 'WHERE idclient=%d AND idlang=%d GROUP BY searchterm ORDER BY COUNT(searchterm) DESC';
        $db->query($sql, $this->table, $idclient, $idlang);
        return $db;
    }

    /**
     * Select all entries about one search term for this client and language
     * sorted by the date
     *
     * @param string $term
     *                         Term the user searched for
     * @param int $idclient [optional]
     *                         Use this client instead of the current one
     * @param int $idlang [optional]
     *                         Use this language instead of the current one
     * @return bool
     * @throws cDbException
     */
    public function selectSearchTerm($term, $idclient = 0, $idlang = 0)
    {
        return $this->select('searchterm=\'' . addslashes($term) . '\' AND idclient='
            . (($idclient == 0) ? cRegistry::getClientId() : $idclient) . ' AND idlang='
            . (($idlang == 0) ? cRegistry::getLanguageId() : $idlang), '', 'datesearched DESC');
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
     * @param bool $mId [optional]
     *                  Item Id
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct($mId = false)
    {
        parent::__construct(cRegistry::getDbTableName('search_tracking'), 'idsearchtracking');
        $this->setFilters(['addslashes'], ['stripslashes']);
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

}
