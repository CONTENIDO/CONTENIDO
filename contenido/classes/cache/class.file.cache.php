<?php

/**
 * This file contains the file cache class.
 *
 * @package    Core
 * @subpackage Cache
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains functions for the CONTENIDO file cache.
 *
 * @package    Core
 * @subpackage Cache
 */
class cFileCache
{

    /**
     * Options for the cache.
     *
     * @var array
     */
    protected $_options = [];

    /**
     * Constructor to create an instance of this class.
     *
     * @param array $options [optional]
     *         array with options for the cache (optional, default: empty array)
     */
    public function __construct(array $options = [])
    {
        $this->setOptions($options);
    }

    /**
     * Setter for the cache options.
     *
     * Validates incoming options and sets the default of the missing options.
     *
     * @param array $options Array with option
     */
    public function setOptions(array $options)
    {
        // complete all options
        if (isset($options['cacheDir']) && cString::getPartOfString($options['cacheDir'], -1) !== '/') {
            $options['cacheDir'] .= '/';
        }

        if (!isset($options['cacheDir'])) {
            $options['cacheDir'] = '/tmp/';
        }

        if (isset($options['lifeTime']) && isset($options['lifetime']) === false) {
            $options['lifetime'] = $options['lifeTime'];
        }

        if (!isset($options['lifetime'])) {
            $options['lifetime'] = 3600;
        }

        if (!isset($options['fileNamePrefix'])) {
            $options['fileNamePrefix'] = 'cache_';
        }

        if (!isset($options['fileExtension'])) {
            $options['fileExtension'] = 'tmp';
        }

        if (!isset($options['fileNameProtection'])) {
            $options['fileNameProtection'] = false;
        }

        $this->_options = $options;
    }

    /**
     * Generates the filename based on set options.
     *
     * @param string $id Cache ID
     * @param string $group [optional] Cache group
     * @return string Filename
     */
    public function generateFileName($id, $group = ''): string
    {
        $id = $this->_options['fileNameProtection'] === true ? md5($id) : $id;
        if ($group != '') {
            $groupName = ($this->_options['fileNameProtection'] === true ? md5($group) : $group) . '_';
            $group = $groupName . '_';
        }

        return $this->_options['fileNamePrefix'] . $group . $id . '.' . $this->_options['fileExtension'];
    }

    /**
     * Validates the caching directory and throws exception on error.
     *
     * @throws cInvalidArgumentException
     */
    protected function _validateDirectory()
    {
        $directory = $this->_options['cacheDir'];
        if ($directory == '') {
            throw new cInvalidArgumentException('The caching directory is empty.');
        }

        if (is_dir($directory) === false) {
            throw new cInvalidArgumentException('The specified caching directory is not a directory.');
        }

        if (cFileHandler::writeable($directory) === false) {
            throw new cInvalidArgumentException('The caching directory is not writable.');
        }
    }

    /**
     * Returns full destination to the cached file.
     *
     * @param string $id Cache ID
     * @param string $group [optional] Cache group
     * @return string Full filename
     * @throws cInvalidArgumentException
     */
    public function getDestination($id, $group = '')
    {
        $this->_validateDirectory();

        $directory = $this->_options['cacheDir'];
        $filename = $this->generateFileName($id, $group);

        return $directory . $filename;
    }

    /**
     * Return content of a specific cache stored in filesystem.
     *
     * If not cached, false is returned.
     *
     * @param string $id Cache ID
     * @param string $group [optional] Cache group
     * @return bool|string Content or false
     * @throws cInvalidArgumentException
     */
    public function get($id, $group = '')
    {
        $data = false;

        $destination = $this->getDestination($id, $group);

        if (cFileHandler::exists($destination) === false) {
            return false;
        }

        $refreshTime = ($this->_options['lifetime'] == 0) ? 0 : time() - (int)$this->_options['lifetime'];

        clearstatcache();
        $info = cFileHandler::info($destination);
        $lastModifyTime = $info['mtime'];

        if ($lastModifyTime > $refreshTime) {
            $data = cFileHandler::read($destination);
        }

        return $data;
    }

    /**
     * Saves the content of a cache in filesystem.
     *
     * @param string $data Data to save
     * @param string $id Cache ID
     * @param string $group [optional] Cache group
     * @return bool Success state
     * @throws cInvalidArgumentException
     */
    public function save($data, $id, $group = ''): bool
    {
        return cFileHandler::write($this->getDestination($id, $group), $data);
    }

    /**
     * Removes cache from filesystem.
     *
     * @param string $id Cache ID
     * @param string $group [optional] Cache group
     * @return bool Success state
     * @throws cInvalidArgumentException
     */
    public function remove($id, $group = ''): bool
    {
        $destination = $this->getDestination($id, $group);
        if (cFileHandler::exists($destination) === false) {
            return false;
        }

        return cFileHandler::remove($this->getDestination($id, $group));
    }

    /**
     * Generates an ID for the given variables.
     *
     * @param mixed $variables Variables to generate a ID for
     * @return string Generated ID
     */
    public function generateID($variables): string
    {
        return md5(serialize($variables));
    }

}
