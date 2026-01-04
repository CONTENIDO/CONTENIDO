<?php

/**
 * This file contains the layout handler class.
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
 * This class controls all layouts in filesystem.
 *
 * @package    Core
 * @subpackage LayoutHandler
 */
class cLayoutHandler
{

    /**
     * @var int The ID of the layout
     */
    protected $_layoutId = 0;

    /**
     * @var string The code of the layout
     */
    protected $_layoutCode = '';

    /**
     *
     * @var cDb
     */
    protected $_db = NULL;

    /**
     * @var string Layout name
     */
    protected $_layoutName = '';

    /**
     * @var array The contenido cfg
     */
    protected $_cfg = [];

    /**
     * @var string Encoding of the page
     */
    protected $_encoding;

    /**
     * @var string Layout path ([layout_path].layoutName/)
     */
    protected $_layoutPath = '';

    /**
     * @var string Main path of layouts ([layout_path].layouts).
     */
    protected $_layoutMainPath = '';

    /**
     * @var string File name of the layout ([layoutName].html
     */
    protected $_fileName = '';

    /**
     * Constructor to create an instance of this class.
     *
     * @param int $layoutId
     * @param string $layoutCode
     * @param array $cfg The CONTENIDO configuration array
     * @param int $lang
     * @param ?cDb $db Database object
     * @throws cDbException|cInvalidArgumentException
     */
    public function __construct($layoutId = 0, $layoutCode = '', array $cfg = [], $lang = 0, ?cDb $db = null)
    {
        if ($db === NULL) {
            $db = cRegistry::getDb();
        }

        $this->_layoutId = $layoutId;
        $this->_db = $db;
        $this->init($layoutId, $layoutCode, $cfg, $lang);
    }

    /**
     * Get method for Layout path
     */
    public function _getLayoutPath(): string
    {
        return $this->_layoutPath;
    }

    /**
     * Get method for Filename
     *
     * @return string
     */
    public function _getFileName()
    {
        return $this->_fileName;
    }

    /**
     * Look in layout directory if layout [$layoutAlias] directory exists
     *
     * @param string $layoutAlias
     * @param array $cfgClient
     * @param int $client
     * @return bool true if file exist
     */
    static function existLayout(string $layoutAlias, array $cfgClient, $client): bool
    {
        $file = $cfgClient[$client]['layout']['path'] . $layoutAlias . '/';

        return cFileHandler::exists($file);
    }

    /**
     * Init all vars for the class
     *
     * @param int $layoutId
     * @param string $layoutCode
     * @param array $cfg The CONTENIDO configuration array
     * @param int $language
     *
     * @throws cDbException|cInvalidArgumentException
     */
    public function init($layoutId, $layoutCode, $cfg, $language)
    {
        $this->_layoutCode = $layoutCode;
        $this->_cfg = $cfg;

        // set encoding
        $this->_setEncoding((int) $language);

        if ((int)$layoutId == 0) {
            return;
        }

        global $cfgClient, $client;

        $cApiLayout = new cApiLayout($layoutId);

        if (true === $cApiLayout->isLoaded() && is_array($cfgClient) && (int)$client > 0) {
            $this->_layoutName = $cApiLayout->get('alias');
            $this->_layoutMainPath = $cfgClient[$client]['layout']['path'];
            $this->_layoutPath = $this->_layoutMainPath . $this->_layoutName . '/';
            $this->_fileName = $this->_layoutName . '.html';

            // make directories for layout
            $this->_makeDirectories();
        }
    }

    /**
     * Get the layout name
     *
     * @return string
     */
    public function getLayoutName()
    {
        return $this->_layoutName;
    }

    /**
     * Init class vars with values, only use for setup or upgrade
     *
     * @throws cInvalidArgumentException
     */
    public function initWithDbObject(cDb $db)
    {
        $cfgClient = cRegistry::getClientConfig();
        $clientId = (int) $db->f('idclient');

        $this->_layoutCode = $db->f('code');
        $this->_layoutName = $db->f('alias');
        $this->_layoutMainPath = $cfgClient[$clientId]['layout']['path'];
        $this->_layoutPath = $this->_layoutMainPath . $this->_layoutName . '/';
        $this->_fileName = $this->_layoutName . '.html';

        // make directories for layout
        $this->_makeDirectories();
    }

    /**
     * Make all directories for layout.
     * Main directory and Layout directory
     *
     * @return bool true if successfully
     * @throws cInvalidArgumentException
     */
    private function _makeDirectories(): bool
    {
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
     * @return bool true if successfully
     * @throws cInvalidArgumentException
     */
    private function _makeDirectory(string $directory): bool
    {
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
     * @throws cDbException|cException
     */
    private function _setEncoding(int $languageId)
    {
        if ($languageId <= 0) {
            $clientId = cRegistry::getClientId();

            $clientsLangColl = new cApiClientLanguageCollection();
            $clientLanguages = $clientsLangColl->getLanguagesByClient($clientId);
            sort($clientLanguages);

            if (isset($clientLanguages[0]) && (int)$clientLanguages[0] != 0) {
                $languageId = $clientLanguages[0];
            }
        }

        $cApiLanguage = new cApiLanguage($languageId);
        $this->_encoding = $cApiLanguage->get('encoding');
    }

    /**
     * Can write/create a file.
     *
     * @param string $fileName File name
     * @param string $directory Directory where is the file
     */
    public function isWritable(string $fileName, string $directory): bool
    {
        if (cFileHandler::exists($fileName)) {
            if (!cFileHandler::writeable($fileName)) {
                return false;
            }
        } elseif (!cFileHandler::writeable($directory)) {
            return false;
        }

        return true;
    }

    /**
     * Save Layout
     *
     * @param string $layoutCode [optional]
     * @throws cInvalidArgumentException
     */
    public function saveLayout($layoutCode = ''): bool
    {
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
     * @throws cInvalidArgumentException
     */
    public function saveLayoutByUpgrade($layoutCode = ''): bool
    {
        // if file exist dont overwrite it
        if (cFileHandler::exists($this->_layoutPath . $this->_fileName)) {
            return true;
        }

        return $this->_save($layoutCode);
    }

    /**
     * @param string $layoutCode [optional]
     * @throws cDbException|cException|cInvalidArgumentException
     */
    private function _save($layoutCode = ''): bool
    {
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
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function eraseLayout(): bool
    {
        $layoutVersion = new cVersionLayout(
            $this->_layoutId,
            cRegistry::getConfig(),
            cRegistry::getClientConfig(),
            cRegistry::getDb(),
            cRegistry::getClientId(),
            cRegistry::getArea(),
            cRegistry::getFrame()
        );
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
     */
    public function rename($old, $new): bool
    {
        // try to rename the dir
        $newPath = $this->_layoutMainPath . $new . '/';

        $newFileName = $new . '.html';

        if (!rename($this->_layoutPath, $newPath)) {
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
     * @return string|bool Content or false
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function getLayoutCode()
    {
        // cant read it dont exist file
        if (!is_readable($this->_layoutPath . $this->_fileName)) {
            return false;
        }

        if (($content = cFileHandler::read($this->_layoutPath . $this->_fileName)) === FALSE) {
            return false;
        } else {
            // convert
            $fileEncoding = getEffectiveSetting('encoding', 'file_encoding', 'UTF-8');
            return iconv($fileEncoding, $this->_encoding . '//IGNORE', $content);
        }
    }

    /**
     * Save all layout in file system.
     * Use it for upgrade.
     *
     * @throws cException if the layout could not be saved
     */
    public static function upgrade(cDb $db, array $cfg, int $clientId)
    {
        // get name of layout and frontendpath
        if (!$db->query(
            "SELECT * FROM `%s` WHERE `idclient` = %d",
            cDb::getTableName('lay'),
            $clientId
        )) {
            return;
        }

        while ($db->nextRecord()) {
            // init class var for save
            $layout = new cLayoutHandler();
            $layout->initWithDbObject($db);
            if (!$layout->saveLayoutByUpgrade($db->f('code'))) {
                throw new cException('Can not save layout.' . print_r($layout, true));
            }
        }

        // all layouts are saved, so remove the code field from _lay
        $db->query(
            "UPDATE `%s` SET `code` = '' WHERE `idclient` = %d",
            cDb::getTableName('lay'),
            $clientId
        );
    }
}
