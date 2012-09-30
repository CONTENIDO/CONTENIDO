<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Utility to get articles of category/categories as cApiArticleLanguage objects.
 * For now, this object will use objects 'cApiArticleLanguage' and 'ArticleCollection'.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO
 * @version    0.2.1
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.8.9
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * @deprecated 2012-09-29 This class is not longer supported. Use cArticleCollector instead.
 */
class Contenido_Category_Articles extends Contenido_Category_Base
{
    /**#@+
     * @var int
     * @access protected
     */
    protected $iClient;
    protected $iLang;
    /**#@+
     * @var obj
     * @access protected
     */
    protected $oArticle;
    protected $oArticleCollection;

    /**
     * Constructor
     * @param DB_Contenido $oDb
     * @param array $aCfg
     * @param int $iClient
     * @param int $iLang
     * @return void
     * @deprecated 2012-09-29 This class is not longer supported. Use cArticleCollector instead.
     */
    public function __construct(DB_Contenido $oDb, array $aCfg, $iClient, $iLang)
    {
        cDeprecated("This class is not longer supported. Use cArticleCollector instead.");
        parent::__construct($oDb, $aCfg);
        $this->setClient($iClient);
        $this->setLang($iLang);
    }

    /**
     * Return array with article-objects of a category.
     * @param int $iCategoryId
     * @param string $sOrderBy
     * @param string $sOrder
     * @param boolean $bArticleIdAsKey
     * @param int $iOnlineStatus 0-offline, 1-online, 2-both
     * @throws cException In case of a sql query that crashes
     * @return array An array with Article objects
     */
    public function getArticlesInCategory($iCategoryId, $sOrderBy = 'creationdate', $sOrder = 'ASC', $bArticleIdAsKey = false, $iOnlineStatus = 2)
    {
        $aReturn = array();
        $sSql = $this->_buildQuery('idcat = '.(int)$iCategoryId, $sOrderBy, $sOrder, $iOnlineStatus);
        if ($this->bDbg === true) {
            $this->oDbg->show($sSql, 'Contenido_Category_Articles::getArticlesInCategory() $sSql');
        }

        $this->oDb->query($sSql);

        $bHasErrors = $this->oDb->Errno == 0 ? $bHasErrors = false : $bHasErrors = true;
        if ($bHasErrors === false && $this->oDb->num_rows() > 0) {
            while ($this->oDb->next_record()) {
                $oArticle = new cApiArticleLanguage();
                $oArticle->loadByArticleAndLanguageId($this->oDb->f('idart'), $this->getLang());
                if ($bArticleIdAsKey === false) {
                    $aReturn[] = clone $oArticle;
                } else {
                    $aReturn[(int) $this->oDb->f('idart')] = clone $oArticle;
                }
            }
        }
        if ($bHasErrors === true) {
            throw new cException('Error in SQL-Query! Errno: '.$this->oDb->Errno.', Error: '.$this->oDb->Error.', SQL: '.$sSql);
        }
        return $aReturn;
    }

    /**
     * Return array with article-objects of a category that are online.
     * @param int $iCategoryId
     * @param string $sOrderBy
     * @param string $sOrder
     * @param boolean $bArticleIdAsKey
     * @param int $iOnlineStatus 0-offline, 1-online, 2-both
     * @return array An array with Article objects
     * @throws cException In case of a sql query that crashes
     */
    public function getOnlineArticlesInCategory($iCategoryId, $sOrderBy = 'creationdate', $sOrder = 'ASC', $bArticleIdAsKey = false)
    {
        try {
            return $this->getArticlesInCategory($iCategoryId, $sOrderBy, $sOrder, $bArticleIdAsKey, 1);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Return array with article-objects of a category that are offline.
     * @param int $iCategoryId
     * @param string $sOrderBy
     * @param string $sOrder
     * @param boolean $bArticleIdAsKey
     * @param int $iOnlineStatus 0-offline, 1-online, 2-both
     * @throws cException In case of a sql query that crashes
     * @return array An array with Article objects
     */
    public function getOfflineArticlesInCategory($iCategoryId, $sOrderBy = 'creationdate', $sOrder = 'ASC', $bArticleIdAsKey = false)
    {
        try {
            return $this->getArticlesInCategory($iCategoryId, $sOrderBy, $sOrder, $bArticleIdAsKey, 0);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Return array with article-objects of a category range.
     *
     * @param array $aCategoryIds
     * @param string $sOrderBy
     * @param string $sOrder
     * @param boolean $bArticleIdAsKey
     * @param int $iOnlineStatus 0-offline, 1-online, 2-both
     * @throws cInvalidArgumentException If given category IDs are empty
     * @throws cException In case of a sql query that crashes or wrong parameters
     * @return array An array with Article objects
     */
    public function getArticlesInCategoryRange(array $aCategoryIds, $sOrderBy = 'creationdate', $sOrder = 'ASC', $bArticleIdAsKey = false, $iOnlineStatus = 2)
    {
        $aReturn = array();
        $aSqlIn = array();
        if (sizeof($aCategoryIds) > 0) {
            foreach ($aCategoryIds as $iId) {
                $aSqlIn[] = (int) $iId;
            }
        } else {
            throw new cInvalidArgumentException('$aCategoryIds must contain at least one item!');
        }
        $sSql = $this->_buildQuery('idcat IN('.implode(', ', $aSqlIn).')', $sOrderBy, $sOrder, $iOnlineStatus);
        if ($this->bDbg === true) {
            $this->oDbg->show($sSql, 'Contenido_Category_Articles::getArticlesInCategory() $sSql');
        }
        $this->oDb->query($sSql);
        $bHasErrors = $this->oDb->Errno == 0 ? false : true;
        if ($bHasErrors === false && $this->oDb->num_rows() > 0) {
            while ($this->oDb->next_record()) {
                $oArticle = new cApiArticleLanguage();
                $oArticle->loadByArticleAndLanguageId($this->oDb->f('idart'), $this->getLang());
                if ($bArticleIdAsKey === false) {
                    $aReturn[] = clone $oArticle;
                } else {
                    $aReturn[(int) $this->oDb->f('idart')] = clone $oArticle;
                }
            }
        }
        if ($bHasErrors === true) {
            throw new cException('Error in SQL-Query! Errno: '.$this->oDb->Errno.', Error: '.$this->oDb->Error.', SQL: '.$sSql);
        }
        return $aReturn;
    }

    /**
     * Return array with online article-objects of a category range.
     * @param array $aCategoryIds
     * @param string $sOrderBy
     * @param string $sOrder
     * @param boolean $bArticleIdAsKey
     * @param int $iOnlineStatus 0-offline, 1-online, 2-both
     * @throws cException In case of a sql query that crashes
     * @return array An array with Article objects
     */
    public function getOnlineArticlesInCategoryRange(array $aCategoryIds, $sOrderBy = 'creationdate', $sOrder = 'ASC', $bArticleIdAsKey = false)
    {
        try {
            return $this->getArticlesInCategoryRange($aCategoryIds, $sOrderBy, $sOrder, $bArticleIdAsKey, 1);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Return array with offline article-objects of a category range.
     * @param array $aCategoryIds
     * @param string $sOrderBy
     * @param string $sOrder
     * @param boolean $bArticleIdAsKey
     * @param int $iOnlineStatus 0-offline, 1-online, 2-both
     * @throws cException In case of a sql query that crashes
     * @return array An array with Article objects
     */
    public function getOfflineArticlesInCategoryRange(array $aCategoryIds, $sOrderBy = 'creationdate', $sOrder = 'ASC', $bArticleIdAsKey = false)
    {
        try {
            return $this->getArticlesInCategoryRange($aCategoryIds, $sOrderBy, $sOrder, $bArticleIdAsKey, 0);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Return start article of a given category.
     * Remember to check for idart: if intval(idart) == 0, given idcat has no start article!
     * @param int $iCategoryId
     * @return cApiArticleLanguage Article
     */
    public function getStartArticleInCategory($iCategoryId)
    {
        $aOptions = array(
            'idcat' => (int) $iCategoryId,
            'lang' => $this->getLang(),
            'client' => $this->getClient(),
            'start' => true
        );
        $this->oArticleCollection = new ArticleCollection($aOptions);
        return $this->oArticleCollection->startArticle();
    }

    /**
     * Return non start articles of a given category.
     * Remember to check for idart: if intval(idart) == 0, given idcat has no start article!
     * @param int $iCategoryId
     * @param string $sOrderBy Valid are fields of tbl. con_art_lang
     * @param string $sOrderDirection
     * @param boolean $bArticleIdAsKey
     * @return array An array with Article objects if any were found
     */
    public function getNonStartArticlesInCategory($iCategoryId, $sOrderBy = 'created', $sOrderDirection = 'DESC', $bArticleIdAsKey = false)
    {
        if (!in_array(strtolower($sOrderDirection), array('asc', 'desc'))) {
            $sOrderDirection = 'DESC';
        }

        if ($sOrderBy != 'created') {
            switch ($sOrderBy) {
                case 'sortsequence':
                    $sOrderBy = 'artsort';
                    break;
                case 'modificationdate':
                    $sOrderBy = 'lastmodified';
                    break;
                case 'creationdate':
                    $sOrderBy = 'created';
                    break;
                case 'publisheddate':
                    $sOrderBy = 'published';
                    break;
                default:
                    $sOrderBy = 'created';
            }
        }

        $aReturn = array();
        $aOptions = array(
            'idcat' => (int) $iCategoryId,
            'lang' => $this->getLang(),
            'client' => $this->getClient(),
            'start' => false,
            'order' => $this->oDb->escape($sOrderBy),
            'direction' => $sOrderDirection
        );

        $this->oArticleCollection = new ArticleCollection($aOptions);
        while ($oArticle = $this->oArticleCollection->nextArticle()) {
            if ($bArticleIdAsKey === false) {
                $aReturn[] = $oArticle;
            } else {
                $aReturn[$oArticle->values['idart']] = $oArticle;
            }
        }
        return $aReturn;
    }

    /**
     * Return start articles of a given category range.
     * Remember to check for idart: if intval(idart) == 0, given idcat has no start article!
     * @param array $aCategoryIds
     * @param boolean $bArticleIdAsKey
     * @return array An array with Article objects if any were found
     */
    public function getStartArticlesInCategoryRange(array $aCategoryIds, $bArticleIdAsKey = false)
    {
        $aReturn = array();
        if (sizeof($aCategoryIds) > 0) {
            foreach ($aCategoryIds as $iIdcat) {
                $aOptions = array(
                    'idcat' => (int) $iIdcat,
                    'lang' => $this->getLang(),
                    'client' => $this->getClient(),
                    'start' => true
                );
                $this->oArticleCollection = new ArticleCollection($aOptions);
                if ($bArticleIdAsKey === false) {
                    $aReturn[] = $this->oArticleCollection->startArticle();
                } else {
                    $oStartArticle = $this->oArticleCollection->startArticle();
                    $aReturn[$oStartArticle->values['idart']] = $oStartArticle;
                }
            }
        }
        return $aReturn;
    }

    /**
     * @throws cBadMethodCallException Because method has not been implemented yet
     */
    public function getNonStartArticlesInCategoryRange(array $aCategoryIds, $sOrderBy = 'created', $sOrderDirection = 'DESC', $bArticleIdAsKey = false)
    {
        throw new cBadMethodCallException('Method not implemented yet!');
    }

    /**
     * @throws cBadMethodCallException Because method has not been implemented yet
     */
    public function getCategoryByArticleId($iArticleId)
    {
        throw new cBadMethodCallException('Method not implemented yet!');
    }

    // Getter/Setter

    public function setLang($iLang)
    {
        $this->iLang = (int) $iLang;
    }

    public function setClient($iClient)
    {
        $this->iClient = (int) $iClient;
    }


    public function getLang()
    {
        return (int) $this->iLang;
    }

    public function getClient()
    {
        return (int) $this->iClient;
    }

    /**
     * Builds SQL query to be used to fetch articles of one/more category/categories
     *
     * @param string $sCategorySelect Must bei either 'idcat = 1' or 'idcat IN(1,2,3)'. Not very beautiful...
     * @param string $sOrderBy
     * @param string $sOrder
     * @param int $iOnlineStatus
     * @return string
     */
    private function _buildQuery($sCategorySelect, $sOrderBy = 'creationdate', $sOrder = 'ASC', $iOnlineStatus = 2)
    {
        $sReturn = '';
        $sCategorySelect = str_replace(';', '', $sCategorySelect);
        // determine online state
        switch ($iOnlineStatus) {
            case 0:
                $sOnline = 'artlang.online = 0';
                break;
            case 1:
                $sOnline = 'artlang.online = 1';
                break;
            case 2:
            default:
                $sOnline = ' (artlang.online = 1 OR artlang.online = 0) ';
                break;
        }
        // determine order condition
        if (!in_array(strtolower($sOrder), array('asc', 'desc'))) {
            $sOrder = 'ASC';
        }
        switch ($sOrderBy) { // TODO: extend to more valid items
            case 'sortsequence':
                $sOrderCondition = 'ORDER BY artlang.artsort '.$sOrder;
                break;
            case 'creationdate':
                $sOrderCondition = 'ORDER BY artlang.created '.$sOrder;
                break;
            case 'modificationdate':
                $sOrderCondition = 'ORDER BY artlang.lastmodified '.$sOrder;
                break;
            case 'publisheddate':
                $sOrderCondition = 'ORDER BY artlang.published '.$sOrder;
                break;
            default:
                $sOrderCondition = 'ORDER BY artlang.artsort '.$sOrder;
                break;
        }
        $sSql = 'SELECT
                    artlang.idart, artlang.online
                FROM
                    '.$this->aCfg['tab']['art_lang'].' AS artlang,
                    '.$this->aCfg['tab']['art'].' AS art,
                    '.$this->aCfg['tab']['cat_art'].' AS catart
                WHERE
                    catart.'.$sCategorySelect.' AND
                    art.idclient = '.$this->getClient().' AND
                    artlang.idlang = '.$this->getLang().' AND
                    '.$sOnline.' AND
                    art.idart = catart.idart AND
                    artlang.idart = art.idart
                    '.$sOrderCondition.' ';

        return $sSql;
    }
}
?>