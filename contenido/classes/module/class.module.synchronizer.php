<?php

/**
 * This file contains the module synchronizer class.
 *
 * @todo refactor documentation
 *
 * @package    Core
 * @subpackage Backend
 * @author     Rusmir Jusufovic
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.api.string.php');
cInclude('includes', 'functions.con.php');

/**
 * This class synchronizes the contents of the clients module directory with
 * the table $cfg['tab']['mod']. If a module exist in module directory but
 * not in the table, then it will be added to the table.
 *
 * @package    Core
 * @subpackage Backend
 */
class cModuleSynchronizer extends cModuleHandler
{

    /**
     * The last id of the module that had changed or had added.
     *
     * @var int
     */
    private $_lastIdMod = 0;

    /**
     * @var cApiModuleCollection
     */
    private $_moduleCollection;

    /**
     * @inheritdoc
     */
    public function __construct($module = NULL) {
        parent::__construct($module);
        $this->_moduleCollection = new cApiModuleCollection();
    }

    /**
     * This method inserts a new modul in $cfg['tab']['mod'] table, if
     * the name of the module don't exist.
     *
     * @param string $oldModulName
     * @param string $newModulName
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    private function _syncNameInDb(string $oldModulName, string $newModulName)
    {
        // If module don't exist in the $cfg['tab']['mod'] table.
        if (!$this->_existsInTable($oldModulName, $this->_client)) {
            // Add new module in db table
            $this->_addModule($newModulName);
        } else {
            // Update the name of the module
            if ($oldModulName != $newModulName) {
                $this->_updateModuleNameInDb($oldModulName, $newModulName, $this->_client);
            }
        }
    }

    /**
     * Rename css, js and input/output file
     *
     * @param string $dir
     * @param string $oldModulName
     * @param string $newModulName
     */
    private function _renameFiles(string $dir, string $oldModulName, string $newModulName)
    {
        $moduleDir = $dir . $newModulName . '/';
        if (cFileHandler::exists($moduleDir . $this->_directories['php'] . $oldModulName . '_input.php')) {
            rename(
                $moduleDir . $this->_directories['php'] . $oldModulName . '_input.php',
                $moduleDir . $this->_directories['php'] . $newModulName . '_input.php'
            );
        }

        if (cFileHandler::exists($moduleDir . $this->_directories['php'] . $oldModulName . '_output.php')) {
            rename(
                $moduleDir . $this->_directories['php'] . $oldModulName . '_output.php',
                $moduleDir . $this->_directories['php'] . $newModulName . '_output.php'
            );
        }

        if (cFileHandler::exists($moduleDir . $this->_directories['css'] . $oldModulName . '.css')) {
            rename(
                $moduleDir . $this->_directories['css'] . $oldModulName . '.css',
                $moduleDir . $this->_directories['css'] . $newModulName . '.css'
            );
        }

        if (cFileHandler::exists($moduleDir . $this->_directories['js'] . $oldModulName . '.js')) {
            rename(
                $moduleDir . $this->_directories['js'] . $oldModulName . '.js',
                $moduleDir . $this->_directories['js'] . $newModulName . '.js'
            );
        }
    }

    /**
     * Rename the Modul files and Modul dir
     *
     * @param string $dir
     *         path to the moduls
     * @param string $dirNameOld
     *         old dir name
     * @param string $dirNameNew
     *         new dir name
     * @return bool
     *         true on success or false on failure
     */
    private function _renameFileAndDir(string $dir, string $dirNameOld, string $dirNameNew): bool
    {
        if (!rename($dir . $dirNameOld, $dir . $dirNameNew)) {
            return false;
        } else {
            // Change names of the files
            $this->_renameFiles($dir, $dirNameOld, $dirNameNew);
        }
        return true;
    }

    /**
     * Compare file change timestamp and the timestamp in ['tab']['mod'].
     * If file had changed make new code :conGenerateCodeForAllArtsUsingMod
     *
     * @return int
     *         id of last update module
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function compareFileAndModuleTimestamp(): int
    {
        // Get all modules by client
        /** @var cApiModule[] $modules */
        $modules = $this->_moduleCollection->getAllByIdclient($this->_client, '', true);

        $syncLock = 0;
        $retIdMod  = 0;
        $syncedModuleIds = [];

        foreach ($modules as $module) {
            $_idMod = cSecurity::toInteger($module->getId());
            $showMessage = false;

            $modulePath = $this->_cfgClient[$this->_client]['module']['path'] . $module->get('alias') . '/';
            $modulePHP = $modulePath . $this->_directories['php'] . $module->get('alias');

            $lastmodified = $module->get('lastmodified');
            $lastmodified = DateTime::createFromFormat('Y-m-d H:i:s', $lastmodified);
            $lastmodified = $lastmodified->getTimestamp();

            $lastmodInput = $lastmodOutput = $lastModInfo = 0;

            if (cFileHandler::exists($modulePHP . '_input.php')) {
                $lastmodInput = filemtime($modulePHP . '_input.php');
            }

            if (cFileHandler::exists($modulePHP . '_output.php')) {
                $lastmodOutput = filemtime($modulePHP . '_output.php');
            }

            if (cFileHandler::exists($modulePath . 'info.xml')) {
                $lastModInfo = filemtime($modulePath . 'info.xml');
                if ($lastModInfo > $lastmodified) {
                    try {
                        $xml = cFileHandler::read($modulePath . 'info.xml');
                    } catch (cInvalidArgumentException $e) {
                        $xml = '';
                    }
                    $modInfo = cXmlBase::xmlStringToArray($xml);
                    if ($modInfo['description'] != $module->get('description')) {
                        $module->set('description', $modInfo['description']);
                    }
                    if ($modInfo['type'] != $module->get('type')) {
                        $module->set('type', $modInfo['type']);
                    }

                    if ($modInfo['name'] != $module->get('name')) {
                        $module->set('name', $modInfo['name']);
                    }

                    if ($modInfo['alias'] != $module->get('alias')) {
                        $module->set('alias', $modInfo['alias']);
                    }
                    $syncLock = 1;
                    $showMessage = true;
                }
            }

            $lastModAbsolute = max($lastmodInput, $lastmodOutput, $lastModInfo);

            if ($module->isModified()) {
                $module->set('lastmodified', date('Y-m-d H:i:s', $lastModAbsolute));
            } elseif ($lastmodified < $lastModAbsolute) {
                $syncLock = 1;
                $module->set('lastmodified', date('Y-m-d H:i:s', $lastModAbsolute));
                $syncedModuleIds[] = cSecurity::toInteger($_idMod);
                $showMessage = true;
            }

            if ($module->isModified()) {
                $module->store(true);
            }

            if (($idmod = $this->_synchronizeFilesystemAndDb($module)) != 0) {
                $retIdMod = $idmod;
            }

            if ($showMessage) {
                cRegistry::appendLastOkMessage(sprintf(i18n('Module %s successfully synchronized'), $module->get('name')));
            }
        }

        if (count($syncedModuleIds)) {
            conGenerateCodeForAllartsUsingMod($syncedModuleIds);
        }

        if ($syncLock == 0) {
            cRegistry::addInfoMessage(i18n('All modules are already synchronized'));
        }

        // we need it for the update of modules on the left site (module/backend)
        return $retIdMod;
    }

    /**
     * If someone deletes a module-dir with ftp/ssh.
     * We have a module in the database but not in directory. If the module
     * is still in use, make a new module in the filesystem, otherwise clear
     * it from the filesystem.
     *
     * @param cApiModule $module
     *         The module instance
     *
     * @return int
     *         id of last update module
     * @throws cDbException|cException|cInvalidArgumentException
     */
    private function _synchronizeFilesystemAndDb(cApiModule $module): int
    {
        $idmod = cSecurity::toInteger($module->getId());
        $returnIdMod = 0;
        $this->_initByModule($module);
        // Module don't exist in filesystem
        if (!$this->modulePathExists()) {
            $returnIdMod = $idmod;
            if ($module->moduleInUse($idmod)) {
                // Module ids in use, make a new module in filesystem
                if (!$this->createModule()) {
                    $notification = new cGuiNotification();
                    $notification->displayNotification('error', i18n("Can not create module") . " " . $module->get('name'));
                }
            } else {
                // Module is not in use, delete it
                $this->_moduleCollection->delete($idmod);
            }
        }
        return $returnIdMod;
    }

    /**
     * Depending on the client, this method
     * will check the modul dir of the client and if found
     * a Modul(Dir) that not exist in Db-table this method will
     * insert the Modul in Db-table ([tab][mod]).
     *
     * @return int
     *         last id of synchronized module
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function synchronize(): int
    {
        // get the path to the module dir from the client
        $dir = $this->_cfgClient[$this->_client]['module']['path'];

        if (is_dir($dir)) {
            if (false !== ($handle = cDirHandler::read($dir))) {
                foreach ($handle as $file) {
                    if (!cFileHandler::fileNameBeginsWithDot($file) && is_dir($dir . $file . '/')) {
                        $newFile = cString::cleanURLCharacters($file);
                        // dir is ok
                        if ($newFile == $file) {
                            $this->_syncNameInDb($file, $newFile);
                        } else { // dir not ok (with not allowed characters)
                            if (is_dir($dir . $newFile)) { // exist the new dir
                                // name?
                                // make new dirname
                                $newDirName = $newFile . cString::getPartOfString(md5(time() . rand(0, time())), 0, 4);

                                // rename
                                if ($this->_renameFileAndDir($dir, $file, $newDirName)) {
                                    $this->_syncNameInDb($file, $newDirName);
                                }
                            } else { // $newFile (dir) not exist
                                // rename dir old
                                if ($this->_renameFileAndDir($dir, $file, $newFile)) {
                                    $this->_syncNameInDb($file, $newFile);
                                }
                            }
                        }
                    }
                }
            }
        }

        // last Modul Id that will refresh the windows /modul overview
        return $this->_lastIdMod;
    }

    /**
     * Checks, if a module entry exists in the database table.
     *
     * @param string $alias
     *        Module alias
     * @param int $idclient
     *         Client id
     * @return bool
     *         True if the module exists in the db table, otherwise false.
     * @throws cDbException|cInvalidArgumentException
     */
    private function _existsInTable(string $alias, int $idclient): bool
    {
        $id = $this->_getModuleIdByModuleAliasAndClientId($alias, $idclient);
        return $id > 0;
    }

    /**
     * Update the name of module (if the name is not allowed)
     *
     * @param string $oldName
     *         old name
     * @param string $newName
     *         new module name
     * @param int $idclient
     *         id of client
     * @throws cDbException|cInvalidArgumentException
     */
    private function _updateModuleNameInDb(string $oldName, string $newName, int $idclient)
    {
        $idmod = $this->_getModuleIdByModuleAliasAndClientId($oldName, $idclient);

        // If an entry was found
        if ($idmod > 0) {
            $db = cRegistry::getDb();
            $sql = $db->buildUpdate(
                cRegistry::getDbTableName('mod'),
                ['alias' => $newName],
                ['idmod' => $idmod]
            );
            $db->query($sql);
        }
    }

    /**
     * Returns the module entry id from the module table by client id and module alias.
     *
     * @param int $idclient
     *         Client id
     * @param string $alias
     *        Module alias
     * @return int
     *      The id of module or 0
     * @throws cDbException|cInvalidArgumentException
     */
    private function _getModuleIdByModuleAliasAndClientId(string $alias, int $idclient): int
    {
        $where = $this->_moduleCollection->prepare("`idclient` = %d AND `alias` = '%s'", $idclient, $alias);
        $ids = $this->_moduleCollection->getIdsByWhereClause($where);
        return !empty($ids) ? cSecurity::toInteger($ids[0]) : 0;
    }

    /**
     * This method add a new Modul in the table $cfg['tab']['mod'].
     *
     * @param string $name
     *         name of the new module
     *
     * @throws cException|cInvalidArgumentException
     */
    private function _addModule(string $name)
    {
        // initializing variables
        $client       = cRegistry::getClientId();
        $alias        = $name;
        $type         = '';
        $error        = 'none';
        $description  = '';
        $deletable    = 0;
        $template     = '';
        $static       = 0;
        $package_guid = '';
        $package_data = '';
        $author       = '';
        $created      = '';
        $lastmodified = '1970-01-01 00:00:00';

        // old behaviour before CON-2603
        // $cfgClient    = cRegistry::getClientConfig(cRegistry::getClientId());
        // $modulePath   = $cfgClient['module']['path'] . $name . '/';
        // $modInfo      = cXmlBase::xmlStringToArray(cFileHandler::read($modulePath . 'info.xml'));
        // $name         = $modInfo['name'];
        // $alias        = $modInfo['alias'];
        // $type         = $modInfo['type'];
        // $lastmodified = '';

        // Create mew module
        $mod = $this->_moduleCollection->create(
            $name,
            $client,
            $alias,
            $type,
            $error,
            $description,
            $deletable,
            $template,
            $static,
            $package_guid,
            $package_data,
            $author,
            $created,
            $lastmodified
        );

        // Save last module id
        if (is_object($mod)) {
            $this->_lastIdMod = $mod->get('idmod');
        }
    }

    /**
     * Update the con_mod, the field lastmodified
     *
     * @param int $timestamp
     *         timestamp of last modification
     * @param int $idmod
     *         id of module
     * @throws cInvalidArgumentException|cException
     */
    public function setLastModified($timestamp, $idmod)
    {
        $oMod = new cApiModule(cSecurity::toInteger($idmod));
        if ($oMod->isLoaded()) {
            $oMod->set('lastmodified', date('Y-m-d H:i:s', $timestamp));
            $oMod->store(true);
        }
    }

}
