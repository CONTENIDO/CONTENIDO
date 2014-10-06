<?php
/**
 * This file contains the container configuration collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @version SVN Revision $Rev:$
 *
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Container configuration collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiContainerConfigurationCollection extends ItemCollection {

    /**
     * Create a new collection of items.
     *
     * @param string $select where clause to use for selection (see
     *            ItemCollection::select())
     */
    public function __construct($select = false) {
        global $cfg;
        parent::__construct($cfg['tab']['container_conf'], 'idcontainerc');
        $this->_setItemClass('cApiContainerConfiguration');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiTemplateConfigurationCollection');

        if ($select !== false) {
            $this->select($select);
        }
    }

    /**
     * Creates a container configuration item
     * @param int $idtplcfg
     * @param int $number
     * @param string $container
     * @return cApiContainerConfiguration
     */
    public function create($idtplcfg, $number, $container) {
        $item = $this->createNewItem();

        $item->set('idtplcfg', $idtplcfg);
        $item->set('number', $number);
        $item->set('container', $container);
        $item->store();

        return $item;
    }

    /**
     * Returns list of all configured container by template configuration id
     *
     * @param  int  $idtplcfg  Template configuration id
     * @return  array  Assoziative array where the key is the number and
     *                 value the container configuration.
     */
    public function getByTemplateConfiguration($idtplcfg) {
        $configuration = array();

        $this->select('idtplcfg = ' . (int) $idtplcfg, '', 'number ASC');
        while (($item = $this->next()) !== false) {
            $configuration[(int) $item->get('number')] = $item->get('container');
        }

        return $configuration;
    }
}

/**
 * Container configuration item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiContainerConfiguration extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['container_conf'], 'idcontainerc');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Userdefined setter for container config fields.
     *
     * @param string $name
     * @param mixed $value
     * @param bool $bSafe Flag to run defined inFilter on passed value
     * @todo should return return value of overloaded method
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
            case 'idtplcfg':
            case 'number':
                $value = cSecurity::toInteger($value);
                break;
        }

        parent::setField($name, $value, $bSafe);
    }

    /**
     * Adds a key value pair to passed container string and returns the modified
     * container string
     * @param string $container
     * @param string $key
     * @param string $value
     * @return string
     */
    public static function addContainerValue($container, $key, $value) {
        $container .= $key . '=' . urlencode(stripslashes($value)) . '&';
        return $container;
    }

    /**
     * Parses the container value to its variables
     * @param string $value
     * @return array
     */
    public static function parseContainerValue($value) {
        $vars = array();

        $value = preg_replace('/&$/', '', $value);
        $parts = preg_split('/&/', $value);
        foreach ($parts as $key1 => $value1) {
            $param = explode('=', $value1);
            foreach ($param as $key2 => $value2) {
                $vars[$param[0]] = urldecode($param[1]);
            }
        }

        return $vars;
    }

}
