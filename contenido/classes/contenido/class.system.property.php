<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * System property management class.
 *
 * cApiSystemProperty instance contains following class properties:
 * - idsystemprop  (int)
 * - type          (string)
 * - name          (string)
 * - value         (string)
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


class cApiSystemPropertyCollection extends ItemCollection
{
    /**
     * Constructor
     */
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg['tab']['system_prop'], 'idsystemprop');
        $this->_setItemClass('cApiSystemProperty');
    }

    /**
     * Creates a system property entry.
     * @param  string  $type
     * @param  string  $name
     * @param  string  $value
     * @return cApiSystemProperty
     */
    public function create($type, $name, $value)
    {
        $item = parent::create();

        $item->set('type', $this->escape($type));
        $item->set('name', $this->escape($name));
        $item->set('value', $this->escape($value));
        $item->store();

        return $item;
    }

    /**
     * Returns all system properties by type and name.
     * @param  string  $type
     * @param  string  $name
     * @return cApiSystemProperty[]
     */
    public function selectByTypeName($type, $name) {
        $this->select("type'=" . $this->escape($type) . "' AND name'=" . $this->escape($name) . "'");
        $props = array();
        while ($property = $his->next()) {
            $props[] = clone $property;
        }
        return $props;
    }

    /**
     * Returns all system properties by type.
     * @param  string  $type
     * @return cApiSystemProperty[]
     */
    public function selectByType($type) {
        $this->select("type'=" . $this->escape($type) . "'");
        $props = array();
        while ($property = $his->next()) {
            $props[] = clone $property;
        }
        return $props;
    }

    /**
     * Deletes all system properties by type and name.
     * @param  string  $type
     * @param  string  $name
     * @return cApiSystemProperty[]
     */
    public function deleteByTypeName($type, $name)
    {
        $this->select("type'=" . $this->escape($type) . "' AND name'=" . $this->escape($name) . "'");
        while ($system = $this->next()) {
            $this->delete($system->get('idsystemprop'));
        }
    }
}


/**
 * Class cApiSystemProperty
 */
class cApiSystemProperty extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['system_prop'], 'idsystemprop');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Updates a system property value.
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