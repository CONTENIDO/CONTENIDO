<?php

/**
 * This file contains the static directory handler class.
 *
 * @package    Core
 * @subpackage Util
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class for directory handling.
 *
 * Provides functions for dealing with directories.
 *
 * @package    Core
 * @subpackage Util
 */
class cDirHandler
{

    /**
     * Default permissions for new directories.
     *
     * @see CON-2770
     * @var int
     */
    const DEFAULT_MODE = 0775;

    /**
     * Creates a new directory.
     *
     * @param string $pathname
     *         the name and path of the new dir
     * @param bool $recursive [optional]
     * @return bool
     *         Returns true on success or false on failure.
     */
    public static function create($pathname, $recursive = false): bool
    {
        // skip if dir already exists
        if (self::exists($pathname)) {
            return true;
        }
        // reset umask and store old umask
        $oldumask = umask(0);
        // calc mode from setting or default
        $mode = self::getDefaultPermissions();
        // create dir with given mode
        $success = mkdir($pathname, $mode, $recursive);
        // reset umask to old umask
        umask($oldumask);
        // return success
        return $success;
    }

    /**
     * Removes a directory from the filesystem.
     *
     * @param string $dirname
     *         The path to the directory
     *
     * @return bool
     *         Returns true on success or false on failure.
     *
     * @throws cInvalidArgumentException
     *         if the dir with the given dirname does not exist
     */
    public static function remove($dirname): bool
    {
        if (!self::exists($dirname)) {
            throw new cInvalidArgumentException('The directory ' . $dirname . ' could not be accessed because it does not exist.');
        }
        return rmdir($dirname);
    }

    /**
     * Moves a directory to another location.
     *
     * @param string $dirname
     *         The path and name of the directory
     * @param string $destination
     *         the destination. Note that the dir can also be renamed in the
     *         process of moving it
     *
     * @return bool
     *         Returns true on success or false on failure.
     *
     * @throws cInvalidArgumentException
     *         if the dir with the given dirname does not exist
     */
    public static function move($dirname, $destination): bool
    {
        if (!self::exists($dirname)) {
            throw new cInvalidArgumentException('The directory ' . $dirname . ' could not be accessed because it does not exist.');
        }

        $success = rename($dirname, $destination);

        if ($success) {
            self::setDefaultPermissions($destination);
        }

        return $success;
    }

    /**
     * Renames a directory.
     *
     * @param string $dirname
     *         the name and path of the dir
     * @param string $new_dirname
     *         the new name of the dir
     *
     * @throws cInvalidArgumentException
     */
    public static function rename($dirname, $new_dirname)
    {
        self::move($dirname, $new_dirname);
    }

    /**
     * Changes the permissions of a directory.
     *
     * @param string $dirname
     *         the name and path of the dir
     * @param int $mode
     *         the new access mode : php chmod needs octal value
     *
     * @return bool
     *         Returns true on success or false on failure.
     *
     * @throws cInvalidArgumentException
     *         if the dir with the given dirname does not exist
     */
    public static function chmod($dirname, $mode): bool
    {
        if (!cFileHandler::exists($dirname)) {
            throw new cInvalidArgumentException('The directory ' . $dirname . ' could not be accessed because it does not exist.');
        }
        // chmod needs octal value for correct execution.
        $mode = intval($mode, 8);
        return @chmod($dirname, $mode);
    }

    /**
     * Returns the default permissions for new directories.
     *
     * These can be configured using the setting "default_perms/directory" in "data/config/<ENV>/config.misc.php".
     * If no configuration can be found 0775 is assumed.
     *
     * @return int
     */
    public static function getDefaultPermissions(): int
    {
        $mode = cRegistry::getConfigValue('default_perms', 'directory', self::DEFAULT_MODE);

        return intval($mode, 8);
    }

    /**
     * Sets the permissions for the given directory to the default.
     *
     * @param string $dirname
     *         the name of the directory
     *
     * @return bool
     *         Returns true on success or false on failure.
     *
     * @throws cInvalidArgumentException
     */
    public static function setDefaultPermissions($dirname): bool
    {
        return self::chmod($dirname, self::getDefaultPermissions());
    }

    /**
     * Sets the permissions for the given directory to the default.
     *
     * @deprecated use setDefaultPermissions() instead
     * @param string $dirname
     *         the name of the directory
     *
     * @return bool
     *         Returns true on success or false on failure.
     *
     * @throws cInvalidArgumentException
     */
    public static function setDefaultDirPerms($dirname): bool
    {
        return self::setDefaultPermissions($dirname);
    }

    /**
     * Deletes a directory and all of its content.
     *
     * @param string $dirname
     *         the name of the directory which should be deleted
     *
     * @return bool
     *         Returns true on success or false on failure.
     *
     * @throws cInvalidArgumentException
     *         if dirname is empty
     */
    public static function recursiveRmdir($dirname): bool
    {
        if ($dirname == '') {
            throw new cInvalidArgumentException('Directory name must not be empty.');
        }

        // make sure $dirname ends with a slash
        if (cString::getPartOfString($dirname, -1) !== '/') {
            $dirname .= '/';
        }

        foreach (new DirectoryIterator($dirname) as $file) {
            if ($file != "." && $file != "..") {
                $file = $dirname . $file;
                if (is_dir($file)) {
                    self::recursiveRmdir($file);
                } else {
                    unlink($file);
                }
            }
        }

        return rmdir($dirname);
    }

    /**
     * Copies a directory and all of its subfolders.
     *
     * @param string $dirname
     *                     the name and path of the file
     * @param string $destination
     *                     the destination. Note that existing files get overwritten
     * @param int    $mode [optional; default as configured or 0775]
     *                     chmod mode
     *
     * @return bool
     *         true on success
     *
     * @throws cInvalidArgumentException
     *         if the file with the given filename does not exist
     */
    public static function recursiveCopy($dirname, $destination, $mode = null): bool
    {
        if (!self::exists($dirname)) {
            throw new cInvalidArgumentException('The directory ' . $dirname . ' could not be accessed because it does not exist.');
        }

        if (is_null($mode)) {
            $mode = self::getDefaultPermissions();
        }

        if (!cFileHandler::exists($destination)) {
            if (!mkdir($destination)) {
                return false;
            }
            if (!self::chmod($destination, $mode)) {
                return false;
            }
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirname), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $item) {
            // workaround for RecursiveDirectoryIterator::SKIP_DOTS, this was
            // not available in PHP 5.2
            if ($item->getFilename() == '.' || $item->getFilename() == '..') {
                continue;
            }

            if ($item->isDir()) {
                if (!mkdir($destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName())) {
                    return false;
                }
                if (!self::chmod($destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName(), $mode)) {
                    return false;
                }
            } else {
                if (!copy($item, $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName())) {
                    return false;
                }
                if (!self::chmod($destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName(), $mode)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Checks if a directory is empty.
     *
     * @param string $dir
     *         Name of the directory
     * @return bool
     *         true if the directory is empty
     */
    public static function isDirectoryEmpty($dir): bool
    {
        if (!is_readable($dir)) {
            return false;
        }
        if (is_dir($dir)) {
            if ($handle = opendir($dir)) {
                while (false !== ($entry = readdir($handle))) {
                    if ($entry != "." && $entry != "..") {
                        return false;
                    }
                }
            }
            closedir($handle);
        }
        return true;
    }

    /**
     * Reads the content from given directory.
     *
     * Optionally options are to read the directory recursive or to list only directories.
     *
     * @param string $dirname
     *         directory
     * @param bool $recursive [optional]
     *         read directory recursively
     * @param bool $dirOnly [optional]
     *         only read directories
     * @param bool $fileOnly [optional]
     *         only read files (ignored if $dirOnly is true)
     * @return array|bool
     *         array containing file names as string, false on error
     */
    public static function read($dirname, $recursive = false, $dirOnly = false, $fileOnly = false)
    {
        if (!self::exists($dirname)) {
            return false;
        }

        $dirContent = [];
        if ($recursive == false) {
            $dirHandle = opendir($dirname);
            $dirContent = [];
            while (false !== ($file = readdir($dirHandle))) {
                if (!cFileHandler::fileNameIsDot($file)) {

                    if ($dirOnly == true) { // get only directories

                        if (is_dir($dirname . $file)) {
                            $dirContent[] = $file;
                        }
                    // bugfix: is_dir only checked file name without path, thus returning everything most of the time
                    } elseif ($fileOnly === true) { // get only files

                        if (is_file($dirname . $file)) {
                            $dirContent[] = $file;
                        }
                    } else { // get everything
                        $dirContent[] = $file;
                    }
                }
            }
            closedir($dirHandle);
        } else {
            $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirname), RecursiveIteratorIterator::SELF_FIRST);
            foreach ($objects as $name => $file) {

                if (!cFileHandler::fileNameIsDot($file)) {
                    $fileName = str_replace("\\", "/", $file->getPathName());

                    if ($dirOnly === true && is_dir($fileName)) {
                        // get only directories
                        $dirContent[] = $fileName;

                    } elseif ($fileOnly === true && is_file($fileName)) {
                        // get only files
                        $dirContent[] = $fileName;

                    } else {
                        // get everything
                        $dirContent[] = $fileName;

                    }
                }
            }
        }

        return $dirContent;
    }

    /**
     * Checks if a directory exists.
     *
     * @param string $dirname
     *         the name and path of the directory
     * @return bool
     *         true if the directory exists
     */
    public static function exists($dirname): bool
    {
        return is_dir($dirname);
    }

    /**
     * Returns the size of a directory.
     *
     * AKA the combined file sizes of all files within it.
     * Note that this function uses filesize().
     * There could be problems with files that are larger than 2GiB.
     *
     * @param string $dirname
     *                          The directory name
     * @param bool   $recursive [optional]
     *                          true if all the subdirectories should be included in the calculation
     *
     * @return int|bool
     *                          false in case of an error or the size
     *
     * @throws cInvalidArgumentException
     */
    public static function getDirectorySize($dirname, $recursive = false)
    {
        $ret = 0;
        $files = self::read($dirname, $recursive, false, true);
        if ($files === false) {
            return false;
        }

        foreach ($files as $file) {
            $temp = cFileHandler::info($dirname . $file);
            $ret += $temp['size'];
        }

        return $ret;
    }

    /**
     * Checks if directory can be created in parent directory.
     *
     * @param $dirname
     * @return bool
     */
    public static function isCreatable($dirname)
    {
        if (cFileHandler::writeable($dirname) === true) {
            return true;
        } else {
            return false;
        }
    }
}
