<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Objects for Category handling.
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    0.8.1
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2008-02-15
 *   modified 2008-02-22 Contenido_Categories now implements Countable
 *  modified 2008-08-20 Removed unnecessary/redundant security fixes (typecasting is already done in getter methods) that were made during security fixing phase
 *             changed method setDebug() in Contenido_Category_Base to allow all debug modes available
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


cInclude('classes', 'Debug/DebuggerFactory.class.php');
cInclude("classes", "class.security.php");

/**
 * Implementation of a Contenido Category.
 * @version 0.9.0
 * @author Rudi Bieller
 * @copyright four for business AG <www.4fb.de>
 * {@internal
 * created 2008-02-15
 * }}
 */
class Contenido_Category extends Contenido_Category_Base {
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
     * @var obj DB_Contenido
     * @access private
     */
    private $_oDb;
    
    
    /**
     * Constructor.
     * @access public
     * @param DB_Contenido $oDb
     * @param array $aCfg
     * @return void
     * @author Rudi Bieller
     */
    public function __construct(DB_Contenido $oDb, array $aCfg) {
        parent::__construct($oDb, $aCfg);
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
     * @throws InvalidArgumentException
     * @throws Exception TODO
     * @author Rudi Bieller
     */
    public function load($iIdCat, $bIncludeLanguage = false, $iIdlang = -1) {
        if (intval($iIdCat) <= 0) {
            throw new InvalidArgumentException('Idcat to load must be greater than 0!');
        }
        $this->setIdLang($iIdlang);
        if ($bIncludeLanguage === true && $this->getIdLang() == -1) {
            throw new InvalidArgumentException('When setting $bIncludeLanguage to true you must provide an $iIdlang!');
        }
        $sSql = 'SELECT 
					idclient, parentid, parentid, preid, postid, status, author, created, lastmodified 
				FROM 
					' . $this->aCfg['tab']['cat'] . ' 
				WHERE 
					idcat = ' . Contenido_Security::toInteger($iIdCat);
	    if ($this->bDbg === true) {
	        $this->oDbg->show($sSql, 'Contenido_Category::load($iIdCat, $bIncludeLanguage = false, $iIdlang = -1): $sSql');
	    }
	    $this->oDb->query($sSql);
	    if ($this->oDb->Errno != 0) {
	        return false;
	    }
	    $this->oDb->next_record();
	    $this->setIdCat($iIdCat);
	    $this->setIdClient($this->oDb->f('idclient'));
	    $this->setIdParent($this->oDb->f('parentid'));
	    $this->setIdPre($this->oDb->f('preid'));
	    $this->setIdPost($this->oDb->f('postid'));
	    $this->setStatus($this->oDb->f('status'));
	    $this->setAuthor($this->oDb->f('author'));
	    $this->setDateCreated($this->oDb->f('created'));
	    $this->setDateModified($this->oDb->f('lastmodified'));
	    if ($bIncludeLanguage === true) {
	        try {
		        $oCategoryLanguage = new Contenido_Category_Language($this->oDb, $this->aCfg);
		        $oCategoryLanguage->setDebug($this->bDbg, $this->sDbgMode);
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
     * @access private
     * @param int $iIdcat
     * @param boolean $bIncludeLanguage If set to true, also creates Contenido_Category_Language object
     * @param int $iIdlang If $bIncludeLanguage is set to true, you must set this value, too or use setIdLang() before!
     * @return Contenido_Categories
     * @author Rudi Bieller
     */
    private function _getSubCategories($iIdcat, $bIncludeLanguage = false, $iIdlang = -1) {
        if (intval($iIdcat) <= 0) {
            throw new InvalidArgumentException('Idcat to load must be greater than 0!');
        }
        if ($bIncludeLanguage === true && $this->getIdLang() == -1) {
            throw new InvalidArgumentException('When setting $bIncludeLanguage to true you must provide an $iIdlang!');
        }
        // if we don't have a Contenido_Categories object created yet, do it now
        if (is_null($this->oSubCategories)) {
            $this->oSubCategories = new Contenido_Categories($this->oDb, $this->aCfg);
            $this->_oDb = new DB_Contenido();
        }
        $aSubCategories = $this->_getSubCategoriesAsArray($iIdcat);
        // current load depth: $this->iCurrentSubCategoriesLoadDepth
        // load depth to go to: $this->iSubCategoriesLoadDepth
        foreach ($aSubCategories as $iIdcatCurrent) {
	        try {
	            $oCategory = new Contenido_Category($this->_oDb, $this->aCfg);
		        $oCategory->setDebug($this->bDbg, $this->sDbgMode);
		        if ($this->iSubCategoriesLoadDepth > 0) {
		            $oCategory->setloadSubCategories($this->bLoadSubCategories, ($this->iSubCategoriesLoadDepth - 1));
		        }
		        $oCategory->load($iIdcatCurrent, $bIncludeLanguage, $iIdlang);
		        $this->oSubCategories->add($oCategory);
	        } catch (InvalidArgumentException $e) {
	            throw $e;
	        }
        }
    }
    
    /**
     * Return array with idcats of subcategories of given idcat
     * @access private
     * @param int $iIdcat
     * @return array
     * @author Rudi Bieller
     */
    private function _getSubCategoriesAsArray($iIdcat) {
        if (intval($iIdcat) <= 0) {
            throw new InvalidArgumentException('Idcat to load must be greater than 0!');
        }
        $aSubCats = array();
        $sSql = 'SELECT
					cattree.idcat
				FROM
					'.$this->aCfg["tab"]["cat_tree"].' AS cattree,
					'.$this->aCfg["tab"]["cat"].' AS cat,
					'.$this->aCfg["tab"]["cat_lang"].' AS catlang
				WHERE
					cattree.idcat    = cat.idcat AND
					cat.idcat    = catlang.idcat AND
					cat.idclient = ' . $this->getIdClient() . ' AND
					catlang.idlang   = ' . $this->getIdLang() . ' AND
					catlang.visible  = 1 AND 
					cat.parentid = ' . Contenido_Security::toInteger($iIdcat) .'
				ORDER BY
					cattree.idtree';
        if ($this->bDbg === true) {
	        $this->oDbg->show($sSql, 'Contenido_Category::_getSubCategoriesAsArray($iIdcat): $sSql');
	    }
	    $this->oDb->query($sSql);
	    if ($this->oDb->Errno != 0) {
	        return false;
	    }
	    while ($this->oDb->next_record()) {
	        $aSubCats[] = $this->oDb->f('idcat');
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
    public function setStatus($iStatus) {
        $iStatus = (int) $iStatus;
        $aValid = array(0, 1);
        if (!in_array($iStatus, $aValid)) {
            throw new InvalidArgumentException('Status must be either 0 or 1');
        }
        $this->iStatus = $iStatus;
    }
    public function setAuthor($sAuthor) {
        // TODO: input validation, strlen 32
        $this->sAuthor = (string) $sAuthor;
    }
    public function setDateCreated($sDateCreated) {
        // TODO: input validation, correct date/datetime format
        $this->sCreated = (string) $sDateCreated;
    }
    public function setDateModified($sDateModified) {
        // TODO: input validation, correct date/datetime format
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
 * Implementation of a "Collection" of Contenido Categories.
 * @version 0.9.0
 * @author Rudi Bieller
 * @copyright four for business AG <www.4fb.de>
 * {@internal
 * created 2008-02-15
 * modified 2008-02-25 Implemented ArrayAccess; added methods reverse(), ksort() and krsort().
 * }}
 */
class Contenido_Categories extends Contenido_Category_Base implements IteratorAggregate, ArrayAccess, Countable {
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
     */
    public function __construct(DB_Contenido $oDb, array $aCfg) {
        parent::__construct($oDb, $aCfg);
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
                $oCategory = new Contenido_Category($this->oDb, $this->aCfg);
                $oCategory->setDebug($this->bDbg, $this->sDbgMode);
                $oCategory->setloadSubCategories($this->bLoadSubCategories, $this->iSubCategoriesLoadDepth);
                $oCategory->load($iId, $bIncludeLanguage, $iIdLang);
                $this->add($oCategory);
            }
        }
    }
    
    /**
     * Add a Contenido_Category object into internal array ("Collection")
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
 * Implementation of a Contenido Category for a given Contenido Language.
 * @version 0.9.0
 * @author Rudi Bieller
 * @copyright four for business AG <www.4fb.de>
 * {@internal
 * created 2008-02-15
 * }}
 */
class Contenido_Category_Language extends Contenido_Category_Base {
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
     */
    public function __construct(DB_Contenido $oDb, array $aCfg) {
        parent::__construct($oDb, $aCfg);
    }
    
    /**
     * Load cat_lang for a given idcat.
     * @access public
     * @param int $iIdCatLang
     * @return boolean
     * @author Rudi Bieller
     */
    public function load($iIdCatLang = null) {
        if ($this->getIdCat() == -1 || $this->getIdLang() == -1) {
            throw new Exception('idcat and idlang must be set in order to load from con_cat_lang!');
        }
        if (is_null($iIdCatLang)) {
	        $sSql = 'SELECT 
						idcatlang, idtplcfg, name, visible, public, status, author, created, lastmodified, startidartlang, urlname 
					FROM 
						' . $this->aCfg["tab"]["cat_lang"] . ' 
					WHERE 
						idcat = ' . $this->getIdCat() . ' AND 
						idlang = ' . $this->getIdLang();
        } else {
	        $sSql = 'SELECT 
						idcatlang, idtplcfg, name, visible, public, status, author, created, lastmodified, startidartlang, urlname 
					FROM 
						' . $this->aCfg["tab"]["cat_lang"] . ' 
					WHERE 
						idcatlang = ' . Contenido_Security::toInteger($iIdCatLang);
        }
	    $this->oDb->query($sSql);
	    if ($this->oDb->Errno != 0) {
	        return false;
	    }
	    $this->oDb->next_record();
	    $this->setIdCatLang($this->oDb->f('idcatlang'));
	    $this->setIdCat($this->getIdCat());
	    $this->setIdLang($this->getIdLang());
	    $this->setIdTemplateConfig($this->oDb->f('idtplcfg'));
	    $this->setName($this->oDb->f('name'));
	    $this->setVisible($this->oDb->f('visible'));
	    $this->setPublic($this->oDb->f('public'));
	    $this->setStatus($this->oDb->f('status'));
	    $this->setAuthor($this->oDb->f('author'));
	    $this->setDateCreated($this->oDb->f('created'));
	    $this->setDateLastModified($this->oDb->f('lastmodified'));
	    $this->setStartIdLang($this->oDb->f('startidartlang'));
	    $this->setUrlName($this->oDb->f('urlname'));
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
    public function setVisible($iVisible) {
        $iVisible = (int) $iVisible;
        $aValid = array(0,1);
        if (!in_array($iVisible, $aValid)) {
            throw new InvalidArgumentException('Visible must be either 0 or 1');
        }
        $this->iVisible = $iVisible;
    }
    public function setPublic($iPublic) {
        $iPublic = (int) $iPublic;
        $aValid = array(0,1);
        if (!in_array($iPublic, $aValid)) {
            throw new InvalidArgumentException('Public must be either 0 or 1');
        }
        $this->iPublic = $iPublic;
    }
    public function setStatus($iStatus) {
        $iStatus = (int) $iStatus;
        $aValid = array(0,1);
        if (!in_array($iStatus, $aValid)) {
            throw new InvalidArgumentException('Status must be either 0 or 1');
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
 * {@internal
 * created 2008-02-15
 * }}
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
     */
    protected $bDbg;
    /**
     * @var string
     * @access protected
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
     */
    public function __construct(DB_Contenido $oDb, array $aCfg) {
        $this->oDb = $oDb;
        $this->aCfg = $aCfg;
        $this->bDbg = false;
        $this->oDbg = null;
    }
    
    /**
     * Set internal property for debugging on/off and choose appropriate debug object
     * @access public
     * @param boolean $bDebug
     * @param string $sDebugMode
     * @return  void
     * @author Rudi Bieller
     */
    public function setDebug($bDebug = true, $sDebugMode = 'visible') {
        if ($bDebug === false) {
            $this->bDbg = false;
            $this->oDbg = null;
            $this->sDbgMode = 'hidden';
        } else {
	        if (!in_array($sDebugMode, array('visible', 'visible_adv', 'file', 'devnull', 'hidden'))) {
	            $sDebugMode = 'devnull';
	        }
            try {
                $this->sDbgMode = $sDebugMode;
                $this->bDbg = true;
                $this->oDbg = DebuggerFactory::getDebugger($sDebugMode);
            } catch (InvalidArgumentException $e) {
                throw $e;
            }
        }
    }
}
?>