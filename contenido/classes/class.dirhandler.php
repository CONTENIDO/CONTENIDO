<?php
/**
 * This file contains the the static directory handler class.
 *
 * @package Core
 * @subpackage Util
 * @version SVN Revision $Rev:$
 *
 * @author Frederic Schneider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class for directory handling.
 * Provides functions for dealing with directories.
 *
 * @package Core
 * @subpackage Util
 */
class cDirHandler {

    /**
     * Creates a new dir.
     *
     * @param string $pathname
     *         the name and path of the new dir
     * @param bool $recursive
     * @return bool
     *         Returns true on success or false on failure.
     */
    public static function create($pathname, $recursive = false) {
        // skip if dir already exists
        if (self::exists($pathname)) {
            return true;
        }
        // reset umask and store old umask
        $oldumask = umask(0);
        // calc mode from setting or default
        $mode = cRegistry::getConfigValue('default_perms', 'directory', 0777);
        // create dir with given mode
        $success = mkdir($pathname, $mode, $recursive);
        // reset umask to old umask
        umask($oldumask);
        // return success
        return $success;
    }

    /**
     * Removes a dir from the filesystem
     *
     * @param string $dirname
     *         The path to the directory
     * @throws cInvalidArgumentException
     *         if the dir with the given dirname does not exist
     * @return bool
     *         Returns true on success or false on failure.
     */
    public static function remove($dirname) {
        if (!self::exists($dirname)) {
            throw new cInvalidArgumentException('The directory ' . $dirname . ' could not be accessed because it does not exist.');
        }
        return rmdir($dirname);
    }

    /**
     * Moves a dir
     *
     * @param string $dirname
     *         The path and name of the directory
     * @param string $destination
     *         the destination. Note that the dir can also be renamed in the
     *         process of moving it
     * @throws cInvalidArgumentException
     *         if the dir with the given dirname does not exist
     * @return bool
     *         Returns true on success or false on failure.
     */
    public static function move($dirname, $destination) {
        if (!self::exists($dirname)) {
            throw new cInvalidArgumentException('The directory ' . $dirname . ' could not be accessed because it does not exist.');
        }

        $success = rename($dirname, $destination);

        if ($success) {
            self::setDefaultDirPerms($destination);
        }

        return $success;
    }

    /**
     * Renames a dir
     *
     * @param string $dirname
     *         the name and path of the dir
     * @param string $new_dirname
     *         the new name of the dir
     */
    public static function rename($dirname, $new_dirname) {
        self::move($dirname, $new_dirname);
    }

    /**
     * Changes the dir permissions
     *
     * @param string $dirname
     *         the name and path of the dir
     * @param int $mode
     *         the new access mode : php chmod needs octal value
     * @throws cInvalidArgumentException
     *         if the dir with the given dirname does not exist
     * @return bool
     *         Returns true on success or false on failure.
     */
    public static function chmod($dirname, $mode) {
        if (!cFileHandler::exists($dirname)) {
            throw new cInvalidArgumentException('The directory ' . $dirname . ' could not be accessed because it does not exist.');
        }
        // chmod needs octal value for correct execution.
        $mode = intval($mode, 8);
        return @chmod($dirname, $mode);
    }

    /**
     * Sets the default directory permissions on the given directory.
     *
     * @param string $dirname
     *         the name of the directory
     * @return bool
     *         Returns true on success or false on failure.
     */
    public static function setDefaultDirPerms($dirname) {
        $cfg = cRegistry::getConfig();
        $dirPerms = $cfg['default_perms']['directory'];

        return self::chmod($dirname, $dirPerms);
    }

    /**
     * Deletes a directory and all of its content.
     *
     * @param string $dirname
     *         the name of the directory which should be deleted
     * @throws cInvalidArgumentException
     *         if dirname is empty
     * @return bool
     *         Returns true on success or false on failure.
     */
    public static function recursiveRmdir($dirname) {
        if ($dirname == '') {
            throw new cInvalidArgumentException('Directory name must not be empty.');
        }

        // make sure $dirname ends with a slash
        if (substr($dirname, -1) !== '/') {
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
     *         the name and path of the file
     * @param string $destination
     *         the destination. Note that existing files get overwritten
     * @throws cInvalidArgumentException
     *         if the file with the given filename does not exist
     * @return bool
     *         true on success
     */
    public static function recursiveCopy($dirname, $destination) {
        if (!self::exists($dirname)) {
            throw new cInvalidArgumentException('The directory ' . $dirname . ' could not be accessed because it does not exist.');
        }

        if (!cFileHandler::exists($destination)) {
            if (!mkdir($destination)) {
                return false;
            }
            if (!self::chmod($destination, "777")) {
                return false;
            }
        }

        foreach ($iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirname), RecursiveIteratorIterator::SELF_FIRST) as $item) {
            // workaround for RecursiveDirectoryIterator::SKIP_DOTS, this was
            // not available in PHP 5.2
            if ($item->getFilename() == '.' || $item->getFilename() == '..') {
                continue;
            }

            if ($item->isDir()) {
                if (!mkdir($destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName())) {
                    return false;
                }
                if (!self::chmod($destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName(), "777")) {
                    return false;
                }
            } else {
                if (!copy($item, $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName())) {
                    return false;
                }
                if (!self::chmod($destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName(), "777")) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Checks if a directory is empty
     *
     * @param string $dir
     *         Name of the directory
     * @return bool
     *         true if the directory is empty
     */
    public static function isDirectoryEmpty($dir) {
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
     * This functions reads the content from given directory.
     * Optionally options are to read the directory recursive or to list only
     * directories.
     *
     * @param string $dirName
     *         directory
     * @param bool $recursive
     *         flag to read recursive
     * @param bool $dirOnly
     *         flag to list only directories
     * @param bool $fileOnly
     *         flag to list only files if $dirOnly is set to false
     * @return array|bool
     *         array containing file names as string, false on error
     */
    public static function read($dirName, $recursive = false, $dirOnly = false, $fileOnly = false) {
        if (!self::exists($dirName)) {
            return false;
        }

        $dirContent = array();
        if ($recursive == false) {
            $dirHandle = opendir($dirName);
            $dirContent = array();
            while (false !== ($file = readdir($dirHandle))) {
                if (!cFileHandler::fileNameIsDot($file)) {

                    if ($dirOnly == true) { // get only directories

                        if (is_dir($dirName . $file)) {
                            $dirContent[] = $file;
                        }
                    // bugfix: is_dir only checked file name without path, thus returning everything most of the time
                    } else if ($fileOnly === true) { // get only files

                        if (is_file($dirName . $file)) {
                            $dirContent[] = $file;
                        }
                    } else { // get everything
                        $dirContent[] = $file;
                    }
                }
            }
            closedir($dirHandle);
        } else {
            $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirName), RecursiveIteratorIterator::SELF_FIRST);
            foreach ($objects as $name => $file) {

                if (!cFileHandler::fileNameIsDot($file)) {
                    $fileName = str_replace("\\", "/", $file->getPathName());

                    // get only directories
                    if ($dirOnly === true && is_dir($fileName)) {
                        $dirContent[] = $fileName;
                    // get only files
                    } else if ($fileOnly === true && is_file($fileName)) {
                        $dirContent[] = $fileName;
                    } else {
                        $dirContent[] = $fileName;
                    }
                }
            }
        }

        return $dirContent;
    }

    /**
     * Checks if a directory exists
     *
     * @param string $dirname
     *         the name and path of the directory
     * @return bool
     *         true if the directory exists
     */
    public static function exists($dirname) {
        return is_dir($dirname);
    }

}
