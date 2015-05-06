<?php
/**
 * This file contains the collections and items for search tracking
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @version SVN Revision $Rev:$
 *
 * @author Mischa Holz
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Tracking collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiSearchTrackingCollection extends ItemCollection {

    /**
     * Basic constructor
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['search_tracking'], 'idsearchtracking');

        $this->_setItemClass('cApiSearchTracking');
    }

    /**
     * Create a new tracking row
     *
     * @param string $searchTerm
     *         Term the user searched for
     * @param int $searchResults
     *         Number of results
     * @param string $timestamp [optional]
     *         Timestamp of the search
     * @param number $idclient [optional]
     *         Client
     * @param number $idlang [optional]
     *         Language
     * @return bool
     */
    public function create($searchTerm, $searchResults, $timestamp = "", $idclient = 0, $idlang = 0) {
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
     * @return bool
     */
    public function trackSearch($searchTerm, $resultCount) {
        if (getEffectiveSetting("search", "term_tracking", "on") != "on") {
            return false;
        }

        return $this->create($searchTerm, $resultCount);
    }

    /**
     * Select all search terms of this client and language and sort them by
     * popularity
     *
     * @param number $idclient [optional]
     *         Use this client instead of the current one
     * @param number $idlang [optional]
     *         Use this language instead of the current one
     * @return bool
     */
    public function selectPopularSearchTerms($idclient = 0, $idlang = 0) {
        return $this->select('idclient=' . (($idclient == 0) ? cRegistry::getClientId() : $idclient) . ' AND idlang=' . (($idlang == 0) ? cRegistry::getLanguageId() : $idlang), 'searchterm', 'COUNT(searchterm) DESC');
    }

    /**
     * Select all entries about one search term for this client and language
     * sorted by the date
     *
     * @param string $term
     *         Term the user searched for
     * @param number $idclient [optional]
     *         Use this client instead of the current one
     * @param number $idlang [optional]
     *         Use this language instead of the current one
     * @return bool
     */
    public function selectSearchTerm($term, $idclient = 0, $idlang = 0) {
        return $this->select('searchterm=\'' . addslashes($term) . '\' AND idclient=' . (($idclient == 0) ? cRegistry::getClientId() : $idclient) . ' AND idlang=' . (($idlang == 0) ? cRegistry::getLanguageId() : $idlang), '', 'datesearched DESC');
    }

}

/**
 * SearchTracking item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiSearchTracking extends Item {

    /**
     * Default constructor
     *
     * @param string $mId [optional]
     *         Item Id
     */
    public function __construct($mId = false) {
        global $cfg;

        parent::__construct($cfg['tab']['search_tracking'], 'idsearchtracking');
        $this->setFilters(array(
            'addslashes'
        ), array(
            'stripslashes'
        ));

        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

}

?>
