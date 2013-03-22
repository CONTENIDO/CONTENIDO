<?php

/**
 * Project: CONTENIDO Content Management System
 * Description: Class for dir handling.
 * This class should never be instantiated
 * Requirements: @con_php_req 5.0
 *
 * @package CONTENIDO Backend Classes
 * @version 1.0
 * @author Frederic Schneider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 * @since file available since CONTENIDO release 4.9
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Provides functions for dealing with dirs.
 */
class cDirHandler {

    /**
     * Creates a new dir
     *
     * @param string $dirname the name and path of the new dir
     * @return bool true on success. Otherwise false.
     */
    public static function create($dirname) {
        $success = mkdir($dirname);

        if ($success) {
            self::setDefaultDirPerms($dirname);
        }

        return $success;
    }

    /**
     * Removes a dir from the filesystem
     *
     * @param string $dirname The path to the directory
     * @throws cInvalidArgumentException if the dir with the given dirname
     *         does not exist
     * @return bool true on success
     */
    public static function remove($dirname) {
        if (!cFileHandler::exists($dirname)) {
            throw new cInvalidArgumentException('The directory ' . $dirname . ' could not be accessed because it does not exist.');
        }

        return unlink($dirname);
    }

    /**
     * Moves a dir
     *
     * @param string $dirname The path and name of the directory
     * @param string $destination the destination. Note that the dir can also
     *        be renamed in the process of moving it
     * @throws cInvalidArgumentException if the dir with the given dirname
     *         does not exist
     * @return bool true on success
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
     * @param int $mode the new access mode
     * @throws cInvalidArgumentException if the dir with the given dirname
     *         does not exist
     * @return bool true on success
     */
    public static function chmod($dirname, $mode) {
        if (!cFileHandler::exists($dirname)) {
            throw new cInvalidArgumentException('The directory ' . $dirname . ' could not be accessed because it does not exist.');
        }

        return chmod($dirname, $mode);
    }

    /**
     * Sets the default directory permissions on the given directory.
     *
     * @param string $dirname the name of the directory
     * @return boolean true on success or false on failure
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
     * @return bool true on success or false on failure
     */
    public static function recursiveRmdir($dirname) {
        if ($dirname == '') {
            throw new cInvalidArgumentException("Directory name must not be empty.");
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

}
