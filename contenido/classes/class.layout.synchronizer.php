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
class cLayoutSynchronizer
{

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
     * @param array $cfg The CONTENIDO configuration array
     */
    public function __construct(array $cfg, array $cfgClient, int $languageId, int $clientId)
    {
        $this->_cfg = $cfg;
        $this->_cfgClient = $cfgClient;
        $this->_lang = $languageId;
        $this->_client = $clientId;
    }

    /**
     * Add a Layout to table or update a layout
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    private function _addOrUpdateLayout(string $dir, string $oldLayoutName, string $newLayoutName)
    {
        // if layout don't exist in the $cfg['tab']['lay'] table.
        if (!$this->_isExistInTable($oldLayoutName)) {
            // add new Layout in db-table
            $layoutCollection = new cApiLayoutCollection();
            $layoutCollection->create($newLayoutName, $this->_client, $newLayoutName);

            // make a layout file if not exist
            if (!cFileHandler::exists($dir . $newLayoutName . '/' . $newLayoutName . '.html')) {
                cFileHandler::write($dir . $newLayoutName . '/' . $newLayoutName . '.html', '');
            }

            // set output message
            $this->_outputMessage['info'][] = sprintf(i18n("Layout synchronization successful: %s"), $newLayoutName);
        } elseif ($oldLayoutName != $newLayoutName) {
            // update the name of the layout
            $this->_updateModulnameInDb($oldLayoutName, $newLayoutName);
        }
    }

    /**
     * Update the name of layout (if the name not allows)
     *
     * @param string $oldName Old name
     * @param string $newName New module name
     * @throws cDbException|cException
     */
    private function _updateModulnameInDb(string $oldName, string $newName)
    {
        $oLayColl = new cApiLayoutCollection();
        $oLayColl->select("`alias` = '" . $oLayColl->escape($oldName) . "' AND `idclient` = " . cSecurity::toInteger($this->_client));
        if (($oLay = $oLayColl->next()) !== false) {
            $oLay->set('alias', $newName);
            $oLay->store();
        }
    }

    /**
     * Rename the directory and files
     */
    private function _renameFileAndDir(string $dir, string $dirNameOld, string $dirNameNew): bool
    {
        if (!rename($dir . $dirNameOld, $dir . $dirNameNew)) {
            return false;
        }

        $this->_renameFiles($dir, $dirNameOld, $dirNameNew);

        return true;
    }

    /**
     * Exist the layout in db-table
     *
     * @param string $alias layout name
     * @throws cDbException
     */
    private function _isExistInTable(string $alias): bool
    {
        // Select depending from idclient all moduls with the name $name
        $oLayColl = new cApiLayoutCollection();
        $ids = $oLayColl->getIdsByWhereClause("alias='" . $oLayColl->escape($alias) . "' AND idclient=" . (int)$this->_client);
        return count($ids) > 0;
    }

    /**
     * Rename the Layout
     *
     * @param string $dir Path to client layout-directory $dir
     * @param string $oldLayoutName Layout name in file directory
     * @param string $newLayoutName Clear layout name
     */
    private function _renameFiles(string $dir, string $oldLayoutName, string $newLayoutName)
    {
        if (cFileHandler::exists($dir . $newLayoutName . '/' . $oldLayoutName . '.html')) {
            rename($dir . $newLayoutName . '/' . $oldLayoutName . '.html', $dir . $newLayoutName . '/' . $newLayoutName . '.html');
        }
    }

    /**
     * Update the con_mod, the field lastmodified
     *
     * @param int|false $timestamp Timestamp of last modification
     * @param int $layoutId Id of layout
     * @throws cDbException|cInvalidArgumentException|cException
     */
    public function setLastModified($timestamp, $layoutId)
    {
        $oLay = new cApiLayout(cSecurity::toInteger($layoutId));
        if ($oLay->isLoaded()) {
            $oLay->set('lastmodified', date('Y-m-d H:i:s', cSecurity::toInteger($timestamp)));
            $oLay->store();
        }
    }

    /**
     * Compare file change timestamp and the timestamp in ['tab']['lay'].
     * If file had changed make new code :conGenerateCodeForAllArtsUsingMod
     *
     * @throws cDbException|cInvalidArgumentException|cException
     */
    private function _compareFileAndLayoutTimestamp()
    {
        // get all layouts from client
        $sql = sprintf(
            "SELECT UNIX_TIMESTAMP(lastmodified) AS lastmodified, alias, name, description, idlay FROM `%s` WHERE idclient = %d",
            cDb::getTableName('lay'),
            $this->_client
        );
        $dir = $this->_cfgClient[$this->_client]['layout']['path'];

        $db = cRegistry::getDb();
        $db->query($sql);
        while ($db->nextRecord()) {
            $lastmodified = $db->f('lastmodified');

            // exist layout directory
            if (cDirHandler::exists($dir . $db->f('alias') . '/')) {
                if (cFileHandler::exists($dir . $db->f('alias') . '/' . $db->f('alias') . '.html')) {
                    $lastmodifiedLayout = filemtime($dir . $db->f('alias') . '/' . $db->f('alias') . '.html');

                    // update layout data
                    if ($lastmodified < $lastmodifiedLayout) {
                        // update field lastmodified in table lay
                        $this->setLastModified($lastmodifiedLayout, $db->f('idlay'));
                        $layoutHandler = new cLayoutHandler($db->f('idlay'), ' ', $this->_cfg, $this->_lang);
                        // Update CODE table
                        conGenerateCodeForAllartsUsingLayout($db->f('idlay'));
                        $this->_outputMessage['info'][] = i18n("Layout synchronization successful: ") . $db->f('name');
                    }
                }
            } else {
                $oLayout = new cApiLayout($db->f('idlay'));

                // is layout in use
                $layoutHandler = new cLayoutHandler($db->f('idlay'), '', $this->_cfg, $this->_lang);
                if ($oLayout->isInUse()) {
                    // make layout file
                    $layoutHandler->saveLayout('');
                    $this->_outputMessage['info'][] = i18n("Layout synchronization successful, created: ") . $db->f('name');
                } elseif ($layoutHandler->eraseLayout()) {
                    // if not in use delete layout
                    layDeleteLayout($db->f('idlay'));
                    $this->_outputMessage['info'][] = i18n("Layout synchronization successful, deleted: ") . $db->f('name');
                } else {
                    $this->_outputMessage['error'][] = i18n("Synchronization failed could not delete layout: ") . $db->f('name');
                }
            }
        }
    }

    /**
     */
    private function _showOutputMessage()
    {
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
     * Synchronize the Layout directory with the lay-table und the lay-table with directory.
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function synchronize(): bool
    {
        // update file and layout
        $this->_compareFileAndLayoutTimestamp();

        // get the path to clients layouts
        $dir = $this->_cfgClient[$this->_client]['layout']['path'];

        // is/exist directory
        if (!cDirHandler::exists($dir)) {
            return false;
        }

        $files = cDirHandler::read($dir);
        if ($files === false) {
            return false;
        }

        foreach ($files as $file) {
            // skip dirs to exclude
            // @todo should use setting for dirs to exclude
            if (cFileHandler::fileNameBeginsWithDot($file)) {
                continue;
            }

            // skip entries that are no directories
            if (false === cDirHandler::exists($dir . $file . '/')) {
                continue;
            }

            $newFile = cString::toLowerCase(cString::cleanURLCharacters($file));

            if ($newFile == $file) {
                // dir is ok
                $this->_addOrUpdateLayout($dir, $file, $newFile);
                continue;
            }

            // dir not ok (with not allowed characters)
            if (cDirHandler::exists($dir . $newFile) && cString::toLowerCase($file) != $newFile) {
                // exist the new dir name after clean?
                // make new dirname
                $newDirName = $newFile . cString::getPartOfString(md5(time() . rand(0, time())), 0, 4);
                // rename
                if ($this->_renameFileAndDir($dir, $file, $newDirName)) {
                    $this->_addOrUpdateLayout($dir, $file, $newDirName);
                }

                continue;
            }

            // $newFile (dir) not exist
            // rename dir old
            if ($this->_renameFileAndDir($dir, $file, $newFile)) {
                $this->_addOrUpdateLayout($dir, $file, $newFile);
            }
        }

        $this->_showOutputMessage();

        return true;
    }
}
