<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Objects for Category handling.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    0.8.2
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/**
 * Implementation of a CONTENIDO Category.
 * @version 0.9.0
 * @author Rudi Bieller
 * @copyright four for business AG <www.4fb.de>
 * @deprecated 2012-09-29 This class is not longer supported. Use cApiCategory instead.
 */
class Contenido_Category {
    /**#@+
     * @var int
     * @access protected
     */
    protected $iIdCat;
    protected $iIdClient;
    protected $iIdParent;
    protected $iIdPre;
    protected $iIdPost;
    protected $iStatus;
    /**#@-*/

    /**#@+
     * @var string
     * @access protected
     */
    protected $sAuthor;
    protected $sCreated;
    protected $sModified;
    /**#@-*/

    /**
     * @var obj
     * @access protected
     */
    protected $oCategoryLanguage;
    /**
     * @var int
     * @access protected
     */
    protected $iIdLang;

    /**
     * @var boolean
     * @access protected
     */
    protected $bLoadSubCategories;

    /**
     * @var obj
     * @access protected
     */
    protected $oSubCategories; // if required, this holds the SubCategories of current Category

    /**
     * @var boolean
     * @access protected
     */
    protected $bHasSubCategories;

    /**
     * @var int
     * @access protected
     */
    protected $iCurrentSubCategoriesLoadDepth; // current level of SubCategories

    /**
     * @var int
     * @access protected
     */
    protected $iSubCategoriesLoadDepth; // up to which level should SubCategories be loaded


    /**
     * Constructor.
     * @access public
     * @param DB_Contenido $oDb
     * @param array $aCfg
     * @return void
     * @author Rudi Bieller
     * @deprecated 2012-09-29 This class is not longer supported. Use cApiCategory instead.
     */
    public function __construct($oDb, $aCfg) {
        cDeprecated("This class is not longer supported. Use cApiCategory instead.");

        $this->oSubCategories = null;
        $this->bHasSubCategories = false;
        $this->iSubCategoriesLoadDepth = 0;
        $this->iCurrentSubCategoriesLoadDepth = 0;
    }

    /**
     * Loads properties for a given idcat. Optionally, also properties from catlang will be loaded into object.
     *
     * @access public
     * @param int $iIdCat
     * @param boolean $bIncludeLanguage If set to true, also creates Contenido_Category_Language object
     * @param int $iIdlang If $bIncludeLanguage is set to true, you must set this value, too or use setIdLang() before!
     * @return boolean
     * @throws cInvalidArgumentException if the given idcat or idlang is invalid
     * @author Rudi Bieller
     */
    public function load($iIdCat, $bIncludeLanguage = false, $iIdlang = -1) {
        if (intval($iIdCat) <= 0) {
            throw new cInvalidArgumentException('Idcat to load must be greater than 0!');
        }

        $this->setIdLang($iIdlang);
        if ($bIncludeLanguage === true && $this->getIdLang() == -1) {
            throw new cInvalidArgumentException('When setting $bIncludeLanguage to true you must provide an $iIdlang!');
        }

        $category = new cApiCategory($iIdCat);

        if ($category->isLoaded() === false) {
            return false;
        }

        $this->setIdCat($iIdCat);
        $this->setIdClient($category->get('idclient'));
        $this->setIdParent($category->get('parentid'));
        $this->setIdPre($category->get('preid'));
        $this->setIdPost($category->get('postid'));
        $this->setStatus($category->get('status'));
        $this->setAuthor($category->get('author'));
        $this->setDateCreated($category->get('created'));
        $this->setDateModified($category->get('lastmodified'));
        if ($bIncludeLanguage === true) {
            try {
                $oCategoryLanguage = new Contenido_Category_Language(null, null);
                $oCategoryLanguage->setIdCat($this->getIdCat());
                $oCategoryLanguage->setIdLang($this->getIdLang());
                $oCategoryLanguage->load();
                $this->setCategoryLanguage($oCategoryLanguage);
            } catch (Exception $e) {
                throw $e;
            }
        }
        if ($this->bLoadSubCategories === true) {
            $this->_getSubCategories($iIdCat, $bIncludeLanguage, $iIdlang);
        }
        return true;
    }

    /**
     * Loads SubCategories depending on values for $this->bLoadSubCategories and $this->iSubCategoriesLoadDepth
     *
     * @param int $iIdcat
     * @param boolean $bIncludeLanguage If set to true, also creates Contenido_Category_Language object
     * @param int $iIdlang If $bIncludeLanguage is set to true, you must set this value, too or use setIdLang() before!
     * @throws cInvalidArgumentException if the given idcat or idlang is invalid
     * @return Contenido_Categories
     */
    private function _getSubCategories($iIdcat, $bIncludeLanguage = false, $iIdlang = -1) {
        if (intval($iIdcat) <= 0) {
            throw new cInvalidArgumentException('Idcat to load must be greater than 0!');
        }
        if ($bIncludeLanguage === true && $this->getIdLang() == -1) {
            throw new cInvalidArgumentException('When setting $bIncludeLanguage to true you must provide an $iIdlang!');
        }
        // if we don't have a Contenido_Categories object created yet, do it now
        if (is_null($this->oSubCategories)) {
            $this->oSubCategories = new Contenido_Categories(null, null);
        }

        $aSubCategories = $this->_getSubCategoriesAsArray($iIdcat);
        // current load depth: $this->iCurrentSubCategoriesLoadDepth
        // load depth to go to: $this->iSubCategoriesLoadDepth
        foreach ($aSubCategories as $iIdcatCurrent) {
            try {
                $oCategory = new Contenido_Category(null, null);
                if ($this->iSubCategoriesLoadDepth > 0) {
                    $oCategory->setloadSubCategories($this->bLoadSubCategories, ($this->iSubCategoriesLoadDepth - 1));
                }
                $oCategory->load($iIdcatCurrent, $bIncludeLanguage, $iIdlang);
                $this->oSubCategories->add($oCategory);
            } catch (cInvalidArgumentException $e) {
                throw $e;
            }
        }
    }

    /**
     * Return array with idcats of subcategories of given idcat
     *
     * @param int $iIdcat
     * @throws cInvalidArgumentException if the given idcat is invalid
     * @return array
     */
    private function _getSubCategoriesAsArray($iIdcat) {
        if (intval($iIdcat) <= 0) {
            throw new cInvalidArgumentException('Idcat to load must be greater than 0!');
        }

        $cfg = cRegistry::getConfig();

        $aSubCats = array();
        $sSql = 'SELECT
                    cattree.idcat
                FROM
                    '.$cfg["tab"]["cat_tree"].' AS cattree,
                    '.$cfg["tab"]["cat"].' AS cat,
                    '.$cfg["tab"]["cat_lang"].' AS catlang
                WHERE
                    cattree.idcat    = cat.idcat AND
                    cat.idcat    = catlang.idcat AND
                    cat.idclient = ' . $this->getIdClient() . ' AND
                    catlang.idlang   = ' . $this->getIdLang() . ' AND
                    catlang.visible  = 1 AND
                    cat.parentid = ' . cSecurity::toInteger($iIdcat) .'
                ORDER BY
                    cattree.idtree';

        $db = cRegistry::getDb();
        $db->query($sSql);
        if ($db->Errno != 0) {
            return false;
        }
        while ($db->next_record()) {
            $aSubCats[] = $db->f('idcat');
        }
        return $aSubCats;
    }

    // SETTER

    /**
     * If you need to load SubCategories, set to true and set how deep SubCategories should be loaded
     * @access public
     * @param boolean $bLoad
     * @param int $iLoadDepth
     * @return void
     * @author Rudi Bieller
     */
    public function setloadSubCategories($bLoad = false, $iLoadDepth = 0) {
        $this->bLoadSubCategories = (boolean) $bLoad;
        $this->iSubCategoriesLoadDepth = (int) $iLoadDepth;
    }

    /**
     * Set internal property with SubCategories of current Category
     * @access public
     * @param Contenido_Categories $oCategories
     * @return void
     * @author Rudi Bieller
     */
    public function setSubCategories(Contenido_Categories $oCategories) {
        $this->oSubCategories = $oCategories;
    }

    public function setCategoryLanguage(Contenido_Category_Language $oCatLang) {
        $this->oCategoryLanguage = $oCatLang;
    }

    public function setIdCat($iIdcat) {
        $this->iIdCat = (int) $iIdcat;
    }
    public function setIdClient($iIdcient) {
        $this->iIdClient = (int) $iIdcient;
    }
    public function setIdParent($iIdcatParent) {
        $this->iIdParent = (int) $iIdcatParent;
    }
    public function setIdPre($iIdcatPre) {
        $this->iIdPre = (int) $iIdcatPre;
    }
    public function setIdPost($iIdcatPost) {
        $this->iIdPost = (int) $iIdcatPost;
    }

    /**
     * @throws cInvalidArgumentException if the given status is invalid
     */
    public function setStatus($iStatus) {
        $iStatus = (int) $iStatus;
        $aValid = array(0, 1);
        if (!in_array($iStatus, $aValid)) {
            throw new cInvalidArgumentException('Status must be either 0 or 1');
        }
        $this->iStatus = $iStatus;
    }
    public function setAuthor($sAuthor) {
        $this->sAuthor = (string) $sAuthor;
    }
    public function setDateCreated($sDateCreated) {
        $this->sCreated = (string) $sDateCreated;
    }
    public function setDateModified($sDateModified) {
        $this->sModified = (string) $sDateModified;
    }
    public function setIdLang($iIdlang) {
        $this->iIdLang = (int) $iIdlang;
    }

    // GETTER

    public function getSubCategories() {
        return is_null($this->oSubCategories) ? new Contenido_Categories($this->oDb, $this->aCfg) : $this->oSubCategories;
    }

    public function getCategoryLanguage() {
        return !is_null($this->oCategoryLanguage) ? $this->oCategoryLanguage : new Contenido_Category_Language($this->oDb, $this->aCfg);
    }

    public function getIdCat() {
        return !is_null($this->iIdCat) ? (int) $this->iIdCat : -1;
    }
    public function getIdClient() {
        return !is_null($this->iIdClient) ? (int) $this->iIdClient : -1;
    }
    public function getIdParent() {
        return !is_null($this->iIdParent) ? (int) $this->iIdParent : -1;
    }
    public function getIdPre() {
        return !is_null($this->iIdPre) ? (int) $this->iIdPre : -1;
    }
    public function getIdPost() {
        return !is_null($this->iIdPost) ? (int) $this->iIdPost : -1;
    }
    public function getStatus() {
        return !is_null($this->iStatus) ? (int) $this->iStatus : -1;
    }
    public function getAuthor() {
        return !is_null($this->sAuthor) ? (string) $this->sAuthor : '';
    }
    public function getDateCreated() {
        return !is_null($this->sCreated) ? (string) $this->sCreated : '';
    }
    public function getDateModified() {
        return !is_null($this->sModified) ? (string) $this->sModified : '';
    }
    public function getIdLang() {
        return (!is_null($this->iIdLang) && $this->iIdLang > 0) ? (int) $this->iIdLang : -1;
    }
}

/**
 * Implementation of a "Collection" of CONTENIDO Categories.
 * @version 0.9.0
 * @author Rudi Bieller
 * @copyright four for business AG <www.4fb.de>
 * @deprecated 2012-09-29 This class is not longer supported. Use cCategoryHelper instead.
 */
class Contenido_Categories implements IteratorAggregate, ArrayAccess, Countable {
    /**
     * @var array
     * @access protected
     */
    protected $aContenidoCategories;
    /**
     * @var int
     * @access protected
     */
    protected $iIdLang;
    /**
     * @var boolean
     * @access protected
     */
    protected $bLoadSubCategories;

    /**
     * @var int
     * @access protected
     */
    protected $iSubCategoriesLoadDepth; // up to which level should SubCategories be loaded

    /**
     * Constructor.
     * @access public
     * @param DB_Contenido $oDb
     * @param array $aCfg
     * @return void
     * @author Rudi Bieller
     * @deprecated 2012-09-29 This class is not longer supported. Use cCategoryHelper instead.
     */
    public function __construct($oDb, $aCfg) {
        cDeprecated("This class is not longer supported. Use cCategoryHelper instead.");
        $this->aContenidoCategories = array();
        $this->bLoadSubCategories = false;
        $this->iSubCategoriesLoadDepth = 0;
    }

    /**
     * Loads a range of Category-IDs.
     * @access public
     * @param array $aCategoryIds
     * @param boolean $bIncludeLanguage
     * @param int $iIdlang If $bIncludeLanguage is set to true, you must set this value, too or use setIdLang() before!
     * @return void
     * @author Rudi Bieller
     */
    public function load(array $aCategoryIds, $bIncludeLanguage = false, $iIdlang = -1) {
        $this->setIdLang($iIdlang);
        if (sizeof($aCategoryIds) > 0) {
            // loop over passed category ids and create single Category object on each run
            foreach ($aCategoryIds as $iId) {
                $iIdLang = $this->getIdLang();
                $oCategory = new Contenido_Category(null, null);
                if ($this->iSubCategoriesLoadDepth > 0) {
                    $oCategory->setloadSubCategories($this->bLoadSubCategories, $this->iSubCategoriesLoadDepth);
                }
                $oCategory->load($iId, $bIncludeLanguage, $iIdLang);
                $this->add($oCategory);
            }
        }
    }

    /**
     * Add a CONTENIDO_Category object into internal array ("Collection")
     * @access public
     * @param Contenido_Category $oContenidoCategory
     * @param int $iOffset
     * @return void
     * @author Rudi Bieller
     */
    public function add(Contenido_Category $oContenidoCategory, $iOffset = null) {
        $this->offsetSet($iOffset, $oContenidoCategory);
    }

    /**
     * If you need to load SubCategories, set to true and set how deep SubCategories should be loaded
     * @access public
     * @param boolean $bLoad
     * @param int $iLoadDepth
     * @return void
     * @author Rudi Bieller
     */
    public function setloadSubCategories($bLoad = false, $iLoadDepth = 0) {
        $this->bLoadSubCategories = (boolean) $bLoad;
        $this->iSubCategoriesLoadDepth = (int) $iLoadDepth;
    }

    /**
     * Set internal property for Contenido-Idlang
     * @access public
     * @param int $iIdlang
     * @return void
     * @author Rudi Bieller
     */
    public function setIdLang($iIdlang) {
        $this->iIdLang = (int) $iIdlang;
    }

    /**
     * Get internal property for Contenido-Idlang
     * @access public
     * @return int
     * @author Rudi Bieller
     */
    public function getIdLang() {
        return (!is_null($this->iIdLang) && $this->iIdLang > 0) ? (int) $this->iIdLang : -1;
    }

    /**
     * Interface method for Iterator.
     * @access public
     * @return ArrayObject
     * @author Rudi Bieller
     */
    public function getIterator () {
        return new ArrayObject($this->aContenidoCategories);
    }

    /**
     * Interface method for Countable.
     * @access public
     * @return int
     * @author Rudi Bieller
     */
    public function count () {
        return sizeof($this->aContenidoCategories);
    }

    /**
     * Sort list of Contenido_Category objects by assigned key
     * @access public
     * @return void
     * @author Rudi Bieller
     */
    public function ksort() {
        ksort($this->aContenidoCategories);
    }

    /**
     * Sort list of Contenido_Category objects by assigned key in reverse order
     * @access public
     * @return void
     * @author Rudi Bieller
     */
    public function krsort() {
        krsort($this->aContenidoCategories);
    }

    /**
     * Sort list of Contenido_Category objects in reverse order
     * @access public
     * @return void
     * @author Rudi Bieller
     */
    public function reverse() {
        $this->aContenidoCategories = array_reverse($this->aContenidoCategories);
    }

    // Methods for ArrayAccess

    /**
     * Interface method for ArrayAccess.
     * @access public
     * @param int $mOffset
     * @return boolean
     * @author Rudi Bieller
     */
    public function offsetExists($mOffset) {
        return array_key_exists($this->aContenidoCategories, $mOffset);
    }
    /**
     * Interface method for ArrayAccess.
     * @access public
     * @param int $mOffset
     * @return obj
     * @author Rudi Bieller
     */
    public function offsetGet($mOffset) {
        return $this->aContenidoCategories[$mOffset];
    }
    /**
     * Interface method for ArrayAccess.
     * @access public
     * @param int $mOffset
     * @param mixed $mValue
     * @return void
     * @author Rudi Bieller
     */
    public function offsetSet($mOffset, $mValue) {
        if (is_null($mOffset)) {
            $this->aContenidoCategories[] = $mValue;
        } else {
            $this->aContenidoCategories[$mOffset] = $mValue;
        }
    }
    /**
     * Interface method for ArrayAccess.
     * @access public
     * @param int $mOffset
     * @return void
     * @author Rudi Bieller
     */
    public function offsetUnset($mOffset) {
        unset($this->aContenidoCategories[$mOffset]);
    }
}

/**
 * Implementation of a CONTENIDO Category for a given CONTENIDO Language.
 * @version 0.9.0
 * @author Rudi Bieller
 * @copyright four for business AG <www.4fb.de>
 * @deprecated 2012-09-29 This class is not longer supported. Use cApiCategortyLanguage instead.
 */
class Contenido_Category_Language {
    /**#@+
     * @var int
     * @access protected
     */
    protected $iIdCatlang;
    protected $iIdCat;
    protected $iIdLang;
    protected $iIdTplcfg;
    /**#@-*/

    /**
     * @var string
     * @access protected
     */
    protected $sName;
    protected $sAlias;

    /**#@+
     * @var int
     * @access protected
     */
    protected $iVisible;
    protected $iPublic;
    protected $iStatus;
    /**#@-*/

    /**#@+
     * @var string
     * @access protected
     */
    protected $sAuthor;
    protected $sDateCreated;
    protected $sDateModified;
    /**#@-*/

    /**
     * @var int
     * @access protected
     */
    protected $iStartIdartlang;
    /**
     * @var string
     * @access protected
     */
    protected $sUrlname;

    /**
     * Constructor.
     * @access public
     * @param DB_Contenido $oDb
     * @param array $aCfg
     * @return void
     * @author Rudi Bieller
     * @deprecated 2012-09-29 This class is not longer supported. Use cApiCategortyLanguage instead.
     */
    public function __construct($oDb, $aCfg) {
        cDeprecated("This class is not supported any longer. Use cApiCategoryLanguage instead.");
    }

    /**
     * Load cat_lang for a given idcat.
     *
     * @param int $iIdCatLang
     * @throws cException if the idcat or the idlang have not been set
     * @return boolean
     */
    public function load($iIdCatLang = null) {
        $categoryLanguage = new cApiCategoryLanguage();

        if (is_null($iIdCatLang)) {
            if ($this->getIdCat() == -1 || $this->getIdLang() == -1) {
                throw new cException('idcat and idlang must be set in order to load from con_cat_lang!');
            }

            $categoryLanguage->loadByCategoryIdAndLanguageId($this->getIdCat(), $this->getIdLang());
        } else {
            $categoryLanguage->loadByPrimaryKey(cSecurity::toInteger($iIdCatLang));
        }

        if ($categoryLanguage->isLoaded() === false) {
            return false;
        }

        $this->setIdCatLang($categoryLanguage->get('idcatlang'));
        $this->setIdCat($this->getIdCat());
        $this->setIdLang($this->getIdLang());
        $this->setIdTemplateConfig($categoryLanguage->get('idtplcfg'));
        $this->setName($categoryLanguage->get('name'));
        $this->setAlias($categoryLanguage->get('urlname'));
        $this->setVisible($categoryLanguage->get('visible'));
        $this->setPublic($categoryLanguage->get('public'));
        $this->setStatus($categoryLanguage->get('status'));
        $this->setAuthor($categoryLanguage->get('author'));
        $this->setDateCreated($categoryLanguage->get('created'));
        $this->setDateLastModified($categoryLanguage->get('lastmodified'));
        $this->setStartIdLang($categoryLanguage->get('startidartlang'));
        $this->setUrlName($categoryLanguage->get('urlname'));
        return true;
    }

    // SETTER

    public function setIdCatLang($iIdcatlang) {
        $this->iIdCatlang = (int) $iIdcatlang;
    }
    public function setIdCat($iIdcat) {
        $this->iIdCat = (int) $iIdcat;
    }
    public function setIdLang($iIdlang) {
        $this->iIdlang = (int) $iIdlang;
    }
    public function setIdTemplateConfig($iIdTplcfg) {
        $this->iIdTplcfg = (int) $iIdTplcfg;
    }
    public function setName($sName) {
        $this->sName = (string) $sName;
    }
    public function setAlias($sAlias) {
        $this->sAlias = (string) $sAlias;
    }

    /**
     * @throws cInvalidArgumentException if the given visibility is invalid
     */
    public function setVisible($iVisible) {
        $iVisible = (int) $iVisible;
        $aValid = array(0,1);
        if (!in_array($iVisible, $aValid)) {
            throw new cInvalidArgumentException('Visible must be either 0 or 1');
        }
        $this->iVisible = $iVisible;
    }

    /**
     * @throws cInvalidArgumentException if the given public flag is invalid
     */
    public function setPublic($iPublic) {
        $iPublic = (int) $iPublic;
        $aValid = array(0,1);
        if (!in_array($iPublic, $aValid)) {
            throw new cInvalidArgumentException('Public must be either 0 or 1');
        }
        $this->iPublic = $iPublic;
    }

    /**
     * @throws cInvalidArgumentException if the given status is invalid
     */
    public function setStatus($iStatus) {
        $iStatus = (int) $iStatus;
        $aValid = array(0,1);
        if (!in_array($iStatus, $aValid)) {
            throw new cInvalidArgumentException('Status must be either 0 or 1');
        }
        $this->iStatus = $iStatus;
    }
    public function setAuthor($sAuthor) {
        $this->sAuthor = (string) $sAuthor;
    }
    public function setDateCreated($sDateCreated) {
        $this->sDateCreated = (string) $sDateCreated;
    }
    public function setDateLastModified($sDateLastModified) {
        $this->sDateModified = (string) $sDateLastModified;
    }
    public function setStartIdLang($iStartIdlang) {
        $this->iStartIdartlang = (int) $iStartIdlang;
    }
    public function setUrlName($sUrlName) {
        $this->sUrlname = (string) $sUrlName;
    }

    // GETTER

    public function getIdCatLang() {
        return !is_null($this->iIdCatlang) ? (int) $this->iIdCatlang : -1;
    }
    public function getIdCat() {
        return !is_null($this->iIdCat) ? (int) $this->iIdCat : -1;
    }
    public function getIdLang() {
        return !is_null($this->iIdlang) ? (int) $this->iIdlang : -1;
    }
    public function getIdTemplateConfig() {
        return !is_null($this->iIdTplcfg) ? (int) $this->iIdTplcfg : -1;
    }
    public function getName() {
        return !is_null($this->sName) ? (string) $this->sName : '';
    }
    public function getAlias() {
        return !is_null($this->sAlias) ? (string) $this->sAlias : '';
    }
    public function getVisible() {
        return !is_null($this->iVisible) ? (int) $this->iVisible : -1;
    }
    public function getPublic() {
        return !is_null($this->iPublic) ? (int) $this->iPublic : -1;
    }
    public function getStatus() {
        return !is_null($this->iStatus) ? (int) $this->iStatus : -1;
    }
    public function getAuthor() {
        return !is_null($this->sAuthor) ? (string) $this->sAuthor : '';
    }
    public function getDateCreated() {
        return !is_null($this->sDateCreated) ? (string) $this->sDateCreated : '';
    }
    public function getDateLastModified() {
        return !is_null($this->sDateModified) ? (string) $this->sDateModified : '';
    }
    public function getStartIdLang() {
        return !is_null($this->iStartIdartlang) ? (int) $this->iStartIdartlang : -1;
    }
    public function getUrlName() {
        return !is_null($this->sUrlname) ? (string) $this->sUrlname : '';
    }
}

/**
 * Base class for Contenido_Category, Contenido_Categories, Contenido_Category_Language.
 * @version 0.9.0
 * @author Rudi Bieller
 * @copyright four for business AG <www.4fb.de>
 * @deprecated 2012-09-29 This class is not longer supported.
 */
class Contenido_Category_Base {
    /**
     * @var obj
     * @access protected
     */
    protected $oDb;
    /**
     * @var array
     * @access protected
     */
    protected $aCfg;
    /**
     * @var boolean
     * @access protected
     * @deprecated No longer needed. The backend chooses the debug mode. This is always true
     */
    protected $bDbg;
    /**
     * @var string
     * @access protected
     * @deprecated No longer needed. The backend chooses the debug mode.
     */
    protected $sDbgMode;
    /**
     * @var obj
     * @access protected
     */
    protected $oDbg;

    /**
     * Constructor.
     * @access public
     * @param DB_Contenido $oDb
     * @param array $aCfg
     * @return void
     * @author Rudi Bieller
     * @deprecated 2012-09-29 This class is not longer supported.
     */
    public function __construct($oDb, $aCfg) {
        cDeprecated("This class is not longer supported.");
        $this->oDb = $oDb;
        $this->aCfg = $aCfg;
        $this->bDbg = true;
        $this->oDbg = cDebug::getDebugger();
    }

    /**
     * Set internal property for debugging on/off and choose appropriate debug object
     * @deprecated No longer needed. The backend chooses the debug mode.
     * @access public
     * @param boolean $bDebug
     * @param string $sDebugMode
     * @return  void
     * @author Rudi Bieller
     */
    public function setDebug($bDebug = true, $sDebugMode = cDebug::DEBUGGER_VISIBLE) {
        cDeprecated("This function is no longer needed. \$oDbg gets chosen by the system settings.");

        if ($bDebug === false) {
            $this->bDbg = false;
            $this->oDbg = null;
            $this->sDbgMode = 'hidden';
        } else {
            try {
                $this->sDbgMode = $sDebugMode;
                $this->bDbg = true;
                $this->oDbg = cDebug::getDebugger($sDebugMode);
            } catch (cInvalidArgumentException $e) {
                throw $e;
            }
        }
    }
}
?>