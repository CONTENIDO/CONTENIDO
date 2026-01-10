<?php

/**
 * This file contains the container configuration collection and item class.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Container configuration collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiContainerConfiguration>
 */
class cApiContainerConfigurationCollection extends ItemCollection
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param string|false $select [optional] Where clause to use for selection {@see ItemCollection::select()}
     * @throws cDbException|cInvalidArgumentException
     */
    public function __construct($select = false)
    {
        $table = cDb::getTableName('container_conf');
        parent::__construct($table, 'idcontainerc');
        $this->_setItemClass('cApiContainerConfiguration');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiTemplateConfigurationCollection');

        if ($select !== false) {
            $this->select($select);
        }
    }

    /**
     * Creates a container configuration item
     *
     * @param int $idtplcfg
     * @param int $number
     * @param string $container
     * @return cApiContainerConfiguration
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($idtplcfg, $number, $container)
    {
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
     * @param int $idtplcfg Template configuration id
     * @return array Associative array where the key is the number and value the container configuration.
     * @throws cDbException|cException
     */
    public function getByTemplateConfiguration($idtplcfg): array
    {
        $configuration = [];
        $this->select('idtplcfg = ' . cSecurity::toInteger($idtplcfg), '', 'number ASC');
        while ($item = $this->next()) {
            $configuration[cSecurity::toInteger($item->get('number'))] = $item->get('container');
        }
        return $configuration;
    }
}

/**
 * Container configuration item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiContainerConfiguration extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id The ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        $table = cDb::getTableName('container_conf');
        parent::__construct($table, 'idcontainerc');
        $this->setFilters();
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * User-defined setter for container config fields.
     *
     * @inheritdoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'idtplcfg':
            case 'number':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $safe);
    }

    /**
     * Adds a key value pair to passed container string and returns the modified container string
     *
     * @param string $container
     * @param string $key
     * @param string $value
     */
    public static function addContainerValue($container, $key, $value): string
    {
        return $container . $key . '=' . urlencode(stripslashes($value)) . '&';
    }

    /**
     * Parses the container value to its variables
     *
     * @param string $value
     */
    public static function parseContainerValue($value): array
    {
        $value = preg_replace('/(&\$)/', '', $value);
        parse_str($value, $vars);
        return is_array($vars) ? $vars : [];
    }

}
