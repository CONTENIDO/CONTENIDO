<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Category tree
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO API
 * @version    1.5
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
 * Category tree collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiCategoryTreeCollection extends ItemCollection
{
    public function __construct($select = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['cat_tree'], 'idtree');
        $this->_setJoinPartner('cApiCategoryCollection');
        $this->_setItemClass('cApiTree');
        if ($select !== false) {
            $this->select($select);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiCategoryTreeCollection($select = false)
    {
        cDeprecated("Use __construct() instead");
        $this->__construct($select);
    }
}


/**
 * Category tree item
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiCategoryTree extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['cat_tree'], 'idtree');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }
}


################################################################################
# Old version of category tree class
#
# NOTE: Class implemetation below is deprecated and the will be removed in
#       future versions of contenido.
#       Don't use it, it's still available due to downwards compatibility.


/**
 * Single category tree item
 * @deprecated  [2011-10-11] Use cApiCategoryTree instead of this class.
 */
class cApiTree extends cApiCategoryTree
{
    public function __construct($mId = false)
    {
        cDeprecated("Use class cApiCategoryTree instead");
        parent::__construct($mId);
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiTree($mId = false)
    {
        cDeprecated("Use __construct() instead");
        $this->__construct($mId);
    }
}

?>