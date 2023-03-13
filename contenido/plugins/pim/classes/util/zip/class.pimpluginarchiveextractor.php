<?php

/**
 * This file contains the Extractor for plugin archive files
 *
 * @package    Plugin
 * @subpackage PluginManager
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Extractor for plugin archive files
 *
 * @package    Plugin
 * @subpackage PluginManager
 * @author     Frederic Schneider
 */
class PimPluginArchiveExtractor
{

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
     * @param string $source path to the temp directory
     * @param string $filename name of zip archive
     *
     * @throws cException if the source file does not exist or zip archive
     *      could not be opened
     */
    public function __construct($source, $filename)
    {
        $cfg = cRegistry::getConfig();

        // initializing ziparchive
        $this->_extractor = new ZipArchive();

        // path to temp directory
        $this->tempDir = $source;

        // temp directory with zip archive
        $this->_source = (string) $source . (string) $filename;

        if (file_exists($source)) {
            // generate absolute path to the plugin manager directory
            $this->_absPath = cRegistry::getBackendPath() . $cfg['path']['plugins'] . 'pim' . DIRECTORY_SEPARATOR;

            // open the zip archive
            $result = $this->_extractor->open($this->_source);
            if ($result !== true) {
                $message = ['Could not open zip archive `' . $filename . '`.'];
                $resultMsg = $this->_openErrorToMessage($result);
                if (!empty($resultMsg)) {
                    $message[] = 'Reason: ' .$resultMsg;
                }
                throw new cException(implode(' ', $message));
            }
            $this->_validateArchive($filename);
        } else {
            throw new cException('Source file does not exists');
        }
    }

    public function closeArchive()
    {
        $this->_extractor->close();
    }

    /**
     * Sets the path where the extractor extracts the archive files
     *
     * @param string $destination string
     *
     * @throws cException if the destination path can not set (directory is not writable)
     * @throws cException if the defined destination already exists
     */
    public function setDestinationPath($destination)
    {
        if (!is_dir($destination)) {
            $makeDirectory = mkdir($destination, cDirHandler::getDefaultPermissions());
            if (!$makeDirectory) {
                throw new cException('Can not set destination path: directory is not writable');
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
    public function extractArchive()
    {
        if ($this->_destination != '') {
            $this->_extractor->extractTo($this->_destination);
        } else {
            throw new cException('Extraction failed: no destination path set');
        }
    }

    /**
     * Extracts a specific file from archive and return its content to use it in
     * a variable
     *
     * @param string $filename
     * @param bool $content [optional] whether to return the content or just the
     *            dir and filename of the extracted file
     * @return string content of extracted file or dir and filename of extracted File
     */
    public function extractArchiveFileToVariable($filename, $content = true)
    {
        $filename = (string) $filename;
        $this->_extractor->extractTo($this->tempDir, $filename);

        if ($content) {
            return file_get_contents($this->tempDir . $filename);
        } else {
            return $this->tempDir . $filename;
        }
    }

    /**
     * Destroy temporary plugin files (plugin.xml, plugin_install.sql and files
     * at CONTENIDO temp dir)
     *
     * @throws cInvalidArgumentException
     */
    public function destroyTempFiles()
    {
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

    /**
     * Validates the archive, following cases will lead to an error.
     * - Archive is empty
     * - Archive does not contain the plugin.xml
     * - Archive contains the plugin folder, e.g. plugin_name.zip contains `plugin_name`
     *
     * @param $filename
     * @return void
     * @throws cException
     */
    private function _validateArchive($filename)
    {
        $filenameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);

        if ($this->_extractor->numFiles === 0) {
            throw new cException(
                sprintf('Archive validation failed: Empty archive `%s`.', $filename)
            );
        } elseif ($this->_extractor->locateName('plugin.xml') === false) {
            throw new cException(
                sprintf('Archive validation failed: No plugin.xml found in archive `%s`.', $filename)
            );
        } elseif ($this->_extractor->getNameIndex(0) === $filenameWithoutExt) {
            throw new cException(
                sprintf(
                    'Archive validation failed: Archive `%s` contains plugin folder `%s`.',
                    $filename, $filenameWithoutExt
                )
            );
        }
    }

    /**
     * Returns the error message for a ZipArchive::open() result.
     *
     * @param int|bool $result The result from a ZipArchive::open() call
     * @return string The error message or empty string.
     */
    private function _openErrorToMessage($result)
    {
        // We may not need all the error codes, they are here for the sake of completeness.
        switch ($result) {
            case ZipArchive::ER_EXISTS:
                return 'File already exists.';
            case ZipArchive::ER_INCONS:
                return 'Zip archive inconsistent.';
            case ZipArchive::ER_INVAL:
                return 'Invalid argument.';
            case ZipArchive::ER_MEMORY:
                return 'Malloc failure.';
            case ZipArchive::ER_NOENT:
                return 'No such file.';
            case ZipArchive::ER_NOZIP:
                return 'Not a zip archive.';
            case ZipArchive::ER_OPEN:
                return 'Can\'t open file.';
            case ZipArchive::ER_READ:
                return 'Read error.';
            case ZipArchive::ER_SEEK:
                return 'Seek error.';
            default:
                return '';
        }
    }

}
