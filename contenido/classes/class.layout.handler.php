<?php
/**
 * This file contains the layout handler class.
 *
 * @package Core
 * @subpackage LayoutHandler
 * @author Rusmir Jusufovic
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class controls all layouts in filesystem.
 *
 * @package Core
 * @subpackage LayoutHandler
 */
class cLayoutHandler {

    /**
     * The ID of the layout
     *
     * @var int
     */
    protected $_layoutId = 0;

    /**
     * The code of the layout
     *
     * @var string
     */
    protected $_layoutCode = '';

    /**
     *
     * @var cDb
     */
    protected $_db = NULL;

    /**
     * Layout name
     *
     * @var string
     */
    protected $_layoutName = '';

    /**
     * The contenido cfg
     *
     * @var array
     */
    protected $_cfg = array();

    /**
     * Encoding of the page
     *
     * @var string
     */
    protected $_encoding;

    /**
     * Layout path
     * [layout_path].layoutName/
     *
     * @var string
     */
    protected $_layoutPath = '';

    /**
     * Main path of layouts.
     * [layout_path].layouts
     *
     * @var string
     */
    protected $_layoutMainPath = '';

    /**
     * File name of the layout ([layoutname].html
     *
     * @var string
     */
    protected $_fileName = '';

    /**
     * Constructor to create an instance of this class.
     *
     * @param int    $layoutId
     * @param string $layoutCode [optional]
     * @param array  $cfg        [optional]
     * @param int    $lang       [optional]
     * @param cDb    $db         [optional]
     *                           CONTENIDO database object
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function __construct($layoutId = 0, $layoutCode = '', array $cfg = array(), $lang = 0, cDb $db = NULL) {
        if ($db === NULL) {
            $db = cRegistry::getDb();
        }

        $this->_layoutId = $layoutId;
        $this->_db = $db;
        $this->init($layoutId, $layoutCode, $cfg, $lang);
    }

    /**
     * Get method for Layout path
     *
     * @return string
     */
    public function _getLayoutPath() {
        return $this->_layoutPath;
    }

    /**
     * Get method for Filename
     *
     * @return string
     */
    public function _getFileName() {
        return $this->_fileName;
    }

    /**
     * Look in layout directory if layout [$layoutAlias] directory exists
     *
     * @param string $layoutAlias
     * @param array $cfgClient
     * @param int $client
     * @return bool
     *         true if file exist
     */
    static function existLayout($layoutAlias, $cfgClient, $client) {
        $file = $cfgClient[$client]['layout']['path'] . $layoutAlias . '/';
        return cFileHandler::exists($file);
    }

    /**
     * Init all vars for the class
     *
     * @param int    $layoutId
     * @param string $layoutCode
     * @param array  $cfg
     * @param int    $language
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function init($layoutId, $layoutCode, $cfg, $language) {
        $this->_layoutCode = $layoutCode;
        $this->_cfg = $cfg;

        // set encoding
        $this->_setEncoding($language);

        if ((int) $layoutId == 0) {
            return;
        }

        global $cfgClient, $client;

        $cApiLayout = new cApiLayout($layoutId);

        if (true === $cApiLayout->isLoaded() && is_array($cfgClient) && (int) $client > 0) {
            $this->_layoutName = $cApiLayout->get('alias');
            $this->_layoutMainPath = $cfgClient[$client]['layout']['path'];
            $this->_layoutPath = $this->_layoutMainPath . $this->_layoutName . '/';
            $this->_fileName = $this->_layoutName . '.html';

            // make directoryies for layout
            $this->_makeDirectories();
        }
    }

    /**
     * Get the layout name
     *
     * @return string
     *         layoutname
     */
    public function getLayoutName() {
        return $this->_layoutName;
    }

    /**
     * Init class vars with values, only use for setup or upgrade
     *
     * @param cDb $dbObject
     *         CONTENIDO database object
     * 
     * @throws cInvalidArgumentException
     */
    public function initWithDbObject($dbObject) {
        global $cfgClient;

        $this->_layoutCode = $dbObject->f('code');
        $this->_layoutName = $dbObject->f('alias');
        $this->_layoutMainPath = $cfgClient[$dbObject->f('idclient')]['layout']['path'];
        $this->_layoutPath = $this->_layoutMainPath . $this->_layoutName . '/';
        $this->_fileName = $this->_layoutName . '.html';

        // make directories for layout
        $this->_makeDirectories();
    }

    /**
     * Make all directories for layout.
     * Main directory and Layout directory
     *
     * @return bool
     *         true if successfully
     * 
     * @throws cInvalidArgumentException
     */
    private function _makeDirectories() {
        if ($this->_makeDirectory($this->_layoutMainPath)) {
            if ($this->_makeDirectory($this->_layoutPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Make directory
     *
     * @param string $directory
     *
     * @return bool
     *         true if succssesfully
     * 
     * @throws cInvalidArgumentException
     */
    private function _makeDirectory($directory) {
        if (is_dir($directory)) {
            $success = true;
        } else {
            $success = mkdir($directory);
            if ($success) {
                cDirHandler::setDefaultPermissions($directory);
            }
        }

        return $success;
    }

    /**
     * Save encoding from language.
     *
     * @param int $lang
     *
     * @throws cDbException
     */
    private function _setEncoding($lang) {
        if ((int) $lang == 0) {
            $clientId = cRegistry::getClientId();

            $clientsLangColl = new cApiClientLanguageCollection();
            $clientLanguages = $clientsLangColl->getLanguagesByClient($clientId);
            sort($clientLanguages);

            if (isset($clientLanguages[0]) && (int) $clientLanguages[0] != 0) {
                $languageId = $clientLanguages[0];
            }
        } else {
            $languageId = $lang;
        }

        $cApiLanguage = new cApiLanguage($languageId);
        $encoding = $cApiLanguage->get('encoding');

        $this->_encoding = $encoding;
    }

    /**
     * Can write/create a file
     *
     * @param string $fileName
     *         file name
     * @param string $directory
     *         directory where is the file
     * @return bool
     *         true on success else false
     */
    public function isWritable($fileName, $directory) {
        if (cFileHandler::exists($fileName)) {
            if (!is_writable($fileName)) {
                return false;
            }
        } else {
            if (!is_writable($directory)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Save Layout
     *
     * @param string $layoutCode [optional]
     *                           
     * @return bool
     * 
     * @throws cInvalidArgumentException
     */
    public function saveLayout($layoutCode = '') {
        $fileName = $this->_layoutPath . $this->_fileName;

        if (!$this->isWritable($fileName, $this->_layoutPath)) {
            return false;
        }

        return $this->_save($layoutCode);
    }

    /**
     * Save the layout only if layout doesn't exist in filesystem!
     * Use it for upgrade!
     *
     * @param string $layoutCode [optional]
     *                           
     * @return bool
     * 
     * @throws cInvalidArgumentException
     */
    public function saveLayoutByUpgrade($layoutCode = '') {
        // if file exist dont overwirte it
        if (cFileHandler::exists($this->_layoutPath . $this->_fileName)) {
            return true;
        }

        return $this->_save($layoutCode);
    }

    /**
     *
     * @param string $layoutCode [optional]
     *                           
     * @return bool
     * 
     * @throws cInvalidArgumentException
     */
    private function _save($layoutCode = '') {
        if ($layoutCode == '') {
            $layoutCode = $this->_layoutCode;
        }

        // exist layout path
        if (!is_dir($this->_layoutPath)) {
            return false;
        }

        // convert
        $fileEncoding = getEffectiveSetting('encoding', 'file_encoding', 'UTF-8');
        $layoutCode = cString::recodeString($layoutCode, $this->_encoding, $fileEncoding);

        $save = cFileHandler::write($this->_layoutPath . $this->_fileName, $layoutCode);

        return (cString::getStringLength($layoutCode) == 0 && $save == 0) || $save > 0;
    }

    /**
     * Removes this layout from the filesystem.
     * Also deletes the version files.
     *
     * @return bool
     *         true on success or false on failure
     * 
     * @throws cInvalidArgumentException
     */
    public function eraseLayout() {
        global $area, $frame;
        $cfg = cRegistry::getConfig();
        $cfgClient = cRegistry::getClientConfig();
        $db = cRegistry::getDb();
        $client = cRegistry::getClientId();

        $layoutVersion = new cVersionLayout($this->_layoutId, $cfg, $cfgClient, $db, $client, $area, $frame);
        $success = true;
        if (count($layoutVersion->getRevisionFiles()) > 0 && !$layoutVersion->deleteFile()) {
            $success = false;
        }

        return $success && cDirHandler::recursiveRmdir($this->_layoutPath);
    }

    /**
     * Rename the Layout directory and layout file
     *
     * @param string $old
     * @param string $new
     * @return bool
     */
    public function rename($old, $new) {
        // try to rename the dir
        $newPath = $this->_layoutMainPath . $new . '/';

        $newFileName = $new . '.html';

        if (rename($this->_layoutPath, $newPath) == FALSE) {
            return false;
        }

        // if file input exist rename it
        if (!cFileHandler::exists($newPath . $this->_fileName)) {
            return false;
        }

        if (!rename($newPath . $this->_fileName, $newPath . $newFileName)) {
            return false;
        }

        $this->_layoutName = $new;
        $this->_layoutPath = $this->_layoutMainPath . $this->_layoutName . '/';
        $this->_fileName = $this->_layoutName . '.html';

        return true;
    }

    /**
     * Get the contents of the file
     *
     * @return string|bool
     *         content or false
     * 
     * @throws cInvalidArgumentException
     */
    public function getLayoutCode() {
        // cant read it dont exist file
        if (!is_readable($this->_layoutPath . $this->_fileName)) {
            return false;
        }

        if (($content = cFileHandler::read($this->_layoutPath . $this->_fileName)) === FALSE) {
            return false;
        } else {
            // convert
            $fileEncoding = getEffectiveSetting('encoding', 'file_encoding', 'UTF-8');
            $content = iconv($fileEncoding, $this->_encoding . '//IGNORE', $content);
            return $content;
        }
    }

    /**
     * Save all layout in file system.
     * Use it for upgrade.
     *
     * @param cDb $adb
     *         CONTENIDO database object
     * @param array $cfg
     *         CONTENIDO config array
     * @param int $clientId
     * 
     * @throws cException if the layout could not be saved
     */
    public static function upgrade($adb, $cfg, $clientId) {
        // get name of layout and frontendpath
        if (!$adb->query("SELECT * FROM `%s` WHERE idclient='%s'", $cfg['tab']['lay'], $clientId)) {
            return;
        }

        while ($adb->nextRecord()) {
            // init class var for save
            $layout = new cLayoutHandler();
            $layout->initWithDbObject($adb);
            if ($layout->saveLayoutByUpgrade($adb->f('code')) == false) {
                throw new cException('Can not save layout.' . print_r($layout, true));
            }
        }

        // all layouts are saved, so remove the code field from _lay
        $sql = sprintf("UPDATE %s SET code = '' WHERE idclient='%s'", $cfg['tab']['lay'], $clientId);
        $adb->query($sql);
    }
}
