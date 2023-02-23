<?php

/**
 * This file contains the layout synchronizer class.
 *
 * @package    Core
 * @subpackage LayoutHandler
 * @author     Rusmir Jusufovic
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class synchronizes layouts from filesystem to database table.
 *
 * @package    Core
 * @subpackage LayoutHandler
 */
class cLayoutSynchronizer {

    /**
     * @var array
     */
    protected $_cfg;

    /**
     * @var array
     */
    protected $_cfgClient;

    /**
     * @var int
     */
    protected $_lang;

    /**
     * @var int
     */
    protected $_client;

    /**
     * @var array
     */
    private $_outputMessage = [];

    /**
     * Constructor to create an instance of this class.
     *
     * @param array $cfg
     * @param array $cfgClient
     * @param int $lang
     * @param int $client
     */
    public function __construct($cfg, $cfgClient, $lang, $client) {
        $this->_cfg = $cfg;
        $this->_cfgClient = $cfgClient;
        $this->_lang = $lang;
        $this->_client = $client;
    }

    /**
     * Add a Layout to table or update a layout
     *
     * @param string $dir
     * @param string $oldLayoutName
     * @param string $newLayoutName
     * @param string $idclient
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    private function _addOrUpdateLayout($dir, $oldLayoutName, $newLayoutName, $idclient) {
        // if layout dont exist in the $cfg["tab"]["lay"] table.
        if ($this->_isExistInTable($oldLayoutName, $idclient) == false) {
            // add new Layout in db-table
            $layoutCollection = new cApiLayoutCollection();
            $layoutCollection->create($newLayoutName, $idclient, $newLayoutName);

            // make a layout file if not exist
            if (!cFileHandler::exists($dir . $newLayoutName . '/' . $newLayoutName . '.html')) {
                cFileHandler::write($dir . $newLayoutName . '/' . $newLayoutName . '.html', '');
            }

            // set output message
            $this->_outputMessage['info'][] = sprintf(i18n("Layout synchronization successful: %s"), $newLayoutName);
        } else {
            // update the name of the layout
            if ($oldLayoutName != $newLayoutName) {
                $this->_updateModulnameInDb($oldLayoutName, $newLayoutName, $idclient);
            }
        }
    }

    /**
     * Update the name of layout (if the name not allowes)
     *
     * @param string $oldName
     *         old name
     * @param string $newName
     *         new module name
     * @param int    $idclient
     *         id of client
     *
     * @throws cDbException
     * @throws cException
     */
    private function _updateModulnameInDb($oldName, $newName, $idclient) {
        $oLayColl = new cApiLayoutCollection();
        $oLayColl->select("alias='" . $oLayColl->escape($oldName) . "' AND idclient=" . (int) $idclient);
        if (false !== $oLay = $oLayColl->next()) {
            $oLay->set('alias', $newName);
            $oLay->store();
        }
    }

    /**
     * Rename the directory and files
     *
     * @param string $dir
     * @param string $dirNameOld
     * @param string $dirNameNew
     * @param int $client
     *         unused
     * @return bool
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
     *
     * @param string $alias
     *         layout name
     * @param int    $idclient
     *         client id
     * @return bool
     * @throws cDbException
     */
    private function _isExistInTable($alias, $idclient) {
        // Select depending from idclient all moduls wiht the name $name
        $oLayColl = new cApiLayoutCollection();
        $ids = $oLayColl->getIdsByWhereClause("alias='" . $oLayColl->escape($alias) . "' AND idclient=" . (int) $idclient);
        return (count($ids) > 0) ? true : false;
    }

    /**
     * Rename the Layout
     *
     * @param string $dir
     *         path to client layout-direcotry $dir
     * @param string $oldLayoutName
     *         layout name in file directory
     * @param string $newLayoutName
     *         clear layout name
     */
    private function _renameFiles($dir, $oldLayoutName, $newLayoutName) {
        if (cFileHandler::exists($dir . $newLayoutName . '/' . $oldLayoutName . '.html') == true) {
            rename($dir . $newLayoutName . '/' . $oldLayoutName . '.html', $dir . $newLayoutName . '/' . $newLayoutName . '.html');
        }
    }

    /**
     * Update the con_mod, the field lastmodified
     *
     * @param int $timestamp
     *         timestamp of last modification
     * @param int $idlay
     *         Id of layout
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function setLastModified($timestamp, $idlay) {
        $oLay = new cApiLayout((int) $idlay);
        if ($oLay->isLoaded()) {
            $oLay->set('lastmodified', date('Y-m-d H:i:s', $timestamp));
            $oLay->store();
        }
    }

    /**
     * Compare file change timestamp and the timestamp in ["tab"]["lay"].
     * If file had changed make new code :conGenerateCodeForAllArtsUsingMod
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     * @throws cException
     */
    private function _compareFileAndLayoutTimestamp() {
        // get all layouts from client
        $sql = sprintf("SELECT UNIX_TIMESTAMP(lastmodified) AS lastmodified, alias, name, description, idlay FROM %s WHERE idclient=%s", $this->_cfg['tab']['lay'], $this->_client);
        $notification = new cGuiNotification();
        $dir = $this->_cfgClient[$this->_client]['layout']['path'];

        $db = cRegistry::getDb();
        $db->query($sql);
        $retIdMod = 0;
        while ($db->nextRecord()) {
            $lastmodified = $db->f('lastmodified');

            // exist layout directory
            if (is_dir($dir . $db->f('alias') . '/')) {
                if (cFileHandler::exists($dir . $db->f('alias') . '/' . $db->f('alias') . '.html')) {
                    $lastmodifiedLayout = filemtime($dir . $db->f('alias') . '/' . $db->f('alias') . '.html');

                    // update layout data
                    if ($lastmodified < $lastmodifiedLayout) {
                        // update field lastmodified in table lay
                        $this->setLastModified($lastmodifiedLayout, $db->f('idlay'));
                        $layout = new cLayoutHandler($db->f('idlay'), ' ', $this->_cfg, $this->_lang);
                        // Update CODE table
                        conGenerateCodeForAllartsUsingLayout($db->f('idlay'));
                        $this->_outputMessage['info'][] = i18n("Layout synchronization successful: ") . $db->f('name');
                    }
                }
            } else {
                $oLayout = new cApiLayout($db->f('idlay'));

                $layout = new cLayoutHandler($db->f('idlay'), '', $this->_cfg, $this->_lang);
                // is layout in use
                if ($oLayout->isInUse()) {
                    // make layout file
                    $layout->saveLayout('');
                    $this->_outputMessage['info'][] = i18n("Layout synchronization successful, created: ") . $db->f('name');
                } else {
                    // if not in use delete layout
                    if ($layout->eraseLayout()) {
                        layDeleteLayout($db->f('idlay'));
                        $this->_outputMessage['info'][] = i18n("Layout synchronization successful, deleted: ") . $db->f('name');
                    } else {
                        $this->_outputMessage['error'][] = i18n("Synchronization failed could not delete layout: ") . $db->f('name');
                    }
                }
            }
        }
    }

    /**
     */
    private function _showOutputMessage() {
        $emptyMessage = true;
        $notification = new cGuiNotification();
        foreach ($this->_outputMessage as $typ) {
            foreach ($typ as $message) {
                $emptyMessage = false;
                // show display massage
                $notification->displayNotification($typ, $message);
            }
        }
        if ($emptyMessage) {
            $notification->displayNotification('info', i18n("Synchronization successful!"));
        }
    }

    /**
     * Synchronize the Layout directory with the lay-table und the lay-table
     * with directory.
     *
     * @return bool
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function synchronize() {
        // update file and layout
        $this->_compareFileAndLayoutTimestamp();

        // get the path to clients layouts
        $dir = $this->_cfgClient[$this->_client]['layout']['path'];

        // is/exist directory
        if (!is_dir($dir)) {
            return false;
        }

        if (false !== ($handle = cDirHandler::read($dir))) {
            foreach ($handle as $file) {
                // skip dirs to exclude
                // @todo should use setting for dirs to exclude
                if (cFileHandler::fileNameBeginsWithDot($file)) {
                    continue;
                }

                // skip entries that are no directories
                if (false === is_dir($dir . $file . '/')) {
                    continue;
                }

                $newFile = cString::toLowerCase(cString::cleanURLCharacters($file));

                if ($newFile == $file) {
                    // dir is ok
                    $this->_addOrUpdateLayout($dir, $file, $newFile, $this->_client);
                } else {
                    // dir not ok (with not allowed characters)
                    if (is_dir($dir . $newFile) && cString::toLowerCase($file) != $newFile) {
                        // exist the new dir name after clean?
                        // make new dirname
                        $newDirName = $newFile . cString::getPartOfString(md5(time() . rand(0, time())), 0, 4);
                        // rename
                        if ($this->_renameFileAndDir($dir, $file, $newDirName, $this->_client) != false) {
                            $this->_addOrUpdateLayout($dir, $file, $newDirName, $this->_client);
                        }
                    } else {
                        // $newFile (dir) not exist
                        // rename dir old
                        if ($this->_renameFileAndDir($dir, $file, $newFile, $this->_client) != false) {
                            $this->_addOrUpdateLayout($dir, $file, $newFile, $this->_client);
                        }
                    }
                }
            }
        }

        $this->_showOutputMessage();
    }
}
