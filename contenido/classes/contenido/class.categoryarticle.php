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
 *   modified 2011-03-15, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/**
 * Category article collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiCategoryArticleCollection extends ItemCollection
{
    public function __construct($select = false)
    {
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
    public function cApiCategoryArticleCollection($select = false)
    {
        cDeprecated("Use __construct() instead");
        $this->__construct($select);
    }

    /**
     * Returns a category article entry by category id and article id.
     * @param int $idcat
     * @param int $idart
     * @return cApiCategoryArticle|null
     */
    public function fetchByCategoryIdAndArticleId($idcat, $idart) {
        $this->select('idcat=' . (int) $idcat . ' AND idart=' . (int) $idart);
        return $this->next();
    }

}


/**
 * Category article item
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiCategoryArticle extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['cat_art'], 'idcatart');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiCategoryArticle($mId = false)
    {
        cDeprecated("Use __construct() instead");
        $this->__construct($mId);
    }
}

?>