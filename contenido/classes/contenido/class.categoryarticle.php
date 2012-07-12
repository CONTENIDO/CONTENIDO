<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Category access class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO API
 * @version    1.4
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2005-08-30
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Category article collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiCategoryArticleCollection extends ItemCollection {

    public function __construct($select = false) {
        global $cfg;
        parent::__construct($cfg['tab']['cat_art'], 'idcatart');
        $this->_setItemClass('cApiCategoryArticle');
        $this->_setJoinPartner('cApiCategoryCollection');
        $this->_setJoinPartner('cApiArticleCollection');
        if ($select !== false) {
            $this->select($select);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiCategoryArticleCollection($select = false) {
        cDeprecated("Use __construct() instead");
        $this->__construct($select);
    }

    /**
     * Creates an article item entry
     *
     * @param   int     $idcat
     * @param   int     $idart
     * @param   int     $status
     * @param   string  $author
     * @param   string  $created
     * @param   string  $lastmodified
     * @param   int     $createcode
     * @param   int     $is_start  NOTE: Is deprecated but still available due to downwards compatibility.
     * @return  cApiCategoryArticle
     */
    public function create($idcat, $idart, $status = 0, $author = "", $created = "", $lastmodified = "", $createcode = 1, $is_start = 0) {
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
        $item->set('is_start', ($is_start == 0) ? 0 : 1);
        $item->set('status', (int) $status);
        $item->set('author', $this->escape($author));
        $item->set('created', $this->escape($created));
        $item->set('lastmodified', $this->escape($lastmodified));
        $item->set('createcode', ($createcode == 1) ? 1 : 0);

        $item->store();
        return $item;
    }

    /**
     * Returns the first category article available entry from category tree by client id and language id.
     * Build a complex query trough several tables to get a ordered tree structure
     * and returns first available category article item.
     *
     * @param int $client
     * @param int $lang
     * @return cApiCategoryArticle|null
     */
    public function fetchFirstFromTreeByClientIdAndLangId($client, $lang) {
        global $cfg;

        $sql = "SELECT A.* FROM `:cat_art` AS A, `:cat_tree` AS B, `:cat` AS C, `:cat_lang` AS D, `:art_lang` AS E "
                . "WHERE A.idcat = B.idcat AND B.idcat = C.idcat AND D.startidartlang = E.idartlang AND D.idlang = :lang AND E.idart = A.idart AND E.idlang = :lang AND idclient = :client "
                . "ORDER BY idtree ASC LIMIT 1";

        $params = array(
            'cat_art' => $this->table,
            'cat_tree' => $cfg['tab']['cat_tree'],
            'cat' => $cfg['tab']['cat'],
            'cat_lang' => $cfg['tab']['cat_lang'],
            'art_lang' => $cfg['tab']['art_lang'],
            'lang' => (int) $lang,
            'client' => (int) $client,
        );

        $sql = $this->db->prepare($sql, $params);
        $this->db->query($sql);
        if ($this->db->next_record()) {
            $oItem = new cApiCategoryArticle();
            $oItem->loadByRecordSet($this->db->toArray());
            return $oItem;
        }
        return null;
    }

    /**
     * Returns a category article entry by category id and article id.
     * @param int $idcat
     * @param int $idart
     * @return cApiCategoryArticle|null
     */
    public function fetchByCategoryIdAndArticleId($idcat, $idart) {
        $aProps = array('idcat' => $idcat, 'idart' => $idart);
        $aRecordSet = $this->_oCache->getItemByProperties($aProps);
        if ($aRecordSet) {
            // entry in cache found, load entry from cache
            $oItem = new cApiCategoryArticle();
            $oItem->loadByRecordSet($aRecordSet);
            return $oItem;
        } else {
            $this->select('idcat=' . (int) $idcat . ' AND idart=' . (int) $idart);
            return $this->next();
        }
    }

    /**
     * Returns all available category ids of entries having a secific article id
     * @param   int  $idart
     * @return  array
     */
    public function getCategoryIdsByArticleId($idart) {
        $aIdCats = array();

        $sql = "SELECT idcat FROM `:cat_art` WHERE idart=:idart";
        $sql = $this->db->prepare($sql, array('cat_art' => $this->table, 'idart' => (int) $idart));
        $this->db->query($sql);

        while ($this->db->next_record()) {
            $aIdCats[] = $this->db->f('idcat');
        }

        return $aIdCats;
    }

}

/**
 * Category article item
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiCategoryArticle extends Item {

    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['cat_art'], 'idcatart');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiCategoryArticle($mId = false) {
        cDeprecated("Use __construct() instead");
        $this->__construct($mId);
    }

}

?>