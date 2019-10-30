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
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.api.string.php');
cInclude('includes', 'functions.con.php');

/**
 * This class synchronized the contents of modul dir with the table
 * $cfg['tab']['mod']. If a modul exist in modul dir but not in
 * $cfg['tab']['mod'] this class will insert the modul in the table.
 *
 * @package    Core
 * @subpackage Backend
 */
class cModuleSynchronizer extends cModuleHandler {

    /**
     * The last id of the module that had changed or had added.
     *
     * @var int
     */
    private $_lastIdMod = 0;

    /**
     * This method insert a new modul in $cfg['tab']['mod'] table, if
     * the name of modul dont exist
     *
     * @param string $dir
     * @param string $oldModulName
     * @param string $newModulName
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    private function _syncModule($dir, $oldModulName, $newModulName) {
        global $client;
        // if modul dont exist in the $cfg['tab']['mod'] table.
        if ($this->_isExistInTable($oldModulName, $client) == false) {
            // add new Module in db-tablle
            $this->_addModule($newModulName);
        } else {
            // update the name of the module
            if ($oldModulName != $newModulName) {
                $this->_updateModulnameInDb($oldModulName, $newModulName, $client);
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
    private function _renameFiles($dir, $oldModulName, $newModulName) {
        if (cFileHandler::exists($dir . $newModulName . '/' . $this->_directories['php'] . $oldModulName . '_input.php') == true) {
            rename($dir . $newModulName . '/' . $this->_directories['php'] . $oldModulName . '_input.php', $dir . $newModulName . '/' . $this->_directories['php'] . $newModulName . '_input.php');
        }

        if (cFileHandler::exists($dir . $newModulName . '/' . $this->_directories['php'] . $oldModulName . '_output.php') == true) {
            rename($dir . $newModulName . '/' . $this->_directories['php'] . $oldModulName . '_output.php', $dir . $newModulName . '/' . $this->_directories['php'] . $newModulName . '_output.php');
        }

        if (cFileHandler::exists($dir . $newModulName . '/' . $this->_directories['css'] . $oldModulName . '.css') == true) {
            rename($dir . $newModulName . '/' . $this->_directories['css'] . $oldModulName . '.css', $dir . $newModulName . '/' . $this->_directories['css'] . $newModulName . '.css');
        }

        if (cFileHandler::exists($dir . $newModulName . '/' . $this->_directories['js'] . $oldModulName . '.js') == true) {
            rename($dir . $newModulName . '/' . $this->_directories['js'] . $oldModulName . '.js', $dir . $newModulName . '/' . $this->_directories['js'] . $newModulName . '.js');
        }
    }

    /**
     * Rename the Modul files and Modul dir
     *
     * @param string $dir
     *         path the the moduls
     * @param string $dirNameOld
     *         old dir name
     * @param string $dirNameNew
     *         new dir name
     * @param int $client
     *         idclient
     * @return bool
     *         true on success or false on failure
     */
    private function _renameFileAndDir($dir, $dirNameOld, $dirNameNew, $client) {
        if (rename($dir . $dirNameOld, $dir . $dirNameNew) == FALSE) {
            return false;
        } else { // change names of the files
            $this->_renameFiles($dir, $dirNameOld, $dirNameNew);
        }
        return true;
    }

    /**
     * Compare file change timestemp and the timestemp in ['tab']['mod'].
     * If file had changed make new code :conGenerateCodeForAllArtsUsingMod
     *
     * @return int
     *         id of last update module
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function compareFileAndModuleTimestamp() {
        global $cfgClient;

        $db = cRegistry::getDb();
        $db->query('
            SELECT lastmodified, idclient, description, type, `name`, alias, idmod
            FROM ' . $this->_cfg['tab']['mod'] . '
            WHERE idclient = ' . cSecurity::toInteger($this->_client));

        $synchLock = 0;
        $retIdMod  = 0;

        // for performance reasons IDs of modules are collected in order to generate their code
        $idmods    = [];

        while ($db->nextRecord()) {
            $showMessage = false;

            $modulePath = $cfgClient[$db->f('idclient')]['module']['path'] . $db->f('alias') . '/';
            $modulePHP = $modulePath . $this->_directories['php'] . $db->f('alias');

            $lastmodified = $db->f('lastmodified');
            $lastmodified = DateTime::createFromFormat('Y-m-d H:i:s', $lastmodified);
            $lastmodified = $lastmodified->getTimestamp();

            $lastmodInput = $lastmodOutput = 0;

            if (cFileHandler::exists($modulePHP . '_input.php')) {
                $lastmodInput = filemtime($modulePHP . '_input.php');
            }

            if (cFileHandler::exists($modulePHP . '_output.php')) {
                $lastmodOutput = filemtime($modulePHP . '_output.php');
            }

            if (cFileHandler::exists($modulePath . "info.xml")) {
                $lastModInfo = filemtime($modulePath . "info.xml");
                if ($lastModInfo > $lastmodified) {
                    try {
                        $xml = cFileHandler::read($modulePath . 'info.xml');
                    } catch (cInvalidArgumentException $e) {
                        $xml = '';
                    }
                    $modInfo = cXmlBase::xmlStringToArray($xml);
                    $mod     = new cApiModule($db->f("idmod"));
                    if ($modInfo["description"] != $mod->get("description")) {
                        $mod->set("description", $modInfo["description"]);
                        $this->setLastModified($lastModInfo, $db->f('idmod'));
                    }
                    if ($modInfo["type"] != $mod->get("type")) {
                        $mod->set("type", $modInfo["type"]);
                        $this->setLastModified($lastModInfo, $db->f('idmod'));
                    }

                    if ($modInfo["name"] != $mod->get("name")) {
                        $mod->set("name", $modInfo["name"]);
                        $this->setLastModified($lastModInfo, $db->f('idmod'));
                    }

                    if ($modInfo["alias"] != $mod->get("alias")) {
                        $mod->set("alias", $modInfo["alias"]);
                        $this->setLastModified($lastModInfo, $db->f('idmod'));
                    }
                    $mod->store(true);
                    $synchLock = 1;
                    $showMessage = true;
                }
            }

            $lastmodabsolute = max($lastmodInput, $lastmodOutput);
            if ($lastmodified < $lastmodabsolute) {
                $synchLock = 1;
                $this->setLastModified($lastmodabsolute, $db->f('idmod'));
                $idmods[] = (int) $db->f('idmod');
                $showMessage = true;
            }

            if (($idmod = $this->_synchronizeFilesystemAndDb($db)) != 0) {
                $retIdMod = $idmod;
            }

            if ($showMessage) {
                cRegistry::appendLastOkMessage(sprintf(i18n('Module %s successfully synchronized'), $db->f('name')));
            }
        }

        if (count($idmods)) {
            conGenerateCodeForAllartsUsingMod($idmods);
        }

        if ($synchLock == 0) {
            cRegistry::addInfoMessage(i18n('All modules are already synchronized'));
        }

        // we need it for the update of moduls on the left site (module/backend)
        return $retIdMod;
    }

    /**
     * If someone delete a moduldir with ftp/ssh.
     * We have a modul
     * in db but not in directory, if the modul in use make a new modul in
     * fileystem but if not
     * clear it from filesystem.
     *
     * @param cDb $db
     *         CONTENIDO database object
     *
     * @return int
     *         id of last update module
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    private function _synchronizeFilesystemAndDb($db) {
        $returnIdMod = 0;
        $this->initWithDatabaseRow($db);
        // modul dont exist in filesystem
        if ($this->modulePathExists() == false) {
            $modul = new cApiModule($db->f('idmod'));
            $returnIdMod = $db->f('idmod');
            if ($modul->moduleInUse($db->f('idmod')) == true) {
                // modul in use, make new modul in filesystem
                if ($this->createModule() == false) {
                    $notification = new cGuiNotification();
                    $notification->displayNotification('error', i18n("Can not create module") . " " . $db->f('name'));
                }
            } else {
                // modul not in use, delete it
                $sql = sprintf('DELETE  FROM %s WHERE idmod = %s AND idclient = %s', $this->_cfg['tab']['mod'], $db->f('idmod'), $this->_client);
                $myDb = cRegistry::getDb();
                $myDb->query($sql);
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
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function synchronize() {
        global $cfgClient;

        // get the path to the modul dir from the client
        $dir = $cfgClient[$this->_client]['module']['path'];

        if (is_dir($dir)) {
            if (false !== ($handle = cDirHandler::read($dir))) {
                foreach ($handle as $file) {
                    if (false === cFileHandler::fileNameBeginsWithDot($file) && is_dir($dir . $file . '/')) {
                        $newFile = cString::cleanURLCharacters($file);
                        // dir is ok
                        if ($newFile == $file) {
                            $this->_syncModule($dir, $file, $newFile);
                        } else { // dir not ok (with not allowed characters)
                            if (is_dir($dir . $newFile)) { // exist the new dir
                                // name?
                                // make new dirname
                                $newDirName = $newFile . cString::getPartOfString(md5(time() . rand(0, time())), 0, 4);

                                // rename
                                if ($this->_renameFileAndDir($dir, $file, $newDirName, $this->_client) != false) {
                                    $this->_syncModule($dir, $file, $newDirName);
                                }
                            } else { // $newFile (dir) not exist
                                // rename dir old
                                if ($this->_renameFileAndDir($dir, $file, $newFile, $this->_client) != false) {
                                    $this->_syncModule($dir, $file, $newFile);
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
     * This method look in the db-table $cfg['tab']['mod'] for a modul
     * name.
     * If the modul name exist it will return true
     *
     * @param string $alias
     * @param int    $idclient
     *         idclient
     * @return bool
     *         if a modul with the $name exist in the $cfg['tab']['mod'] table
     *         return true else false
     * @throws cDbException
     */
    private function _isExistInTable($alias, $idclient) {
        $db = cRegistry::getDb();

        // Select depending from idclient all moduls wiht the name $name
        $sql = sprintf("SELECT * FROM %s WHERE alias='%s' AND idclient=%s", $this->_cfg['tab']['mod'], $alias, $idclient);

        $db->query($sql);

        // a record is found
        if ($db->nextRecord()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Update the name of module (if the name not allowes)
     *
     * @param string $oldName
     *         old name
     * @param string $newName
     *         new module name
     * @param int    $idclient
     *         id of client
     * @throws cDbException
     */
    private function _updateModulnameInDb($oldName, $newName, $idclient) {
        $db = cRegistry::getDb();

        // Select depending from idclient all modules wiht the name $name
        $sql = sprintf("SELECT * FROM %s WHERE alias='%s' AND idclient=%s", $this->_cfg['tab']['mod'], $oldName, $idclient);

        $db->query($sql);

        // a record is found
        if ($db->nextRecord()) {
            $sqlUpdateName = sprintf("UPDATE %s SET alias='%s' WHERE idmod=%s", $this->_cfg['tab']['mod'], $newName, $db->f('idmod'));
            $db->query($sqlUpdateName);
            return;
        }
    }

    /**
     * This method add a new Modul in the table $cfg['tab']['mod'].
     *
     * @param string $name
     *         name of the new module
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    private function _addModule($name) {

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

        // create mew module
        $oModColl = new cApiModuleCollection();
        $mod = $oModColl->create(
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

        // save last module id
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
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function setLastModified($timestamp, $idmod) {
        $oMod = new cApiModule((int) $idmod);
        if ($oMod->isLoaded()) {
            $oMod->set('lastmodified', date('Y-m-d H:i:s', $timestamp));
            $oMod->store(true);
        }
    }

}
