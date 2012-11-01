<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Type management class
 *
 * @package CONTENIDO API
 * @version 0.1
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Type collection
 *
 * @package CONTENIDO API
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
     *
     * @param string $type
     * @param string $description
     * @param string $code
     * @param int $status
     * @param string $author
     * @param string $created
     * @param string $lastmodified
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

        $item = parent::createNewItem();

        $item->set('type', $type);
        $item->set('description', $description);
        $item->set('code', $code);
        $item->set('status', $status);
        $item->set('author', $author);
        $item->set('created', $created);
        $item->set('lastmodified', $lastmodified);
        $item->store();

        return $item;
    }

}

/**
 * Type item
 *
 * @package CONTENIDO API
 * @subpackage Model
 */
class cApiType extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $id Specifies the ID of item to load
     */
    public function __construct($id = false) {
        global $cfg;
        parent::__construct($cfg['tab']['type'], 'idtype');
        $this->setFilters(array(), array());
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Loads an type entry by its type.
     *
     * @param string $type e. g. CMS_HTML, CMS_TEXT, etc.
     * @return bool
     */
    public function loadByType($type) {
        $aProps = array(
            'type' => $type
        );
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
     *
     * @param string $name
     * @param mixed $value
     * @param bool $safe Flag to run defined inFilter on passed value
     */
    public function setField($name, $value, $safe = true) {
        if ('status' === $name) {
            $value = (int) $value;
        }

        parent::setField($name, $value, $safe);
    }

}
