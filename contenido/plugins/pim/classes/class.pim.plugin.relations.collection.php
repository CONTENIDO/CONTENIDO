<?php

/**
 * This file contains Plugin Manager Relations recipient class.
 *
 * @package    Plugin
 * @subpackage PluginManager
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Plugin Manager Relations recipient class.
 *
 * @package    Plugin
 * @subpackage PluginManager
 * @author     Frederic Schneider
 * @extends ItemCollection<PimPluginRelations>
 */
class PimPluginRelationsCollection extends ItemCollection
{
    /**
     * Constructor Function
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('plugins_rel'), 'idpluginrelation');
        $this->_setItemClass('PimPluginRelations');
    }

    /**
     * Create a new plugin
     *
     * @param $itemId int Is equivalent to idarea or idnavm
     * @param $pluginId int Plugin Id
     * @param $type string Relation to tables *_area and *_nav_main
     * @return PimPluginRelations
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($itemId, $pluginId, $type)
    {
        // create a new entry
        $item = $this->createNewItem();
        $item->set('iditem', $itemId);
        $item->set('idplugin', $pluginId);
        $item->set('type', $type);

        $item->store();

        return $item;
    }

}

/**
 * Single Plugin Manager Relations Item
 */
class PimPluginRelations extends Item
{

    /**
     * @var string Error storage
     */
    protected $_sError;

    /**
     * Constructor Function
     *
     * @param mixed $id Specifies the id of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('plugins_rel'), 'idpluginrelation');
        $this->_sError = '';
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * User-defined setter for pim relations fields.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'idplugin':
            case 'iditem':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $safe);
    }

}
