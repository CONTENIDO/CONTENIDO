<?php
/**
 * Project:
 * CONTENIDO Content Management System.
 *
 * Description:
 * Group property management class
 *
 * cApiGroupProperty instance contains following class properties:
 * - idgroupprop   (int)
 * - group_id      (string)
 * - type          (string)
 * - name          (string)
 * - value         (string)
 * - idcatlang     (int)
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend classes
 * @version    0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2011-11-03
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class cApiGroupPropertyCollection extends ItemCollection
{
    protected $_groupId = '';

    /**
     * Constructor
     * @param  string  $groupId
     */
    public function __construct($groupId = '')
    {
        global $cfg;
        parent::__construct($cfg['tab']['group_prop'], 'idgroupprop');
        $this->_setItemClass('cApiGroupProperty');
        if ($groupId !== '') {
            $this->setGroupId($groupId);
        }
    }

    /**
     * Group id setter
     * @param  string  $groupId
     */
    public function setGroupId($groupId)
    {
        $this->_groupId = $groupId;
    }

    /**
     * Updatess a existing group property entry or creates it.
     * @param  string  $type
     * @param  string  $name
     * @param  string  $value
     * @param  int     $idcatlang
     * @return cApiGroupProperty
     */
    public function set($type, $name, $value, $idcatlang = 0)
    {
        $item = $this->fetchByGroupIdTypeName($type, $name);
        if ($item) {
            $item->set('value', $this->escape($value));
            $item->store();
        } else {
            $item = $this->create($type, $name, $value, $idcatlang);
        }

        return $item;
    }

    /**
     * Creates a group property entry.
     * @param  string  $type
     * @param  string  $name
     * @param  string  $value
     * @param  int     $idcatlang
     * @return cApiGroupProperty
     */
    public function create($type, $name, $value, $idcatlang = 0)
    {
        $item = parent::create();

        $item->set('group_id', $this->escape($this->_groupId));
        $item->set('type', $this->escape($type));
        $item->set('name', $this->escape($name));
        $item->set('value', $this->escape($value));
        $item->set('idcatlang', (int) $idcatlang);
        $item->store();

        return $item;
    }

    /**
     * Returns all group properties by groupid, type and name.
     * @param  string  $type
     * @param  string  $name
     * @return cApiGroupProperty|null
     */
    public function fetchByGroupIdTypeName($type, $name)
    {
        $this->select("group_id='" . $this->escape($this->_groupId) . "' AND type='" . $this->escape($type) . "' AND name='" . $this->escape($name) . "'");
        if ($property = $this->next()) {
            return $property;
        }
        return null;
    }

    /**
     * Returns all group properties by groupid and type.
     * @param  string  $type
     * @return cApiGroupProperty[]
     */
    public function fetchByGroupIdType($type)
    {
        $this->select("group_id='" . $this->escape($this->_groupId) . "' AND type='" . $this->escape($type) . "'");
        $props = array();
        while ($property = $this->next()) {
            $props[] = clone $property;
        }
        return $props;
    }

    /**
     * Returns all group properties by groupid.
     * @param  string  $type
     * @return cApiGroupProperty[]
     */
    public function fetchByGroupId()
    {
        $this->select("group_id='" . $this->escape($this->_groupId) . "'");
        $props = array();
        while ($property = $this->next()) {
            $props[] = clone $property;
        }
        return $props;
    }

    /**
     * Deletes group property by groupid, type and name.
     * @param  string  $type
     * @param  string  $name
     * @return bool
     */
    public function deleteByGroupIdTypeName($type, $name)
    {
        $this->select("group_id='" . $this->escape($this->_groupId) . "' AND type='" . $this->escape($type) . "' AND name='" . $this->escape($name) . "'");
        return $this->_deleteSelected();
    }

    /**
     * Deletes group properties by groupid and type.
     * @param  string  $type
     * @return bool
     */
    public function deleteByGroupIdType($type)
    {
        $this->select("group_id='" . $this->escape($this->_groupId) . "' AND type='" . $this->escape($type) . "'");
        return $this->_deleteSelected();
    }

    /**
     * Deletes all group properties by groupid.
     * @return bool
     */
    public function deleteByGroupId()
    {
        $this->select("group_id='" . $this->escape($this->_groupId) . "'");
        return $this->_deleteSelected();
    }

    /**
     * Deletes selected group properties.
     * @return bool
     */
    protected function _deleteSelected()
    {
        $result = false;
        while ($system = $this->next()) {
            $result = $this->delete($system->get('idsystemprop'));
        }
        return $result;
    }
}


/**
 * Class cApiGroupProperty
 */
class cApiGroupProperty extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['group_prop'], 'idgroupprop');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Updates a group property value.
     * @param   string  $value
     * @return  bool
     */
    public function updateValue($value)
    {
        $this->set('value', $this->escape($value));
        return $this->store();
    }
}

?>