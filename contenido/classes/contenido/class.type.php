<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Type management class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO API
 * @version    0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2012-07-11
 *   $Id$
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Type collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiTypeCollection extends ItemCollection {

    /**
     * Constructor
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['type'], 'idtype');
        $this->_setItemClass('cApiType');
    }

    /**
     * Creates a type entry.
     * @param  string  $type
     * @param  string  $description
     * @param  string  $code
     * @param  int  $status
     * @param  string  $author
     * @param  string  $created
     * @param  string  $lastmodified
     * @return cApiType
     */
    public function create($type, $description, $code = '', $status = 0, $author = '', $created = '', $lastmodified = '') {
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

        $oItem->set('type', $type);
        $oItem->set('description', $description);
        $oItem->set('code', $code);
        $oItem->set('status', $status);
        $oItem->set('author', $author);
        $oItem->set('created', $created);
        $oItem->set('lastmodified', $lastmodified);
        $oItem->store();

        return $oItem;
    }

}

/**
 * Type item
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiType extends Item {

    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['type'], 'idtype');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Loads an type entry by its type.
     * @param   string  $type  e. g. CMS_HTML, CMS_TEXT, etc.
     * @return  bool
     */
    public function loadByType($type) {
        $aProps = array('type' => $type);
        $aRecordSet = $this->_oCache->getItemByProperties($aProps);
        if ($aRecordSet) {
            // entry in cache found, load entry from cache
            $this->loadByRecordSet($aRecordSet);
            return true;
        } else {
            $where = $this->db->prepare("type = '%s'", $type);
            return $this->_loadByWhereClause($where);
        }
    }

    /**
     * Userdefined setter for item fields.
     * @param  string  $name
     * @param  mixed   $value
     * @param  bool    $bSafe   Flag to run defined inFilter on passed value
     */
    public function setField($name, $value, $bSafe = true) {
        if ('status' === $name) {
            $value = (int) $value;
        }

        if (is_string($value)) {
            $value = $this->escape($value);
        }

        parent::setField($name, $value, $bSafe);
    }

}

?>