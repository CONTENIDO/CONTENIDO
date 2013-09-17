<?php
/**
 * This file contains Plugin Manager class.
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
 * Plugin Manager recipient class.
 *
 * @package     Plugin
 * @subpackage  PluginManager
 * @author Frederic Schneider
 */
class PimPluginCollection extends ItemCollection {

    /**
     * Constructor Function
     *
     * @param none
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['plugins'], 'idplugin');
        $this->_setItemClass('PimPlugin');
    }

    /**
     * Create a new plugin
     *
     * @param none
     */
    public function create($name, $description, $author, $copyright, $mail, $website, $version, $foldername, $uuId, $active, $execOrder = 0) {
        global $client;

        $nextId = $this->_getNextId();

        // security checks
        $client = cSecurity::toInteger($client);
        $name = cSecurity::escapeString($name);
        $description = cSecurity::escapeString($description);
        $author = cSecurity::escapeString($author);
        $copyright = cSecurity::escapeString($copyright);
        $mail = cSecurity::escapeString($mail);
        $website = cSecurity::escapeString($website);
        $version = cSecurity::escapeString($version);
        $foldername = cSecurity::escapeString($foldername);
        $uuId = cSecurity::escapeString($uuId);
        $active = cSecurity::toInteger($active);

        // create a new entry
        $item = parent::createNewItem($nextId);
        $item->set('idclient', $client);
        $item->set('name', $name);
        $item->set('description', $description);
        $item->set('author', $author);
        $item->set('copyright', $copyright);
        $item->set('mail', $mail);
        $item->set('website', $website);
        $item->set('version', $version);
        $item->set('folder', $foldername);
        $item->set('uuid', $uuId);
        $item->set('installed', date("Y-m-d H:i:s"), false);
        $item->set('active', $active);

        // set execution order to the last of the list or to what was specified in create
        if($execOrder == 0) {
            $this->select();
            $execOrder = $this->count();
        }
        $item->set("executionorder", $execOrder);

        $item->store();

        return $item;
    }

    /**
     * Get the next id in table *_plugins
     *
     * @access protected
     * @return int
     */
    protected function _getNextId() {
        global $cfg;

        $sql = 'SELECT MAX(idplugin) AS id FROM ' . $cfg['tab']['plugins'];
        $this->db->query($sql);

        if ($this->db->nextRecord()) {

            $result = $this->db->f('id');

            // id must be over 10.000
            if ($result < 10000) {
                $result = 10000;
            }

            // add ten
            $result = $result + 10;

            // removed the last number
            $result = substr($result, 0, strlen($result) - 1);

            // last number is always zero
            return cSecurity::toInteger($result . 0);
        }
    }

}

/**
 * Single Plugin Manager Item
 */
class PimPlugin extends Item {

    /**
     *
     * @var string Error storage
     * @access private
     */
    protected $_error;

    /**
     * Constructor Function
     *
     * @param $id mixed Specifies the id of item to load
     */
    public function __construct($id = false) {
        global $cfg;
        parent::__construct($cfg['tab']['plugins'], 'idplugin');
        $this->_error = '';
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Change the execution order of this plugin and update the order for every other plugin
     *
     * @param int $newOrder New execution order for this plugin
     */
    public function updateExecOrder($newOrder) {
        $oldOrder = $this->get('executionorder'); // get the old value
        $idplugin = $this->get("idplugin");

        $this->set('executionorder', $newOrder); // update this plugin to the new value
        $this->store();

        // move the other plugins up or down
        $pluginColl = new PimPluginCollection();
        $pluginColl->select('executionorder >= "' . min($newOrder, $oldOrder) . '" AND executionorder <= "' . max($newOrder, $oldOrder) . '" AND idplugin != "' . $idplugin . '"', null, 'executionorder'); // select every plugin that needs to be updated

        while($plugin = $pluginColl->next()) {
            if($newOrder < $oldOrder) {
                $plugin->set("executionorder", $plugin->get("executionorder") + 1); // increment the execution order after we moved the plugin up
                $plugin->store();
            } else if($oldOrder < $newOrder) {
                $plugin->set("executionorder", $plugin->get("executionorder") - 1); // decrement the execution value after we moved the plugin down
                $plugin->store();
            }
        }
    }
}
