<?php

/**
 * This file contains the frontend permission collection and item class.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Frontend permission collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiFrontendPermission>
 */
class cApiFrontendPermissionCollection extends ItemCollection
{

    use cItemCollectionIdsByLanguageIdTrait;

    /**
     * @var string Language id foreign key field name
     * @since CONTENIDO 4.10.2
     */
    private $fkLanguageIdName = 'idlang';

    /**
     * instance of cApiFrontendPermission to access defined filters
     *
     * @var cApiFrontendPermission
     */
    protected $_frontendPermission;

    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        $this->_frontendPermission = new cApiFrontendPermission();

        parent::__construct(cDb::getTableName('frontendpermissions'), 'idfrontendpermission');
        $this->_setItemClass('cApiFrontendPermission');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiFrontendGroupCollection');
        $this->_setJoinPartner('cApiLanguageCollection');
    }

    /**
     * Creates a new permission entry.
     *
     * @param int $group Specifies the frontend group
     * @param string $plugin Specifies the plugin
     * @param string $action Specifies the action
     * @param string $item Specifies the item
     * @return cApiFrontendPermission|false
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($group, $plugin, $action, $item)
    {
        $perm = false;
        if (!$this->checkPerm($group, $plugin, $action, $item)) {
            $lang = cRegistry::getLanguageId();
            $perm = $this->createNewItem();
            $perm->set('idlang', $lang);
            $perm->set('idfrontendgroup', $group);
            $perm->set('plugin', $plugin);
            $perm->set('action', $action);
            $perm->set('item', $item);

            $perm->store();
        }

        return $perm;
    }

    /**
     * Sets a permission entry, is a wrapper for create() function
     *
     * @param int $group Specifies the frontend group
     * @param string $plugin Specifies the plugin
     * @param string $action Specifies the action
     * @param string $item Specifies the item
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function setPerm($group, $plugin, $action, $item)
    {
        $this->create($group, $plugin, $action, $item);
    }

    /**
     * Cheks, if an entry exists.
     *
     * 1.) Checks for global permission
     * 2.) Checks for specific item permission
     *
     * @param int $group Specifies the frontend group
     * @param string $plugin Specifies the plugin
     * @param string $action Specifies the action
     * @param string $item Specifies the item
     * @param bool $useLang [optional] Flag to use language (Not used!)
     * @throws cDbException|cException
     */
    public function checkPerm($group, $plugin, $action, $item, $useLang = false): bool
    {
        // checklang = ($useLang !== false) ? $useLang : $lang;

        $lang = cRegistry::getLanguageId();
        $group = cSecurity::toInteger($group);
        $plugin = $this->_frontendPermission->inFilter($plugin);
        $action = $this->_frontendPermission->inFilter($action);
        $item = $this->_frontendPermission->inFilter($item);

        // Check for global permission
        $this->select(sprintf(
            "`idlang` = %d AND `idfrontendgroup` = %d AND `plugin` = '%s' AND `action` = '%s' AND `item` = '__GLOBAL__'",
            $lang,
            $group,
            $plugin,
            $action
        ));
        if ($this->next()) {
            return true;
        }

        // Check for item permission
        $this->select(sprintf(
            "`idlang` = %d AND `idfrontendgroup` = %d AND `plugin` = '%s' AND `action` = '%s' AND `item` = '%s'",
            $lang,
            $group,
            $plugin,
            $action,
            $item
        ));
        return (bool) $this->next();
    }

    /**
     * Removes the permission.
     *
     * @param int $group Specifies the frontend group
     * @param string $plugin Specifies the plugin
     * @param string $action Specifies the action
     * @param string $item Specifies the item
     * @param bool $useLang [optional] Flag to use language (Not used!)
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function removePerm($group, $plugin, $action, $item, $useLang = false): bool
    {
        // checklang = ($useLang !== false) ? $useLang : $lang;

        $lang = cRegistry::getLanguageId();
        $group = cSecurity::toInteger($group);
        $plugin = $this->_frontendPermission->inFilter($plugin);
        $action = $this->_frontendPermission->inFilter($action);
        $item = $this->_frontendPermission->inFilter($item);

        $this->select(sprintf(
            "`idlang` = %d AND `idfrontendgroup` = %d AND `plugin` = '%s' AND `action` = '%s' AND `item` = '%s'",
            $lang,
            $group,
            $plugin,
            $action,
            $item
        ));
        if (($item = $this->next()) !== false) {
            return $this->delete($item->get('idfrontendpermission'));
        }
        return false;
    }
}

/**
 * Frontend permission item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiFrontendPermission extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id Specifies the ID of item to load
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('frontendpermissions'), 'idfrontendpermission');
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }
}
