<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Utility to get articles of category/categories as Article objects.
 * For now, this object will use objects "Article" and "ArticleCollection".
 * TODO: Method getNonStartArticlesInCategoryRange() must be fixed so order by condition is working correctly (works now just by category, not overall)
 * TODO: Somehow avoid ArticleCollection because it is too expensive.
 * TODO: Also take article specifications into account
 * TODO: Extend _buildQuery() to accept more order conditions
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido
 * @version    0.1.0
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release 4.8.9
 *
 * {@internal
 *  created 2008-08-21
 *  modified 2009-04-09: Timo Trautmann fixed inconsistence bug in getNonStartArticlesInCategory()
 *  $Id: Contenido_Category_Articles.class.php 1004 2009-04-09 12:08:15Z timo.trautmann $:
 * }}
 *
 */

if(!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

include_once('Contenido_Category.class.php');
cInclude("classes", "class.article.php");
cInclude("classes", "class.security.php");

class Contenido_Category_Articles extends Contenido_Category_Base {
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
     * @access public
     * @param DB_Contenido $oDb
     * @param array $aCfg
     * @param int $iClient
     * @param int $iLang
     * @return void
     */
    public function __construct(DB_Contenido $oDb, array $aCfg, $iClient, $iLang) {
        parent::__construct($oDb, $aCfg);
        $this->setClient($iClient);
        $this->setLang($iLang);
    }

    /**
     * Return array with article-objects of a category.
     * @access public
     * @param int $iCategoryId
     * @param string $sOrderBy
     * @param string $sOrder
     * @param boolean $bArticleIdAsKey
     * @param int $iOnlineStatus 0-offline, 1-online, 2-both
     * @return array An array with Article objects
     * @throws Exception In case of a sql query that crashes
     */
    public function getArticlesInCategory($iCategoryId, $sOrderBy = "creationdate", $sOrder = "ASC", $bArticleIdAsKey = false, $iOnlineStatus = 2) {
		$aReturn = array();
        $sSql = $this->_buildQuery('idcat = '.Contenido_Security::toInteger($iCategoryId), $sOrderBy, $sOrder, $iOnlineStatus);
        if ($this->bDbg === true) {
            $this->oDbg->show($sSql, 'Contenido_Category_Articles::getArticlesInCategory() $sSql');
        }

        $this->oDb->query($sSql);

        $bHasErrors = $this->oDb->Errno == 0 ? $bHasErrors = false : $bHasErrors = true;
        if ($bHasErrors === false && $this->oDb->num_rows() > 0) {
            while($this->oDb->next_record()) {
                if ($bArticleIdAsKey === false) {
                    $aReturn[] = new Article($this->oDb->f('idart'), $this->getClient(), $this->getLang());
                } else {
                    $aReturn[intval($this->oDb->f('idart'))] = new Article($this->oDb->f('idart'), $this->getClient(), $this->getLang());
                }
            }
        }
        if ($bHasErrors === true) {
            throw new Exception('Error in SQL-Query! Errno: '.$this->oDb->Errno.', Error: '.$this->oDb->Error.', SQL: '.$sSql);
        }
        return $aReturn;
    }

    /**
     * Return array with article-objects of a category that are online.
     * @access public
     * @param int $iCategoryId
     * @param string $sOrderBy
     * @param string $sOrder
     * @param boolean $bArticleIdAsKey
     * @param int $iOnlineStatus 0-offline, 1-online, 2-both
     * @return array An array with Article objects
     * @throws Exception In case of a sql query that crashes
     */
    public function getOnlineArticlesInCategory($iCategoryId, $sOrderBy = "creationdate", $sOrder = "ASC", $bArticleIdAsKey = false) {
        try {
            return $this->getArticlesInCategory($iCategoryId, $sOrderBy, $sOrder, $bArticleIdAsKey, 1);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Return array with article-objects of a category that are offline.
     * @access public
     * @param int $iCategoryId
     * @param string $sOrderBy
     * @param string $sOrder
     * @param boolean $bArticleIdAsKey
     * @param int $iOnlineStatus 0-offline, 1-online, 2-both
     * @return array An array with Article objects
     * @throws Exception In case of a sql query that crashes
     */
    public function getOfflineArticlesInCategory($iCategoryId, $sOrderBy = "creationdate", $sOrder = "ASC", $bArticleIdAsKey = false) {
        try {
            return $this->getArticlesInCategory($iCategoryId, $sOrderBy, $sOrder, $bArticleIdAsKey, 0);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Return array with article-objects of a category range.
     * @access public
     * @param array $aCategoryIds
     * @param string $sOrderBy
     * @param string $sOrder
     * @param boolean $bArticleIdAsKey
     * @param int $iOnlineStatus 0-offline, 1-online, 2-both
     * @return array An array with Article objects
     * @throws Exception In case of a sql query that crashes or wrong parameters
     */
    public function getArticlesInCategoryRange(array $aCategoryIds, $sOrderBy = "creationdate", $sOrder = "ASC", $bArticleIdAsKey = false, $iOnlineStatus = 2) {
        $aReturn = array();
        $aSqlIn = array();
        if (sizeof($aCategoryIds) > 0) {
            foreach ($aCategoryIds as $iId) {
                $aSqlIn[] = Contenido_Security::toInteger($iId);
            }
        } else {
            throw new Exception('$aCategoryIds must contain at least one item!');
        }
        $sSql = $this->_buildQuery('idcat IN('.implode(', ', $aSqlIn).')', $sOrderBy, $sOrder, $iOnlineStatus);
        if ($this->bDbg === true) {
            $this->oDbg->show($sSql, 'Contenido_Category_Articles::getArticlesInCategory() $sSql');
        }
        $this->oDb->query($sSql);
        $bHasErrors = $this->oDb->Errno == 0 ? false : true;
        if ($bHasErrors === false && $this->oDb->num_rows() > 0) {
            while($this->oDb->next_record()) {
                if ($bArticleIdAsKey === false) {
                    $aReturn[] = new Article($this->oDb->f('idart'), $this->getClient(), $this->getLang());
                } else {
                    $aReturn[intval($this->oDb->f('idart'))] = new Article($this->oDb->f('idart'), $this->getClient(), $this->getLang());
                }
            }
        }
        if ($bHasErrors === true) {
            throw new Exception('Error in SQL-Query! Errno: '.$this->oDb->Errno.', Error: '.$this->oDb->Error.', SQL: '.$sSql);
        }
        return $aReturn;
    }

    /**
     * Return array with online article-objects of a category range.
     * @access public
     * @param array $aCategoryIds
     * @param string $sOrderBy
     * @param string $sOrder
     * @param boolean $bArticleIdAsKey
     * @param int $iOnlineStatus 0-offline, 1-online, 2-both
     * @return array An array with Article objects
     * @throws Exception In case of a sql query that crashes
     */
    public function getOnlineArticlesInCategoryRange(array $aCategoryIds, $sOrderBy = "creationdate", $sOrder = "ASC", $bArticleIdAsKey = false) {
        try {
            return $this->getArticlesInCategoryRange($aCategoryIds, $sOrderBy, $sOrder, $bArticleIdAsKey, 1);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Return array with offline article-objects of a category range.
     * @access public
     * @param array $aCategoryIds
     * @param string $sOrderBy
     * @param string $sOrder
     * @param boolean $bArticleIdAsKey
     * @param int $iOnlineStatus 0-offline, 1-online, 2-both
     * @return array An array with Article objects
     * @throws Exception In case of a sql query that crashes
     */
    public function getOfflineArticlesInCategoryRange(array $aCategoryIds, $sOrderBy = "creationdate", $sOrder = "ASC", $bArticleIdAsKey = false) {
        try {
            return $this->getArticlesInCategoryRange($aCategoryIds, $sOrderBy, $sOrder, $bArticleIdAsKey, 0);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Return start article of a given category.
     * Remember to check for idart: if intval(idart) == 0, given idcat has no start article!
     * @access public
     * @param int $iCategoryId
     * @return obj Article
     */
    public function getStartArticleInCategory($iCategoryId) {
        $aOptions = array(
                            'idcat' => Contenido_Security::toInteger($iCategoryId),
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
     * @access public
     * @param int $iCategoryId
     * @param string $sOrderBy Valid are fields of tbl. con_art_lang
     * @param string $sOrderDirection
     * @param boolean $bArticleIdAsKey
     * @return array An array with Article objects if any were found
     */
    public function getNonStartArticlesInCategory($iCategoryId, $sOrderBy = 'created', $sOrderDirection = 'DESC', $bArticleIdAsKey = false) {
        if (!in_array(strtolower($sOrderDirection), array('asc', 'desc'))) {
            $sOrderDirection = 'DESC';
        }
		
		$sOrderBy == 'sortsequence' ? $sOrderBy = 'artsort' : null;
		$sOrderBy == 'modificationdate' ? $sOrderBy = 'lastmodified' : null;
		$sOrderBy == 'creationdate' ? $sOrderBy = 'created': null;
				
        $aReturn = array();
        $aOptions = array(
                            'idcat' => Contenido_Security::toInteger($iCategoryId),
                            'lang' => $this->getLang(),
                            'client' => $this->getClient(),
                            'start' => false,
                            'order' => Contenido_Security::escapeDB($sOrderBy, $this->oDb),
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
     * @access public
     * @param array $aCategoryIds
     * @param boolean $bArticleIdAsKey
     * @return array An array with Article objects if any were found
     */
    public function getStartArticlesInCategoryRange(array $aCategoryIds, $bArticleIdAsKey = false) {
        $aReturn = array();
        if (sizeof($aCategoryIds) > 0) {
            foreach ($aCategoryIds as $iIdcat) {
                $aOptions = array(
                                    'idcat' => Contenido_Security::toInteger($iIdcat),
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
     * Return non start articles of a given category range.
     * Remember to check for idart: if intval(idart) == 0, given idcat has no start article!
     * Sortorder is applied to each category and not overall!
     * @access public
     * @param array $aCategoryIds
     * @param string $sOrderBy Valid are fields of tbl. con_art_lang
     * @param string $sOrderDirection
     * @param boolean $bArticleIdAsKey
     * @return array An array with Article objects if any were found
     * TODO: must be fixed so order by condition is working correctly (works now just by category, not overall)
     */
    public function getNonStartArticlesInCategoryRange(array $aCategoryIds, $sOrderBy = 'created', $sOrderDirection = 'DESC', $bArticleIdAsKey = false) {
        throw new Exception('Method not implemented yet!');
        if (!in_array(strtolower($sOrderDirection), array('asc', 'desc'))) {
            $sOrderDirection = 'DESC';
        }
        $aReturn = array();
        if (sizeof($aCategoryIds) > 0) {
            foreach ($aCategoryIds as $iIdcat) {
                $aOptions = array(
                                    'idcat' => Contenido_Security::toInteger($iIdcat),
                                    'lang' => $this->getLang(),
                                    'client' => $this->getClient(),
                                    'start' => false,
                                    'order' => Contenido_Security::escapeDB($sOrderBy, $this->oDb),
                                    'direction' => $sOrderDirection
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

    public function getCategoryByArticleId($iArticleId) {
        throw new Exception('Method not implemented yet!');
    }

    // Getter/Setter

    public function setLang($iLang) {
        $this->iLang = (int) $iLang;
    }

    public function setClient($iClient) {
        $this->iClient = (int) $iClient;
    }


    public function getLang() {
        return (int) $this->iLang;
    }

    public function getClient() {
        return (int) $this->iClient;
    }

    /**
     * Builds SQL query to be used to fetch articles of one/more category/categories
     *
     * @param string $sCategorySelect Must bei either "idcat = 1" or "idcat IN(1,2,3)". Not very beautiful...
     * @param string $sOrderBy
     * @param string $sOrder
     * @param int $iOnlineStatus
     * @return unknown
     */
    private function _buildQuery($sCategorySelect, $sOrderBy = "creationdate", $sOrder = "ASC", $iOnlineStatus = 2) {
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
            case "sortsequence":
                $sOrderCondition = 'ORDER BY artlang.artsort '.$sOrder;
                break;
            case "creationdate":
                $sOrderCondition = 'ORDER BY artlang.created '.$sOrder;
                break;
            case "modificationdate":
                $sOrderCondition = 'ORDER BY artlang.lastmodified '.$sOrder;
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