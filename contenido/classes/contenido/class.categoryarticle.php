<?php
/**
 * This file contains the category article collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @version SVN Revision $Rev:$
 *
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Category article collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiCategoryArticleCollection extends ItemCollection {

    /**
     * Create a new collection of items.
     *
     * @param string $select where clause to use for selection (see
     *            ItemCollection::select())
     */
    public function __construct($select = false) {
        global $cfg;
        parent::__construct($cfg['tab']['cat_art'], 'idcatart');
        $this->_setItemClass('cApiCategoryArticle');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiCategoryCollection');
        $this->_setJoinPartner('cApiArticleCollection');

        if ($select !== false) {
            $this->select($select);
        }
    }

    /**
     * Creates an article item entry
     *
     * @param int $idcat
     * @param int $idart
     * @param int $status
     * @param string $author
     * @param string $created
     * @param string $lastmodified
     * @param int $createcode
     * @return cApiCategoryArticle
     */
    public function create($idcat, $idart, $status = 0, $author = "", $created = "", $lastmodified = "", $createcode = 1) {
        global $auth;

        if (empty($author)) {
            $author = $auth->auth['uname'];
        }
        if (empty($created)) {
            $created = date('Y-m-d H:i:s');
        }
        if (empty($lastmodified)) {
            $lastmodified = date('Y-m-d H:i:s');
        }

        $item = parent::createNewItem();

        $item->set('idcat', (int) $idcat);
        $item->set('idart', (int) $idart);
        $item->set('status', (int) $status);
        $item->set('author', $this->escape($author));
        $item->set('created', $this->escape($created));
        $item->set('lastmodified', $this->escape($lastmodified));
        $item->set('createcode', ($createcode == 1)? 1 : 0);

        $item->store();
        return $item;
    }

    /**
     * Returns the first category article available entry from category tree by
     * client id and language id.
     * Build a complex query trough several tables to get a ordered tree
     * structure
     * and returns first available category article item.
     *
     * @param int $client
     * @param int $lang
     * @return cApiCategoryArticle null
     */
    public function fetchFirstFromTreeByClientIdAndLangId($client, $lang) {
        global $cfg;

        $sql = "SELECT A.* FROM `:cat_art` AS A, `:cat_tree` AS B, `:cat` AS C, `:cat_lang` AS D, `:art_lang` AS E " . "WHERE A.idcat = B.idcat AND B.idcat = C.idcat AND D.startidartlang = E.idartlang AND D.idlang = :lang AND E.idart = A.idart AND E.idlang = :lang AND idclient = :client " . "ORDER BY idtree ASC LIMIT 1";

        $params = array(
            'cat_art' => $this->table,
            'cat_tree' => $cfg['tab']['cat_tree'],
            'cat' => $cfg['tab']['cat'],
            'cat_lang' => $cfg['tab']['cat_lang'],
            'art_lang' => $cfg['tab']['art_lang'],
            'lang' => (int) $lang,
            'client' => (int) $client
        );

        $sql = $this->db->prepare($sql, $params);
        $this->db->query($sql);
        if ($this->db->nextRecord()) {
            $oItem = new cApiCategoryArticle();
            $oItem->loadByRecordSet($this->db->toArray());
            return $oItem;
        }
        return NULL;
    }

    /**
     * Returns a category article entry by category id and article id.
     *
     * @param int $idcat
     * @param int $idart
     * @return cApiCategoryArticle null
     */
    public function fetchByCategoryIdAndArticleId($idcat, $idart) {
        $aProps = array(
            'idcat' => $idcat,
            'idart' => $idart
        );
        $aRecordSet = $this->_oCache->getItemByProperties($aProps);
        if ($aRecordSet) {
            // entry in cache found, load entry from cache
            $oItem = new cApiCategoryArticle();
            $oItem->loadByRecordSet($aRecordSet);
            return $oItem;
        } else {
            $this->select('idcat = ' . (int) $idcat . ' AND idart = ' . (int) $idart);
            return $this->next();
        }
    }

    /**
     * Returns a category article id by category id and article id.
     *
     * @param int $idcat
     * @param int $idart
     * @return int null
     */
    public function getIdByCategoryIdAndArticleId($idcat, $idart) {
        $where = "idcat = %d AND idart = %d";
        $where = $this->db->prepare("idcat = %d AND idart = %d", $idcat, $idart);
        $aIds = $this->getIdsByWhereClause($where);
        return (count($aIds) > 0)? $aIds[0] : NULL;
    }

    /**
     * Returns all category article ids by client id.
     *
     * @param int $idclient
     * @return array
     */
    public function getAllIdsByClientId($idclient) {
        global $cfg;

        $aIds = array();

        $sql = "SELECT A.idcatart FROM `%s` as A, `%s` as B WHERE B.idclient = %d AND B.idcat = A.idcat";
        $this->db->query($sql, $this->table, $cfg['tab']['cat'], $idclient);
        while ($this->db->nextRecord()) {
            $aIds[] = $this->db->f('idcatart');
        }

        return $aIds;
    }

    /**
     * Returns all available category ids of entries having a secific article id
     *
     * @param int $idart
     * @return array
     */
    public function getCategoryIdsByArticleId($idart) {
        $aIdCats = array();

        $sql = "SELECT idcat FROM `:cat_art` WHERE idart=:idart";
        $sql = $this->db->prepare($sql, array(
            'cat_art' => $this->table,
            'idart' => (int) $idart
        ));
        $this->db->query($sql);

        while ($this->db->nextRecord()) {
            $aIdCats[] = $this->db->f('idcat');
        }

        return $aIdCats;
    }

    /**
     * Checks, if passed category contains any articles in specified language.
     *
     * @param int $idcat Category id
     * @param int $idlang Language id
     * @return bool
     */
    public function getHasArticles($idcat, $idlang) {
        global $cfg;

        $sql = "SELECT b.idartlang AS idartlang FROM `:cat_art` AS a, `:art_lang` AS b " . "WHERE a.idcat = :idcat AND a.idart = b.idart AND b.idlang = :idlang";
        $sql = $this->db->prepare($sql, array(
            'cat_art' => $this->table,
            'art_lang' => $cfg['tab']['art_lang'],
            'idcat' => $idcat,
            'idlang' => $idlang
        ));
        $this->db->query($sql);

        return ($this->db->nextRecord())? true : false;
    }

    /**
     * Sets 'createcode' flag for one or more category articles.
     *
     * @param int|array $idcatart One category article id or list of category
     *        article ids
     * @param int $createcode Create code state, either 1 or 0.
     * @return int Number of updated entries
     */
    public function setCreateCodeFlag($idcatart, $createcode = 1) {
        $createcode = ($createcode == 1)? 1 : 0;
        if (is_array($idcatart)) {
            // Multiple ids
            if (count($idcatart) == 0) {
                return;
            }
            foreach ($idcatart as $pos => $id) {
                $idcatart[$pos] = (int) $id;
            }
            $inSql = implode(', ', $idcatart);
            $sql = "UPDATE `%s` SET createcode = %d WHERE idcatart IN (" . $inSql . ")";
            $sql = $this->db->prepare($sql, $this->table, $createcode);
        } else {
            // Single id
            $sql = "UPDATE `%s` SET createcode = %d WHERE idcatart = %d";
            $sql = $this->db->prepare($sql, $this->table, $createcode, $idcatart);
        }
        $this->db->query($sql);
        return $this->db->affectedRows();
    }
}

/**
 * Category article item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiCategoryArticle extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['cat_art'], 'idcatart');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }
}
