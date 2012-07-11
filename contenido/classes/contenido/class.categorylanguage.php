<?php
/**
 * Project:
 * Category access class
 *
 * Description:
 * Layout class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO API
 * @version    1.3.2
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2005-11-08
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Category language collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiCategoryLanguageCollection extends ItemCollection {

    /**
     * Constructor function.
     *
     * @param  string  $select  Select statement (see ItemCollection::select())
     */
    public function __construct($select = false) {
        global $cfg;
        parent::__construct($cfg['tab']['cat_lang'], 'idcatlang');
        $this->_setItemClass('cApiCategoryLanguage');
        $this->_setJoinPartner('cApiCategoryCollection');
        if ($select !== false) {
            $this->select($select);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiCategoryLanguageCollection($select = false) {
        cDeprecated("Use __construct() instead");
        $this->__construct($select);
    }

    /**
     * Creates a category language entry.
     *
     * @param  int  $idcat
     * @param  int  $idlang
     * @param  string  $name
     * @param  string  $urlname
     * @param  string  $urlpath
     * @param  int  $idtplcfg
     * @param  int  $visible
     * @param  int  $public
     * @param  int  $status
     * @param  string  $author
     * @param  int  $startidartlang
     * @param  string  $created
     * @param  string  $lastmodified
     * @return cApiCategoryLanguage
     */
    public function create($idcat, $idlang, $name, $urlname, $urlpath = '', $idtplcfg = 0, $visible = 0, $public = 0, $status = 0, $author = '', $startidartlang = 0, $created = '', $lastmodified = '') {
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

        $oItem = parent::createNewItem();

        $oItem->set('idcat', $idcat);
        $oItem->set('idlang', $idlang);
        $oItem->set('name', $name);
        $oItem->set('urlname', $urlname);
        $oItem->set('urlpath', $urlpath);
        $oItem->set('idtplcfg', $idtplcfg);
        $oItem->set('visible', $visible);
        $oItem->set('public', $public);
        $oItem->set('status', $status);
        $oItem->set('author', $author);
        $oItem->set('created', $created);
        $oItem->set('lastmodified', $lastmodified);
        $oItem->store();

        return $oItem;
    }

    /**
     * Returns startarticle  id of articlelanguage by category id and language id
     * @param  int  $idcat
     * @param  int  $idlang
     * @return  int
     */
    public function getStartIdartlangByIdcatAndIdlang($idcat, $idlang) {
        $sql = "SELECT startidartlang FROM `" . $this->table . "` WHERE idcat = " . (int) $idcat . " AND idlang = " . (int) $idlang . " AND startidartlang != 0";
        $this->db->query($sql);
        return ($this->db->next_record()) ? $this->db->f('startidartlang') : 0;
    }

}

/**
 * Category language item
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiCategoryLanguage extends Item {

    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['cat_lang'], 'idcatlang');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiCategoryLanguage($mId = false) {
        cDeprecated("Use __construct() instead");
        $this->__construct($mId);
    }

    /**
     * Load data by category id and language id
     *
     * @param  int  $idcat   Category id
     * @param  int  $idlang  Language id
     * @return  bool  true on success, otherwhise false
     */
    public function loadByCategoryIdAndLanguageId($idcat, $idlang) {
        $aProps = array('idcat' => $idcat, 'idlang' => $idlang);
        $aRecordSet = $this->_oCache->getItemByProperties($aProps);
        if ($aRecordSet) {
            // entry in cache found, load entry from cache
            $this->loadByRecordSet($aRecordSet);
            return true;
        } else {
            $where = $this->db->prepare('idcat = %d AND idlang = %d', $idcat, $idlang);
            return $this->_loadByWhereClause($where);
        }
    }

    /**
     * Userdefined setter for article language fields.
     * @param  string  $name
     * @param  mixed   $value
     * @param  bool    $safe   Flag to run defined inFilter on passed value
     */
    public function setField($name, $value, $safe = true) {
        switch ($name) {
            case 'name':
                $this->setField('urlname', htmlspecialchars($value, ENT_QUOTES), $safe);
                break;
            case 'urlname':
                $value = htmlspecialchars(cApiStrCleanURLCharacters($value), ENT_QUOTES);
                break;
            case 'visible':
            case 'public':
                $value = ($value == 1) ? 1 : 0;
                break;
            case 'idcat':
            case 'idlang':
            case 'idtplcfg':
            case 'status':
                $value = (int) $value;
                break;
        }

        if (is_string($value)) {
            $value = $this->escape($value);
        }

        parent::setField($name, $value, $safe);
    }

    /**
     * Assigns the passed template to the category language item.
     *
     * @param int $idtpl
     * @return cApiTemplateConfigurationCollection
     */
    public function assignTemplate($idtpl) {
        $templateConfigurationColl = new cApiTemplateConfigurationCollection();

        if ($this->get('idtplcfg') != 0) {
            // Remove old template first
            $templateConfigurationColl->delete($this->get('idtplcfg'));
        }

        $templateConfiguration = $templateConfigurationColl->create($idtpl);

        $this->set('idtplcfg', $templateConfiguration->get('idtplcfg'));
        $this->store();

        return $templateConfiguration;
    }

    /**
     * Returns id of template where this item is configured
     *
     * @return int
     */
    public function getTemplate() {
        $templateConfiguration = new cApiTemplateConfiguration($this->get('idtplcfg'));
        return $templateConfiguration->get('idtpl');
    }

    /**
     * Checks if category language item has a start article
     *
     * @return bool
     */
    public function hasStartArticle() {
        cInclude('includes', 'functions.str.php');
        return strHasStartArticle($this->get('idcat'), $this->get('idlang'));
    }

    /**
     * Updates lastmodified field and calls parents store method
     *
     * @return  bool
     */
    public function store() {
        $this->set('lastmodified', date('Y-m-d H:i:s'));
        return parent::store();
    }

}

################################################################################
# Old versions of category language item collection and category language item classes
#
# NOTE: Class implemetations below are deprecated and the will be removed in
#       future versions of contenido.
#       Don't use them, they are still available due to downwards compatibility.

/**
 * Category language collection
 * @deprecated  [2011-11-15] Use cApiCategoryLanguageCollection instead of this class.
 */
class CategoryLanguageCollection extends cApiCategoryLanguageCollection {

    public function __construct() {
        cDeprecated("Use class cApiCategoryLanguageCollection instead");
        parent::__construct();
    }

    public function CategoryLanguageCollection() {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }

}

/**
 * Single category language item
 * @deprecated  [2011-11-15] Use  instead of this class.
 */
class CategoryLanguageItem extends cApiCategoryLanguage {

    public function __construct($mId = false) {
        cDeprecated("Use class cApiCategoryLanguage instead");
        parent::__construct($mId);
    }

    public function CategoryLanguageItem($mId = false) {
        cDeprecated("Use __construct() instead");
        $this->__construct($mId);
    }

}

?>