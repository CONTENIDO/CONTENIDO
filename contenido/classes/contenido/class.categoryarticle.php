<?php

/**
 * This file contains the category article collection and item class.
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

/**
 * Category article collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiCategoryArticle>
 */
class cApiCategoryArticleCollection extends ItemCollection
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param string|false $select [optional] Where clause to use for selection {@see ItemCollection::select()}
     * @throws cDbException|cInvalidArgumentException
     */
    public function __construct($select = false)
    {
        $table = cDb::getTableName('cat_art');
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
     * @param int $categoryId
     * @param int $articleId
     * @param int $status [optional]
     * @param string $author [optional]
     * @param string $created [optional]
     * @param string $lastmodified [optional]
     * @param int $createCode [optional]
     * @return cApiCategoryArticle
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create(
        $categoryId,
        $articleId,
        $status = 0,
        $author = '',
        $created = '',
        $lastmodified = '',
        $createCode = 1
    ) {
        if (empty($author)) {
            $author = cRegistry::getAuth()->getUsername();
        }
        if (empty($created)) {
            $created = date('Y-m-d H:i:s');
        }
        if (empty($lastmodified)) {
            $lastmodified = date('Y-m-d H:i:s');
        }

        $item = $this->createNewItem();

        $item->set('idcat', $categoryId);
        $item->set('idart', $articleId);
        $item->set('status', $status);
        $item->set('author', $author);
        $item->set('created', $created);
        $item->set('lastmodified', $lastmodified);
        $item->set('createcode', $createCode);

        $item->store();
        return $item;
    }

    /**
     * Returns the first category article available entry from category tree by
     * client id and language id.
     * Build a complex query trough several tables to get an ordered tree structure
     * and returns first available category article item.
     *
     * @param int $clientId
     * @param int $languageId
     * @throws cDbException
     */
    public function fetchFirstFromTreeByClientIdAndLangId($clientId, $languageId): ?cApiCategoryArticle
    {
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
            'tab_cat_tree' => cDb::getTableName('cat_tree'),
            'tab_cat_lang' => cDb::getTableName('cat_lang'),
            'tab_art_lang' => cDb::getTableName('art_lang'),
            'tab_cat' => cDb::getTableName('cat'),
            'lang' => cSecurity::toInteger($languageId),
            'client' => cSecurity::toInteger($clientId)
        ]);
        if ($this->db->nextRecord()) {
            $item = new cApiCategoryArticle();
            $item->loadByRecordSet($this->db->toArray());
            return $item;
        }
        return NULL;
    }

    /**
     * Returns a category article entry by category id and article id.
     *
     * @param int $categoryId
     * @param int $articleId
     * @throws cDbException|cException
     */
    public function fetchByCategoryIdAndArticleId($categoryId, $articleId): ?cApiCategoryArticle
    {
        $recordSet = $this->_oCache->getItemByProperties([
            'idcat' => $categoryId,
            'idart' => $articleId
        ]);
        if ($recordSet) {
            // entry in cache found, load entry from cache
            $oItem = new cApiCategoryArticle();
            $oItem->loadByRecordSet($recordSet);
            return $oItem;
        } else {
            $this->select(sprintf('`idcat` = %d AND `idart` = %d', $categoryId, $articleId));
            return (($item = $this->next()) instanceof cApiCategoryArticle) ? $item : null;
        }
    }

    /**
     * Returns a category article id by category id and article id.
     *
     * @param int $categoryId
     * @param int $articleId
     * @return ?int
     * @throws cDbException
     */
    public function getIdByCategoryIdAndArticleId($categoryId, $articleId): ?int
    {
        $where = $this->db->prepare("`idcat` = %d AND `idart` = %d", $categoryId, $articleId);
        $ids = $this->getIdsByWhereClause($where);
        return (count($ids) > 0) ? cSecurity::toInteger($ids[0]) : NULL;
    }

    /**
     * Returns all category article ids by client id.
     *
     * @param int $clientId
     * @return int[]
     * @throws cDbException
     */
    public function getAllIdsByClientId($clientId): array
    {
        $ids = [];

        $this->db->query(
            "SELECT a.idcatart FROM `%s` AS a, `%s` AS b WHERE b.idclient = %d AND b.idcat = a.idcat",
            $this->table,
            cDb::getTableName('cat'),
            $clientId
        );
        while ($this->db->nextRecord()) {
            $ids[] = cSecurity::toInteger($this->db->f('idcatart'));
        }

        return $ids;
    }

    /**
     * Returns all available category ids of entries having a specific article id
     *
     * @param int $articleId
     * @return int[]
     * @throws cDbException
     */
    public function getCategoryIdsByArticleId($articleId): array
    {
        $ids = [];

        $this->db->query(
            "SELECT `idcat` FROM `:tab_cat_art` WHERE `idart` = :idart",
            [
            'tab_cat_art' => $this->table,
            'idart' => cSecurity::toInteger($articleId)
            ]
        );

        while ($this->db->nextRecord()) {
            $ids[] = cSecurity::toInteger($this->db->f('idcat'));
        }

        return $ids;
    }

    /**
     * Checks if the passed category contains any articles in the specified language.
     *
     * @param int $categoryId Category id
     * @param int $languageId Language id
     * @throws cDbException
     */
    public function getHasArticles($categoryId, $languageId): bool
    {
        $this->db->query(
            "SELECT b.idartlang FROM `:tab_cat_art` AS a, `:art_lang` AS b
                   WHERE a.idcat = :idcat AND a.idart = b.idart AND b.idlang = :idlang",
            [
                'tab_cat_art' => $this->table,
                'art_lang' => cDb::getTableName('art_lang'),
                'idcat' => $categoryId,
                'idlang' => $languageId
            ]
        );

        return $this->db->nextRecord();
    }

    /**
     * Sets 'createcode' flag for one or more category articles.
     *
     * @param int|int[] $categoryArticleId One category article id or list of category article ids
     * @param int $createCode Create code state, either 1 or 0.
     * @return ?int Number of updated entries
     * @throws cDbException
     */
    public function setCreateCodeFlag($categoryArticleId, $createCode = 1): ?int
    {
        $createCode = $createCode == 1 ? 1 : 0;
        if (is_array($categoryArticleId)) {
            // Multiple ids
            if (!count($categoryArticleId)) {
                return null;
            }
            $categoryArticleId = array_map('intval', $categoryArticleId);
            $inSql = implode(',', $categoryArticleId);
            $sql = $this->db->prepare(
                "UPDATE `%s` SET `createcode` = %d WHERE `idcatart` IN (" . $inSql . ")",
                $this->table,
                $createCode
            );
        } else {
            // Single id
            $sql = $this->db->prepare(
                "UPDATE `%s` SET `createcode` = %d WHERE `idcatart` = %d",
                $this->table,
                $createCode,
                $categoryArticleId
            );
        }
        $this->db->query($sql);

        return $this->db->affectedRows();
    }
}

/**
 * Category article item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiCategoryArticle extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id The ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        $table = cDb::getTableName('cat_art');
        parent::__construct($table, 'idcatart');
        $this->setFilters();
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * User-defined setter for category article fields.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
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

        return parent::setField($name, $value, $safe);
    }

}
