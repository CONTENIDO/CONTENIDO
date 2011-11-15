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
 * @package    CONTENIDO Backend Classes
 * @version    1.3
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2005-11-08
 *   modified 2011-03-15, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *   modified 2011-10-26, Murat Purc, added functions cApiCategoryLanguageCollection->create and cApiCategoryLanguage->store
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class cApiCategoryLanguageCollection extends ItemCollection
{
    /**
     * Constructor function.
     *
     * @param  string  $select  Select statement (see ItemCollection::select())
     */
    public function __construct($select = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['cat_lang'], 'idcatlang');
        $this->_setItemClass('cApiCategoryLanguage');
        $this->_setJoinPartner('cApiCategoryCollection');
        if ($select !== false) {
            $this->select($select);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiCategoryLanguageCollection($select = false)
    {
        cWarning(__FILE__, __LINE__, 'Deprecated method call, use __construct()');
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
     * @return cApiCategoryLanguage
     */
    public function create($idcat, $idlang, $name, $urlname, $urlpath = '', $idtplcfg = 0,
        $visible = 0, $public = 0, $status = 0, $author = '', $startidartlang = 0)
    {
        global $auth;

        if (empty($author)) {
            $author = $auth->auth['uname'];
        }
        $created = date('Y-m-d H:i:s');

        $visible = (1 == $visible) ? 1 : 0;
        $public = (1 == $public) ? 1 : 0;

        $oItem = parent::create();

        $oItem->set('idcat', (int) $idcat);
        $oItem->set('idlang', (int) $idlang);
        // name and urlname will be escaped by cApiCategoryLanguage->setField
        $oItem->set('name', $name);
        $oItem->set('urlname', $urlname);
        $oItem->set('urlpath', $this->escape($urlpath));
        $oItem->set('idtplcfg', (int) $idtplcfg);
        $oItem->set('visible', $visible);
        $oItem->set('public', $public);
        $oItem->set('status', (int) $status);
        $oItem->set('author', $this->escape($author));
        $oItem->set('created', $created);
        $oItem->set('lastmodified', $created);
        $oItem->store();

        return $oItem;
    }
}


class cApiCategoryLanguage extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['cat_lang'], 'idcatlang');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiCategoryLanguage($mId = false)
    {
        cWarning(__FILE__, __LINE__, 'Deprecated method call, use __construct()');
        $this->__construct($mId);
    }

    /**
     * User defined method, overwrites parents setField()
     *
     * @param  string  $field
     * @param  mixed   $value
     */
    public function setField($field, $value)
    {
        switch ($field) {
            case 'name':
                $this->setField('urlname', htmlspecialchars($value, ENT_QUOTES));
                break;
            case 'urlname':
                $value = htmlspecialchars(capiStrCleanURLCharacters($value), ENT_QUOTES);
                break;
        }

        parent::setField($field, $value);
    }

    /**
     * Assigns the passed template to the category language item.
     *
     * @param int $idtpl
     * @return cApiTemplateConfigurationCollection
     */
    public function assignTemplate($idtpl)
    {
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
    public function getTemplate()
    {
        $templateConfiguration = new cApiTemplateConfiguration($this->get('idtplcfg'));
        return $templateConfiguration->get('idtpl');
    }

    /**
     * Checks if category language item has a start article
     *
     * @return bool
     */
    public function hasStartArticle()
    {
        cInclude('includes', 'functions.str.php');
        return strHasStartArticle($this->get('idcat'), $this->get('idlang'));
    }

    /**
     * Updates lastmodified field and calls parents store method
     *
     * @return  bool
     */
    public function store()
    {
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
class CategoryLanguageCollection extends cApiCategoryLanguageCollection
{
    public function __construct()
    {
        cWarning(__FILE__, __LINE__, 'Deprecated class ' . __CLASS__ . ' use ' . get_parent_class($this));
        parent::__construct();
    }
    public function CategoryLanguageCollection()
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct();
    }
}


/**
 * Single category language item
 * @deprecated  [2011-11-15] Use cApiCategoryLanguage instead of this class.
 */
class CategoryLanguageItem extends cApiCategoryLanguage
{
    public function __construct($mId = false)
    {
        cWarning(__FILE__, __LINE__, 'Deprecated class ' . __CLASS__ . ' use ' . get_parent_class($this));
        parent::__construct($mId);
    }
    public function CategoryLanguageItem($mId = false)
    {
        cWarning(__FILE__, __LINE__, 'Deprecated method call, use __construct()');
        $this->__construct($mId);
    }
}

?>