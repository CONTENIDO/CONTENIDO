<?php
/**
 * Extractor for plugin archive files
 *
 * @package plugin
 * @subpackage Plugin Manager
 * @version SVN Revision $Rev:$
 * @author Frederic Schneider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}
class PimPluginArchiveExtractor {

    /**
     * The extractor initializer
     *
     * @var integer
     */
    protected $_extractor = 0;

    /**
     * The temp dir
     *
     * @var string
     */
    public $tempDir = '';

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
     * @access public
     * @param $sSource string
     * @param $sFilename string
     * @throws cException if the source file does not exists
     * @return void
     */
    public function __construct($sSource, $sFilename) {
        $cfg = cRegistry::getConfig();

        // initialzing ziparchive
        $this->_extractor = new ZipArchive();

        // path to temp dir
        $this->tempDir = $sSource;

        // temp dir with zip archive
        $this->_source = (string) $sSource . (string) $sFilename;

        if (file_exists($sSource)) {
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
     * @access public
     * @param $destination string
     * @throws cException if the destination path can not set (directory is not
     *         writable)
     * @throws cException if the defined destination already exists
     * @return void
     */
    public function setDestinationPath($destination) {
        if (!is_dir($destination)) {
            $makeDirectory = mkdir($destination, 0777);
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
     * @access public
     * @throws cException if the extraction failed
     * @return void
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
     * @access public
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
     * Destory temporary plugin files (plugin.xml, plugin.sql and files at
     * CONTENIDO temp dir)
     *
     * @access public
     * @return void
     */
    public function destroyTempFiles() {

        // remove plugin.xml if exists
        if (cFileHandler::exists($this->tempDir . 'plugin.xml')) {
            cFileHandler::remove($this->tempDir . 'plugin.xml');
        }

        // remove plugin.sql if exists
        if (cFileHandler::exists($this->tempDir . 'plugin.sql')) {
            cFileHandler::remove($this->tempDir . 'plugin.sql');
        }

        // remove temporary plugin dir if exists
        if (cFileHandler::exists($this->_source)) {
            cFileHandler::remove($this->_source);
        }
    }

}
