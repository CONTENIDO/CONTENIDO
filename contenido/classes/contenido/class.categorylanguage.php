<?php
/**
 * This file contains the category language collection and item class.
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
 * Category language collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiCategoryLanguageCollection extends ItemCollection {

    /**
     * Create a new collection of items.
     *
     * @param string $select where clause to use for selection (see
     *            ItemCollection::select())
     */
    public function __construct($select = false) {
        global $cfg;
        parent::__construct($cfg['tab']['cat_lang'], 'idcatlang');
        $this->_setItemClass('cApiCategoryLanguage');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiCategoryCollection');
        $this->_setJoinPartner('cApiLanguageCollection');
        $this->_setJoinPartner('cApiTemplateConfigurationCollection');

        if ($select !== false) {
            $this->select($select);
        }
    }

    /**
     * Creates a category language entry.
     *
     * @param int $idcat
     * @param int $idlang
     * @param string $name
     * @param string $urlname
     * @param string $urlpath
     * @param int $idtplcfg
     * @param int $visible
     * @param int $public
     * @param int $status
     * @param string $author
     * @param int $startidartlang
     * @param string $created
     * @param string $lastmodified
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
     * Returns startidartlang of articlelanguage by category id and language id
     *
     * @param int $idcat
     * @param int $idlang
     * @return int
     */
    public function getStartIdartlangByIdcatAndIdlang($idcat, $idlang) {
        $sql = "SELECT startidartlang FROM `" . $this->table . "` WHERE idcat = " . (int) $idcat . " AND idlang = " . (int) $idlang . " AND startidartlang != 0";
        $this->db->query($sql);
        return ($this->db->nextRecord()) ? $this->db->f('startidartlang') : 0;
    }

    /**
     * Returns article id of articlelanguages startarticle by category id and
     * language id
     *
     * @param int $idcat
     * @param int $idlang
     * @return int
     */
    public function getStartIdartByIdcatAndIdlang($idcat, $idlang) {
        global $cfg;
        $sql = "SELECT al.idart FROM `" . $cfg['tab']['art_lang'] . "` AS al, `" . $this->table . "` " . "AS cl WHERE cl.idcat = " . (int) $idcat . " AND cl.startidartlang != 0 AND " . "cl.idlang = " . (int) $idlang . " AND cl.idlang = al.idlang AND cl.startidartlang = al.idartlang";
        $this->db->query($sql);
        return ($this->db->nextRecord()) ? $this->db->f('idart') : 0;
    }

    /**
     * Checks if passed idartlang is a start article.
     *
     * @param int $idartlang
     * @param int $idcat Check category id additionally
     * @param int $idlang Check language id additionally
     * @return bool
     */
    public function isStartArticle($idartlang, $idcat = NULL, $idlang = NULL) {
        $where = 'startidartlang = ' . (int) $idartlang;
        if (is_numeric($idcat)) {
            $where .= ' AND idcat = ' . $idcat;
        }
        if (is_numeric($idlang)) {
            $where .= ' AND idlang = ' . $idlang;
        }
        $where .= ' AND startidartlang != 0';

        $sql = "SELECT startidartlang FROM `" . $this->table . "` WHERE " . $where;
        $this->db->query($sql);
        return ($this->db->nextRecord() && $this->db->f('startidartlang') != 0);
    }
}

/**
 * Category language item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiCategoryLanguage extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['cat_lang'], 'idcatlang');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Load data by category id and language id
     *
     * @param int $idcat Category id
     * @param int $idlang Language id
     * @return bool true on success, otherwhise false
     */
    public function loadByCategoryIdAndLanguageId($idcat, $idlang) {
        $aProps = array(
            'idcat' => $idcat,
            'idlang' => $idlang
        );
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
     *
     * @param string $name
     * @param mixed $value
     * @param bool $safe Flag to run defined inFilter on passed value
     * @todo should return return value of overloaded method
     */
    public function setField($name, $value, $safe = true) {
        switch ($name) {
            case 'name':
                $this->setField('urlname', conHtmlSpecialChars($value, ENT_QUOTES), $safe);
                break;
            case 'urlname':
                $value = conHtmlSpecialChars(cApiStrCleanURLCharacters($value), ENT_QUOTES);
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

        parent::setField($name, $value, $safe);
    }

    /**
     * Assigns the passed template to the category language item.
     *
     * @param int $idtpl
     * @return cApiTemplateConfiguration
     */
    public function assignTemplate($idtpl) {
        $oTplConfColl = new cApiTemplateConfigurationCollection();

        if ($this->get('idtplcfg') != 0) {
            // Remove old template first
            $oTplConfColl->delete($this->get('idtplcfg'));
        }

        $oTplConf = $oTplConfColl->create($idtpl);

        // If there is a preconfiguration of template, copy its settings into
        // templateconfiguration
        $oTplConfColl->copyTemplatePreconfiguration($idtpl, $oTplConf->get('idtplcfg'));

        $this->set('idtplcfg', $oTplConf->get('idtplcfg'));
        $this->store();

        return $oTplConf;
    }

    /**
     * Returns id of template where this item is configured
     *
     * @return int
     */
    public function getTemplate() {
        $oTplConf = new cApiTemplateConfiguration($this->get('idtplcfg'));
        return $oTplConf->get('idtpl');
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
     * @return bool
     */
    public function store() {
        $this->set('lastmodified', date('Y-m-d H:i:s'));
        return parent::store();
    }

    /**
     * Returns the link to the current object.
     *
     * @param int $changeLangId change language id for URL (optional)
     * @return string link
     */
    public function getLink($changeLangId = 0) {
        if ($this->isLoaded() === false) {
            return '';
        }

        $options = array();
        $options['idcat'] = $this->get('idcat');
        $options['lang'] = ($changeLangId == 0) ? $this->get('idlang') : $changeLangId;
        if ($changeLangId > 0) {
            $options['changelang'] = $changeLangId;
        }

        return cUri::getInstance()->build($options);
    }
}
