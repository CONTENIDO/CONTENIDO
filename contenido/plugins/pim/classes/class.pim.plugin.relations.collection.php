<?php
/**
 * This file contains Plugin Manager Relations recipient class.
 *
 * @package Plugin
 * @subpackage PluginManager
 * @version SVN Revision $Rev:$
 *
 * @author Frederic Schneider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Plugin Manager Relations recipient class.
 *
 * @package     Plugin
 * @subpackage  PluginManager
 * @author Frederic Schneider
 */
class PimPluginRelationsCollection extends ItemCollection {

    /**
     * Constructor Function
     *
     * @param none
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['plugins_rel'], 'idpluginrelation');
        $this->_setItemClass('PimPluginRelations');
    }

    /**
     * Create a new plugin
     *
     * @param $idItem Is equivalent to idarea or idnavm
     * @param $idPlugin Plugin Id
     * @param $type Relation to tables *_area and *_nav_main
     */
    public function create($idItem, $idPlugin, $type) {

        // security checks
        $idItem = cSecurity::toInteger($idItem);
        $idPlugin = cSecurity::toInteger($idPlugin);
        $type = cSecurity::escapeString($type);

        // create a new entry
        $item = parent::createNewItem();
        $item->set('iditem', $idItem);
        $item->set('idplugin', $idPlugin);
        $item->set('type', $type);

        $item->store();

        return $item;
    }

}

/**
 * Single Plugin Manager Relations Item
 */
class PimPluginRelations extends Item {

    /**
     *
     * @var string Error storage
     * @access private
     */
    protected $_sError;

    /**
     * Constructor Function
     *
     * @param $id mixed Specifies the id of item to load
     */
    public function __construct($id = false) {
        global $cfg;
        parent::__construct($cfg['tab']['plugins_rel'], 'idpluginrelation');
        $this->_sError = '';
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

}
