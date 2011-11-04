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
 *   $Id: $:
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
     * @return cApiGroupProperty[]
     */
    public function selectByGroupIdTypeName($type, $name) {
        $this->select("group_id='" . $this->escape($this->_groupId) . "' AND type'=" . $this->escape($type) . "' AND name'=" . $this->escape($name) . "'");
        $props = array();
        while ($property = $his->next()) {
            $props[] = clone $property;
        }
        return $props;
    }

    /**
     * Returns all group properties by groupid and type.
     * @param  string  $type
     * @return cApiGroupProperty[]
     */
    public function selectByGroupIdType($type) {
        $this->select("group_id='" . $this->escape($this->_groupId) . "' AND type'=" . $this->escape($type) . "'");
        $props = array();
        while ($property = $his->next()) {
            $props[] = clone $property;
        }
        return $props;
    }

    /**
     * Deletes all group properties by groupid, type and name.
     * @param  string  $type
     * @param  string  $name
     * @return cApiGroupProperty[]
     */
    public function deleteByGroupIdTypeName($type, $name)
    {
        $this->select("group_id='" . $this->escape($this->_groupId) . "' AND type'=" . $this->escape($type) . "' AND name'=" . $this->escape($name) . "'");
        while ($group = $this->next()) {
            $this->delete($group->get('idgroupprop'));
        }
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