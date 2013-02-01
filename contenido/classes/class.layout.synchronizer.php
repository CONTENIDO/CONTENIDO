<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Synchronize the layout directory with lay db-table.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.0.1
 * @author     Rusmir Jusufovic
 * @copyright  four for business AG <info@contenido.org>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

class cLayoutSynchronizer {

    protected $_cfg;
    protected $_lang;
    protected $_client;
    protected $_cfgClient;
    private $_outputMessage = array();

    public function __construct($cfg, $cfgClient, $lang, $client) {
        $this->_cfg = $cfg;
        $this->_cfgClient = $cfgClient;
        $this->_lang = $lang;
        $this->_client = $client;
    }

    /**
     * Add a Layout to table or update a layout
     * @param string $dir
     * @param string $oldLayoutName
     * @param string $newLayoutName
     * @param string $idclient
     */
    private function _addOrUpdateLayout($dir, $oldLayoutName, $newLayoutName, $idclient) {
        //if layout dont exist in the $cfg["tab"]["lay"] table.
        if ($this->_isExistInTable($oldLayoutName, $idclient) == false) {
            //add new Layout in db-table
            $this->_addLayout($newLayoutName, $idclient);
            //make a layout file if not exist
            if (!cFileHandler::exists($dir . $newLayoutName . '/' . $newLayoutName . '.html')) {
                cFileHandler::write($dir . $newLayoutName . '/' . $newLayoutName . '.html', '');
            }

            //set output message
            $this->_outputMessage['info'][] = sprintf(i18n("Synchronization successfully layout name: %s"), $newLayoutName);
        } else {
            //update the name of the module
            if ($oldModulName != $newModulName) {
                $this->_updateModulnameInDb($oldLayoutName, $newLayoutName, $idclient);
            }
        }
    }

    /**
     * Update the name of layout (if the name not allowes)
     * @param string $oldName  old name
     * @param string $newName new module name
     * @param int $idclient id of client
     */
    private function _updateModulnameInDb($oldName, $newName, $idclient) {
        $oLayColl = new cApiLayoutCollection();
        $oLayColl->select("alias='" . $oLayColl->escape($oldName) . "' AND idclient=" . (int) $idclient);
        if ($oLay = $oLayColl->next()) {
            $oLay->set('alias', $newName);
            $oLay->store();
        }
    }

    /**
     * Add a layout to Db-table
     * @param string $name
     * @param int $idclient
     */
    private function _addLayout($name, $idclient) {
        $oLayColl = new cApiLayoutCollection();
        $oLayColl->create($name, $idclient);
    }

    /**
     * Rename the directory and files
     * @param string $dir
     * @param string $dirNameOld
     * @param string $dirNameNew
     * @param int $client
     */
    private function _renameFileAndDir($dir, $dirNameOld, $dirNameNew, $client) {
        if (rename($dir . $dirNameOld, $dir . $dirNameNew) == false) {
            return false;
        }

        $this->_renameFiles($dir, $dirNameOld, $dirNameNew);

        return true;
    }

    /**
     * Exist the layout in db-table
     * @param string $alias  layout name
     * @param int $idclient client id
     */
    private function _isExistInTable($alias, $idclient) {
        //Select depending from idclient all moduls wiht the name $name
        $oLayColl = new cApiLayoutCollection();
        $ids = $oLayColl->getIdsByWhereClause("alias='" . $oLayColl->escape($alias) . "' AND idclient=" . (int) $idclient);
        return (count($ids) > 0) ? true : false;
    }

    /**
     * Rename the Layout
     * @param path to  client layout-direcotry $dir
     * @param string $oldLayoutName layout name in file directory
     * @param string $newLayoutName clear layout name
     */
    private function _renameFiles($dir, $oldLayoutName, $newLayoutName) {
        if (cFileHandler::exists($dir . $newLayoutName . '/' . $oldLayoutName . '.html') == true) {
            rename($dir . $newLayoutName . '/' . $oldLayoutName . '.html', $dir . $newLayoutName . '/' . $newLayoutName . '.html');
        }
    }

    /**
     * Update the con_mod, the field lastmodified
     *
     * @param int $timestamp timestamp of last modification
     * @param int $idlay Id of layout
     */
    public function setLastModified($timestamp, $idlay) {
        $oLay = new cApiLayout((int) $idlay);
        if ($oLay->isLoaded()) {
            $oLay->set('lastmodified', date('Y-m-d H:i:s', $timestamp));
            $oLay->store();
        }
    }

    /**
     * Compare file change timestemp and the timestemp in ["tab"]["lay"].
     * If file had changed make new code :conGenerateCodeForAllArtsUsingMod
     */
    private function _compareFileAndLayoutTimestamp() {
        //get all layouts from client
        $sql = sprintf("SELECT UNIX_TIMESTAMP(lastmodified) AS lastmodified, alias, name, description, idlay FROM %s WHERE idclient=%s", $this->_cfg['tab']['lay'], $this->_client);
        $notification = new cGuiNotification();
        $dir = $this->_cfgClient[$this->_client]['layout']['path'];

        $db = cRegistry::getDb();
        $db->query($sql);
        $retIdMod = 0;
        while ($db->nextRecord()) {
            $lastmodified = $db->f('lastmodified');

            //exist layout directory
            if (is_dir($dir . $db->f('alias') . '/')) {
                if (cFileHandler::exists($dir . $db->f('alias') . '/' . $db->f('alias') . '.html')) {
                    $lastmodifiedLayout = filemtime($dir . $db->f('alias') . '/' . $db->f('alias') . '.html');

                    //update layout data
                    if ($lastmodified < $lastmodifiedLayout) {
                        //update field lastmodified in table lay
                        $this->setLastModified($lastmodifiedLayout, $db->f('idlay'));
                        $layout = new cLayoutHandler($db->f('idlay'), ' ', $this->_cfg, $this->_lang);
                        // Update CODE table
                        conGenerateCodeForAllartsUsingLayout($db->f('idlay'));
                        $this->_outputMessage['info'][] = i18n("Synchronization successfully layout name: ") . $db->f('name');
                    }
                }
            } else {
                $oLayout = new cApiLayout($db->f('idlay'));

                $layout = new cLayoutHandler($db->f('idlay'), '', $this->_cfg, $this->_lang);
                //is layout in use
                if ($oLayout->isInUse()) {
                    //make layout file
                    $layout->saveLayout('');
                    $this->_outputMessage['info'][] = i18n("Synchronization successfully layout name made: ") . $db->f('name');
                } else {
                    //if not in use delete layout
                    if ($layout->eraseLayout()) {
                        layDeleteLayout($db->f('idlay'));
                        $this->_outputMessage['info'][] = i18n("Synchronization successfully layout deleted: ") . $db->f('name');
                    } else {
                        $this->_outputMessage['error'][] = i18n("Synchronization faild cold not delate layout: ") . $db->f('name');
                    }
                }
            }
        }
    }

    private function _showOutputMessage() {
        $emptyMessage = true;
        $notification = new cGuiNotification();
        foreach ($this->_outputMessage as $typ) {
            foreach ($typ as $message) {
                $emptyMessage = false;
                //show display massage
                $notification->displayNotification($typ, $message);
            }
        }
        if ($emptyMessage) {
            $notification->displayNotification('info', i18n("Synchronization successfully!"));
        }
    }

    /**
     * If the first char a '.' return false else true
     * @param string $file
     * @return boolean true if the first char !='.' else false
     */
    private function _isValidFirstChar($file) {
        return (!(substr($file, 0, 1) == '.'));
    }

    /**
     * Synchronize the Layout directory with the lay-table und the lay-table
     * with directory.
     */
    public function synchronize() {
        //update file and layout
        $this->_compareFileAndLayoutTimestamp();

        //get the path to cliets layouts
        $dir = $this->_cfgClient[$this->_client]['layout']['path'];

        //is/exist directory
        if (!is_dir($dir)) {
            return false;
        }

        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                //is file a dir or not
                if ($this->_isValidFirstChar($file) && is_dir($dir . $file . "/")) {
                    $newFile = strtolower(cApiStrCleanURLCharacters($file));
                    //dir is ok
                    if ($newFile == $file) {
                        $this->_addOrUpdateLayout($dir, $file, $newFile, $this->_client);
                    } else { //dir not ok (with not allowed characters)
                        if (is_dir($dir . $newFile) && strtolower($file) != $newFile) {// exist the new dir name after clean?
                            //make new dirname
                            $newDirName = $newFile . substr(md5(time() . rand(0, time())), 0, 4);
                            //rename
                            if ($this->_renameFileAndDir($dir, $file, $newDirName, $this->_client) != false) {
                                $this->_addOrUpdateLayout($dir, $file, $newDirName, $this->_client);
                            }
                        } else {//$newFile (dir) not exist
                            //rename dir old
                            if ($this->_renameFileAndDir($dir, $file, $newFile, $this->_client) != false) {
                                $this->_addOrUpdateLayout($dir, $file, $newFile, $this->_client);
                            }
                        }
                    }
                }
            }
        }
        //close dir
        closedir($dh);
        $this->_showOutputMessage();
    }

}


/**
 * @deprecated [2011-11-15] Use cLayoutSynchronizer instead of this class.
 */
class SynchronizeLayouts extends cLayoutSynchronizer {
    /**
     * @deprecated [2011-11-15] Use cLayoutHandler instead of this class.
     */
    public function __construct($cfg, $cfgClient, $lang, $client) {
        cDeprecated("Use class cLayoutSynchronizer instead");
        parent::__construct($cfg, $cfgClient, $lang, $client);
    }
}
?>