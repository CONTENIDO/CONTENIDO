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
     * @param string $pathname the name and path of the new dir
     * @param bool $recursive
     * @return bool Returns true on success or false on failure.
     */
    public static function create($pathname, $recursive = false) {
        // skip if dir already exists (better check with is_dir?)
        if (cFileHandler::exists($pathname)) {
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
     * @param string $dirname The path to the directory
     * @throws cInvalidArgumentException if the dir with the given dirname
     *         does not exist
     * @return bool Returns true on success or false on failure.
     */
    public static function remove($dirname) {
        if (!cFileHandler::exists($dirname)) {
            throw new cInvalidArgumentException('The directory ' . $dirname . ' could not be accessed because it does not exist.');
        }
        return rmdir($dirname);
    }

    /**
     * Moves a dir
     *
     * @param string $dirname The path and name of the directory
     * @param string $destination the destination. Note that the dir can also
     *        be renamed in the process of moving it
     * @throws cInvalidArgumentException if the dir with the given dirname
     *         does not exist
     * @return bool Returns true on success or false on failure.
     */
    public static function move($dirname, $destination) {
        if (!cFileHandler::exists($dirname)) {
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
     * @param string $dirname the name and path of the dir
     * @param string $new_dirname the new name of the dir
     */
    public static function rename($dirname, $new_dirname) {
        self::move($dirname, $new_dirname);
    }

    /**
     * Changes the dir permissions
     *
     * @param string $dirname the name and path of the dir
     * @param int $mode the new access mode : php chmod needs octal value
     * @throws cInvalidArgumentException if the dir with the given dirname
     *         does not exist
     * @return bool Returns true on success or false on failure.
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
     * @param string $dirname the name of the directory
     * @return bool Returns true on success or false on failure.
     */
    public static function setDefaultDirPerms($dirname) {
        $cfg = cRegistry::getConfig();
        $dirPerms = $cfg['default_perms']['directory'];

        return self::chmod($dirname, $dirPerms);
    }

    /**
     * Deletes a directory and all of its content.
     *
     * @param string $dirname the name of the directory which should be deleted
     * @throws cInvalidArgumentException if dirname is empty
     * @return bool Returns true on success or false on failure.
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
     * This functions reads the content from given directory.
     * Optionally options are to read the directory recursive or to list only
     * directories.
     *
     * @param string $dirName directory
     * @param bool $recursive flag to read recursive
     * @param bool $dirOnly flag to list only directories
     * @return boolean Ambigous multitype:unknown string mixed >
     */
    public static function read($dirName, $recursive = false, $dirOnly = false) {
        if (!is_dir($dirName)) {
            return false;
        } else {
            $dirContent = array();
            if ($recursive == false) {
                $dirHandle = opendir($dirName);
                $dirContent = array();
                while (false !== ($file = readdir($dirHandle))) {
                    if (!self::fileNameIsDot($file)) {
                        // get only directories
                        if ($dirOnly == true) {
                            if (is_dir($dirName . $file)) {
                                $dirContent[] = $file;
                            }
                        } else {
                            $dirContent[] = $file;
                        }
                    }
                }
                closedir($dirHandle);
            }

            else {
                $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirName), RecursiveIteratorIterator::SELF_FIRST);
                foreach ($objects as $name => $file) {

                    if (!self::fileNameIsDot($file)) {
                        $fileName = str_replace("\\", "/", $file->getPathName());

                        // get only directories
                        if ($dirOnly == true) {

                            if (is_dir($fileName)) {
                                $dirContent[] = $fileName;
                            }
                        } else {
                            $dirContent[] = $fileName;
                        }
                    }
                }
            }
        }
        return $dirContent;
    }

    /**
     * Check if given filename is either '.' or '..'.
     *
     * @param string $fileName
     * @return boolean
     */
    public static function fileNameIsDot($fileName) {
        if ($fileName != '.' && $fileName != '..') {
            return false;
        } else {
            return true;
        }
    }

}
