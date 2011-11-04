<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * User property management class.
 *
 * cApiUserProperty instance contains following class properties:
 * - iduserprop    (int)
 * - user_id       (string)
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


class cApiUserPropertyCollection extends ItemCollection
{
    protected $_userId = '';

    /**
     * Constructor
     * @param  string  $userId
     */
    public function __construct($userId = '')
    {
        global $cfg;
        parent::__construct($cfg['tab']['user_prop'], 'iduserprop');
        $this->_setItemClass('cApiUserProperty');
        if ($userId !== '') {
            $this->setUserId($userId);
        }
    }

    /**
     * User id setter
     * @param  string  $userId
     */
    public function setUserId($userId)
    {
        $this->_userId = $userId;
    }

    /**
     * Creates a user property entry.
     * @param  string  $type
     * @param  string  $name
     * @param  string  $value
     * @param  int     $idcatlang
     * @return cApiUserProperty
     */
    public function create($type, $name, $value, $idcatlang = 0)
    {
        $item = parent::create();

        $item->set('user_id', $this->escape($this->_userId));
        $item->set('type', $this->escape($type));
        $item->set('name', $this->escape($name));
        $item->set('value', $this->escape($value));
        $item->set('idcatlang', (int) $idcatlang);
        $item->store();

        return $item;
    }

    /**
     * Returns all user properties by userid, type and name.
     * @param  string  $type
     * @param  string  $name
     * @return cApiUserProperty[]
     */
    public function selectByUserIdTypeName($type, $name) {
        $this->select("user_id='" . $this->escape($this->_userId) . "' AND type'=" . $this->escape($type) . "' AND name'=" . $this->escape($name) . "'");
        $props = array();
        while ($property = $his->next()) {
            $props[] = clone $property;
        }
        return $props;
    }

    /**
     * Returns all user properties by userid and type.
     * @param  string  $type
     * @return cApiUserProperty[]
     */
    public function selectByUserIdType($type) {
        $this->select("user_id='" . $this->escape($this->_userId) . "' AND type'=" . $this->escape($type) . "'");
        $props = array();
        while ($property = $his->next()) {
            $props[] = clone $property;
        }
        return $props;
    }

    /**
     * Deletes all user properties by userid, type and name.
     * @param  string  $type
     * @param  string  $name
     * @return cApiUserProperty[]
     */
    public function deleteByUserIdTypeName($type, $name)
    {
        $this->select("user_id='" . $this->escape($this->_userId) . "' AND type'=" . $this->escape($type) . "' AND name'=" . $this->escape($name) . "'");
        while ($user = $this->next()) {
            $this->delete($user->get('iduserprop'));
        }
    }
}


/**
 * Class cApiUserProperty
 */
class cApiUserProperty extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['user_prop'], 'iduserprop');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Updates a user property value.
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