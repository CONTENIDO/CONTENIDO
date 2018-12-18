<?php
/**
 * This file contains the Extractor for plugin archive files
 *
 * @package Plugin
 * @subpackage PluginManager
 * @author Frederic Schneider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Extractor for plugin archive files
 *
 * @package     Plugin
 * @subpackage  PluginManager
 * @author Frederic Schneider
 */
class PimPluginArchiveExtractor {

    /**
     * The extractor initializer
     *
     * @var int
     */
    protected $_extractor = 0;

    /**
     * The temp dir
     *
     * @var string
     */
    protected $tempDir = '';

    /**
     * The archive file
     *
     * @var string
     */
    protected $_source = '';

    /**
     * The destination path
     *
     * @var string
     */
    protected $_destination = '';

    /**
     * The absolute path
     *
     * @var string
     */
    protected $_absPath = '';

    /**
     * Constructor of ArchiveExtractor, load the file list
     *
     * @param $source string path to the temp directory
     * @param $filename string name of zip archive
     * @throws cException if the source file does not exists
     */
    public function __construct($source, $filename) {
        $cfg = cRegistry::getConfig();

        // initialzing ziparchive
        $this->_extractor = new ZipArchive();

        // path to temp directory
        $this->tempDir = $source;

        // temp directory with zip archive
        $this->_source = (string) $source . (string) $filename;

        if (file_exists($source)) {
            // generate absolute path to the plugin manager directory
            $this->_absPath = $cfg['path']['contenido'] . $cfg['path']['plugins'] . 'pim' . DIRECTORY_SEPARATOR;

            // open the zip archive
            $this->_extractor->open($this->_source);
        } else {
            throw new cException('Source file does not exists');
        }
    }

    public function closeArchive() {
        $this->_extractor->close();
    }

    /**
     * Sets the path where the extractor extracts the archive files
     *
     * @param $destination string
     * @throws cException if the destination path can not set (directory is not
     *         writable)
     * @throws cException if the defined destination already exists
     */
    public function setDestinationPath($destination) {
        if (!is_dir($destination)) {
            $makeDirectory = mkdir($destination, cDirHandler::getDefaultPermissions());
            if ($makeDirectory != true) {
                throw new cException('Can not set destination path: directoy is not writable');
            }
            $this->_destination = (string) $destination;
        } else {
            throw new cException('Destination already exists');
        }
    }

    /**
     * Extracts the whole archive
     *
     * @throws cException if the extraction failed
     */
    public function extractArchive() {
        if ($this->_destination != '') {
            $this->_extractor->extractTo($this->_destination);
        } else {
            throw new cException('Extraction failed: no destination path setted');
        }
    }

    /**
     * Extracts a specific file from archive and return its content to use it in
     * a variable
     *
     * @param $filename string
     * @param $content bool [optional] whether to return the content or just the
     *            dir and filename of the extracted file
     * @return string content of extracted file or dir and filename of extracted
     *         File
     */
    public function extractArchiveFileToVariable($filename, $content = true) {
        $filename = (string) $filename;
        $this->_extractor->extractTo($this->tempDir, $filename);

        if ($content) {
            return file_get_contents($this->tempDir . $filename);
        } else {
            return $this->tempDir . $filename;
        }
    }

    /**
     * Destory temporary plugin files (plugin.xml, plugin_install.sql and files
     * at CONTENIDO temp dir)
     */
    public function destroyTempFiles() {

        // remove plugin.xml if exists
        if (cFileHandler::exists($this->tempDir . 'plugin.xml')) {
            cFileHandler::remove($this->tempDir . 'plugin.xml');
        }

        // remove plugin_install.sql if exists
        if (cFileHandler::exists($this->tempDir . 'plugin_install.sql')) {
            cFileHandler::remove($this->tempDir . 'plugin_install.sql');
        }

        // remove temporary plugin dir if exists
        if (cFileHandler::exists($this->_source)) {
            cFileHandler::remove($this->_source);
        }
    }

}
