<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Article specification class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 * Code is taken over from file contenido/classes/class.artspec.php in favor of
 * normalizing API.
 *
 * @package    CONTENIDO API
 * @version    0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9.0
 *
 * {@internal
 *   created  2011-09-14
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Article specification collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiArticleSpecificationCollection extends ItemCollection {

    /**
     * Constructor function
     * @param none
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['art_spec'], 'idartspec');
        $this->_setItemClass('cApiArticleSpecification');
    }

    /**
     * Returns all article specifications by client and language.
     * @param  int  $client
     * @param  int  $lang
     * @param  string  $orderby  Order statement, like "artspec ASC"
     * @return cApiGroupProperty[]
     */
    public function fetchByClientLang($client, $lang, $orderBy = '') {
        $this->select("client=" . (int) $client . " AND lang=" . (int) $lang, '', $this->escape($orderBy));
        $entries = array();
        while ($entry = $this->next()) {
            $entries[] = clone $entry;
        }
        return $entries;
    }

}

/**
 * Article specification item
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiArticleSpecification extends Item {

    /**
     * Constructor function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['art_spec'], 'idartspec');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

}

################################################################################
# Old versions of article item collection and article item classes
#
# NOTE: Class implemetations below are deprecated and the will be removed in
#       future versions of contenido.
#       Don't use them, they are still available due to downwards compatibility.

/**
 * Article specification collection
 * @deprecated  [2011-09-19] Use  instead of this class.
 */
class ArtSpecCollection extends cApiArticleSpecificationCollection {

    public function __construct() {
        cDeprecated("Use class cApiArticleSpecificationCollection instead");
        parent::__construct();
    }

    public function ArtSpecCollection() {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }

}

/**
 * Article specification Item
 * @deprecated  [2011-09-19] Use cApiArticleSpecification instead of this class.
 */
class ArtSpecItem extends cApiArticleSpecification {

    public function __construct($mId = false) {
        cDeprecated("Use class cApiArticleSpecificationCollection instead");
        parent::__construct($mId);
    }

    public function ArtSpecItem($mId = false) {
        cDeprecated("Use __construct() instead");
        $this->__construct($mId);
    }

}

?>