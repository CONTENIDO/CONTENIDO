<?php
/**
 * This file contains the category article collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
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
 * @method cApiCategoryArticle createNewItem
 * @method cApiCategoryArticle|bool next
 */
class cApiCategoryArticleCollection extends ItemCollection {
    /**
     * Constructor to create an instance of this class.
     *
     * @param bool $select [optional]
     *                     where clause to use for selection (see ItemCollection::select())
     *
     * @throws cDbException|cInvalidArgumentException
     */
    public function __construct($select = false) {
        $table = cRegistry::getDbTableName('cat_art');
        parent::__construct($table, 'idcatart');
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
     * @param int    $idcat
     * @param int    $idart
     * @param int    $status       [optional]
     * @param string $author       [optional]
     * @param string $created      [optional]
     * @param string $lastmodified [optional]
     * @param int    $createcode   [optional]
     *
     * @return cApiCategoryArticle
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($idcat, $idart, $status = 0, $author = "", $created = "", $lastmodified = "", $createcode = 1) {
        if (empty($author)) {
            $auth = cRegistry::getAuth();
            $author = $auth->auth['uname'];
        }
        if (empty($created)) {
            $created = date('Y-m-d H:i:s');
        }
        if (empty($lastmodified)) {
            $lastmodified = date('Y-m-d H:i:s');
        }

        $item = $this->createNewItem();

        $item->set('idcat', $idcat);
        $item->set('idart', $idart);
        $item->set('status', $status);
        $item->set('author', $author);
        $item->set('created', $created);
        $item->set('lastmodified', $lastmodified);
        $item->set('createcode', $createcode);

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
     *
     * @return cApiCategoryArticle|NULL
     *
     * @throws cDbException|cInvalidArgumentException
     */
    public function fetchFirstFromTreeByClientIdAndLangId($client, $lang) {
        $sql = "-- cApiCategoryArticleCollection->fetchFirstFromTreeByClientIdAndLangId()
            SELECT
                A.*
            FROM
                `:tab_cat_art` AS A,
                `:tab_cat_tree` AS B,
                `:tab_cat` AS C,
                `:tab_cat_lang` AS D,
                `:tab_art_lang` AS E
            WHERE
                A.idcat = B.idcat AND
                B.idcat = C.idcat AND
                D.startidartlang = E.idartlang AND
                D.idlang = :lang AND
                E.idart = A.idart AND
                E.idlang = :lang AND
                idclient = :client
            ORDER BY
                `idtree` ASC LIMIT 1";

        $this->db->query($sql, [
            'tab_cat_art' => $this->table,
            'tab_cat_tree' => cRegistry::getDbTableName('cat_tree'),
            'tab_cat_lang' => cRegistry::getDbTableName('cat_lang'),
            'tab_art_lang' => cRegistry::getDbTableName('art_lang'),
            'tab_cat' => cRegistry::getDbTableName('cat'),
            'lang' => cSecurity::toInteger($lang),
            'client' => cSecurity::toInteger($client)
        ]);
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
     *
     * @return cApiCategoryArticle|NULL
     *
     * @throws cDbException|cException
     */
    public function fetchByCategoryIdAndArticleId($idcat, $idart) {
        $aProps = [
            'idcat' => $idcat,
            'idart' => $idart
        ];
        $aRecordSet = $this->_oCache->getItemByProperties($aProps);
        if ($aRecordSet) {
            // entry in cache found, load entry from cache
            $oItem = new cApiCategoryArticle();
            $oItem->loadByRecordSet($aRecordSet);
            return $oItem;
        } else {
            $this->select(sprintf('`idcat` = %d AND `idart` = %d', $idcat, $idart));
            return $this->next();
        }
    }

    /**
     * Returns a category article id by category id and article id.
     *
     * @param int $idcat
     * @param int $idart
     *
     * @return int|NULL
     *
     * @throws cDbException
     */
    public function getIdByCategoryIdAndArticleId($idcat, $idart) {
        $where = $this->db->prepare("idcat = %d AND idart = %d", $idcat, $idart);
        $aIds = $this->getIdsByWhereClause($where);
        return (count($aIds) > 0) ? $aIds[0] : NULL;
    }

    /**
     * Returns all category article ids by client id.
     *
     * @param int $idclient
     *
     * @return array
     *
     * @throws cDbException|cInvalidArgumentException
     */
    public function getAllIdsByClientId($idclient) {
        $aIds = [];

        $catTable = cRegistry::getDbTableName('cat');
        $sql = "SELECT a.idcatart FROM `%s` AS a, `%s` AS b WHERE b.idclient = %d AND b.idcat = a.idcat";
        $this->db->query($sql, $this->table, $catTable, $idclient);
        while ($this->db->nextRecord()) {
            $aIds[] = $this->db->f('idcatart');
        }

        return $aIds;
    }

    /**
     * Returns all available category ids of entries having a specific article id
     *
     * @param int $idart
     *
     * @return array
     *
     * @throws cDbException|cInvalidArgumentException
     */
    public function getCategoryIdsByArticleId($idart) {
        $aIdCats = [];

        $sql = "SELECT `idcat` FROM `:tab_cat_art` WHERE `idart` = :idart";
        $this->db->query($sql, [
            'tab_cat_art' => $this->table,
            'idart' => cSecurity::toInteger($idart)
        ]);

        while ($this->db->nextRecord()) {
            $aIdCats[] = $this->db->f('idcat');
        }

        return $aIdCats;
    }

    /**
     * Checks, if passed category contains any articles in specified language.
     *
     * @param int $idcat
     *         Category id
     * @param int $idlang
     *         Language id
     *
     * @return bool
     *
     * @throws cDbException|cInvalidArgumentException
     */
    public function getHasArticles($idcat, $idlang) {
        $sql = "SELECT b.idartlang FROM `:tab_cat_art` AS a, `:art_lang` AS b "
            . "WHERE a.idcat = :idcat AND a.idart = b.idart AND b.idlang = :idlang";
        $this->db->query($sql, [
            'tab_cat_art' => $this->table,
            'art_lang' => cRegistry::getDbTableName('art_lang'),
            'idcat' => $idcat,
            'idlang' => $idlang
        ]);

        return $this->db->nextRecord();
    }

    /**
     * Sets 'createcode' flag for one or more category articles.
     *
     * @param int|array $idcatart
     *                              One category article id or list of category article ids
     * @param int       $createcode [optional]
     *                              Create code state, either 1 or 0.
     *
     * @return int|void
     *                              Number of updated entries
     *
     * @throws cDbException
     */
    public function setCreateCodeFlag($idcatart, $createcode = 1) {
        $createcode = ($createcode == 1) ? 1 : 0;
        if (is_array($idcatart)) {
            // Multiple ids
            if (count($idcatart) == 0) {
                return;
            }
            foreach ($idcatart as $pos => $id) {
                $idcatart[$pos] = cSecurity::toInteger($id);
            }
            $inSql = implode(', ', $idcatart);
            $sql = "UPDATE `%s` SET `createcode` = %d WHERE `idcatart` IN (" . $inSql . ")";
            $sql = $this->db->prepare($sql, $this->table, $createcode);
        } else {
            // Single id
            $sql = "UPDATE `%s` SET `createcode` = %d WHERE `idcatart` = %d";
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
class cApiCategoryArticle extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $mId [optional]
     *                   Specifies the ID of item to load
     *
     * @throws cDbException|cException
     */
    public function __construct($mId = false) {
        $table = cRegistry::getDbTableName('cat_art');
        parent::__construct($table, 'idcatart');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * User-defined setter for category article fields.
     *
     * @param string $name
     * @param mixed $value
     * @param bool $bSafe [optional]
     *         Flag to run defined inFilter on passed value
     * @return bool
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
            case 'idart':
            case 'status':
            case 'idcat':
                $value = cSecurity::toInteger($value);
                break;
            case 'createcode':
                $value = ($value == 1) ? 1 : 0;
                break;
        }

        return parent::setField($name, $value, $bSafe);
    }

}
