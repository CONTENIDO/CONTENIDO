<?php
/**
 * This file contains the the static file handler class.
 *
 * @package Core
 * @subpackage Util
 * @version SVN Revision $Rev:$
 *
 * @author Mischa Holz
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class for file handling.
 * Provides functions for dealing with files.
 *
 * @package Core
 * @subpackage Util
 */
class cFileHandler {

    /**
     * Creates a new file
     *
     * @param string $filename the name and path of the new file
     * @param string $content optional content of the new file. Optional.
     * @return bool true on success. Otherwise false.
     */
    public static function create($filename, $content = '') {
        $success = file_put_contents($filename, $content) === strlen($content);
        if ($success) {
            self::setDefaultFilePerms($filename);
        }

        return $success;
    }

    /**
     * Reads bytes from a file
     *
     * @param string $filename the name and path of the file
     * @param int $length the number of bytes to read. Optional.
     * @param int $offset this will be the first byte which is read. Optional.
     * @param bool $reverse if true, the function will start from the back of
     *        the file. Optional.
     * @throws cInvalidArgumentException if the file with the given filename
     *         does not exist
     * @return string bool success it returns the bytes which have been read.
     *         Otherwise false.
     */
    public static function read($filename, $length = 0, $offset = 0, $reverse = false) {
        if (!cFileHandler::exists($filename)) {
            throw new cInvalidArgumentException('The file ' . $filename . ' could not be accessed because it does not exist.');
        }

        if ($reverse) {
            return file_get_contents($filename, false, NULL, filesize($filename) - $length - $offset, $length);
        } else if ($length > 0 && $offset == 0) {
            return file_get_contents($filename, false, NULL, 0, $length);
        } else if ($offset > 0 && $length == 0) {
            return file_get_contents($filename, false, NULL, $offset);
        } else if ($offset > 0 && $length > 0) {
            return file_get_contents($filename, false, NULL, $offset, $length);
        } else {
            return file_get_contents($filename);
        }
    }

    /**
     * Reads a file line by line
     *
     * @param string $filename the name and path of the file
     * @param int $lines the number of lines to be read. Optional.
     * @param int $lineoffset this will be the first line which is read.
     *        Optional.
     * @throws cInvalidArgumentException if the file with the given filename
     *         does not exist
     * @return string array bool one line was read the function will return it.
     *         If more than one line was read the function will return an array
     *         containing the lines. Otherwise false is returned
     */
    public static function readLine($filename, $lines = 0, $lineoffset = 0) {
        if (!cFileHandler::exists($filename)) {
            throw new cInvalidArgumentException('The file ' . $filename . ' could not be accessed because it does not exist.');
        }

        $f = fopen($filename, 'r');

        if ($f === false) {
            fclose($f);
            return false;
        }
        if ($lines == 0) {
            $lines = 1;
        }

        for ($i = 0; $i < $lineoffset; $i++) {
            $waste = fgets($f);
        }

        $ret = NULL;
        if ($lines > 1) {
            $ret = array();
            for ($i = 0; $i < $lines; $i++) {
                $temp = fgets($f);
                if ($temp === false) {
                    fclose($f);
                    return false;
                }
                $ret[] = substr($temp, 0, strlen($temp) - 1);
            }
        } else {
            $ret = fgets($f);
            $ret = substr($ret, 0, strlen($ret) - 1);
        }

        fclose($f);
        return $ret;
    }

    /**
     * Writes data to a file
     *
     * @param string $filename the name and path of the file
     * @param string $content the data which should be written
     * @param bool $append if true the data will be appended to the file.
     *        Optional.
     * @return bool true on success, false otherwise
     */
    public static function write($filename, $content, $append = false) {
        $flag = 0;
        if ($append && self::exists($filename)) {
            $flag = FILE_APPEND;
        }

        $success = file_put_contents($filename, $content, $flag);
        if ((int) $success != 0) {
            self::setDefaultFilePerms($filename);
        }

        return !($success === false);
    }

    /**
     * Writes a line to a file (this is similar to
     * cFileHandler::write($filename, $data."\n", $apppend)
     *
     * @see cFileHandler::write($filename, $content, $append)
     * @param string $filename the name and path to the file
     * @param string $content the data of the line
     * @param bool $append if true the data will be appended to file. Optional.
     * @return bool true on success, false otherwise
     */
    public static function writeLine($filename, $content, $append = false) {
        return self::write($filename, $content . "\n", $append);
    }

    /**
     * Checks if a file exists
     *
     * @param string $filename the name and path of the file
     * @return bool true if the file exists
     */
    public static function exists($filename) {
        return file_exists($filename);
    }

    /**
     * Checks if the file is writable for the PHP user
     *
     * @param string $filename the name and path of the file
     * @return bool true if the file can be written
     */
    public static function writeable($filename) {
        return is_writable($filename);
    }

    /**
     * Checks if a file is readable for the PHP user
     *
     * @param string $filename the name and path of the file
     * @throws cInvalidArgumentException if the file with the given filename
     *         does not exist
     * @return bool true if the file is readable
     */
    public static function readable($filename) {
        if (!cFileHandler::exists($filename)) {
            throw new cInvalidArgumentException('The file ' . $filename . ' could not be accessed because it does not exist.');
        }

        return is_readable($filename);
    }

    /**
     * Removes a file from the filesystem
     *
     * @param string $filename the name and path of the file
     * @throws cInvalidArgumentException if the file with the given filename
     *         does not exist
     * @return bool true on success
     */
    public static function remove($filename) {
        if (!cFileHandler::exists($filename)) {
            throw new cInvalidArgumentException('The file ' . $filename . ' could not be accessed because it does not exist.');
        }

        return unlink($filename);
    }

    /**
     * Truncates a file so that it is empty
     *
     * @param string $filename the name and path of the file
     * @throws cInvalidArgumentException if the file with the given filename
     *         does not exist
     * @return bool true on success
     */
    public static function truncate($filename) {
        if (!cFileHandler::exists($filename)) {
            throw new cInvalidArgumentException('The file ' . $filename . ' could not be accessed because it does not exist.');
        }
        $success = file_put_contents($filename, '') === 0;
        if ($success) {
            self::setDefaultFilePerms($filename);
        }

        return $success;
    }

    /**
     * Moves a file
     *
     * @param string $filename the name of the source file
     * @param string $destination the destination. Note that the file can also
     *        be renamed in the process of moving it
     * @throws cInvalidArgumentException if the file with the given filename
     *         does not exist
     * @return bool true on success
     */
    public static function move($filename, $destination) {
        if (!cFileHandler::exists($filename)) {
            throw new cInvalidArgumentException('The file ' . $filename . ' could not be accessed because it does not exist.');
        }
        $success = rename($filename, $destination);
        if ($success) {
            self::setDefaultFilePerms($destination);
        }

        return $success;
    }

    /**
     * Renames a file
     *
     * @param string $filename the name and path of the file
     * @param string $new_filename the new name of the file
     * @throws cInvalidArgumentException if the file with the given filename
     *         does not exist
     * @return bool true on success
     */
    public static function rename($filename, $new_filename) {
        if (!cFileHandler::exists($filename)) {
            throw new cInvalidArgumentException('The file ' . $filename . ' could not be accessed because it does not exist.');
        }
        $success = rename($filename, dirname($filename) . '/' . $new_filename);
        if ($success) {
            self::setDefaultFilePerms(dirname($filename) . '/' . $new_filename);
        }

        return $success;
    }

    /**
     * Copies a file
     *
     * @param string $filename the name and path of the file
     * @param string $destination the destination. Note that existing files get
     *        overwritten
     * @throws cInvalidArgumentException if the file with the given filename
     *         does not exist
     * @return bool true on success
     */
    public static function copy($filename, $destination) {
        if (!cFileHandler::exists($filename)) {
            throw new cInvalidArgumentException('The file ' . $filename . ' could not be accessed because it does not exist.');
        }
        $success = copy($filename, $destination);
        if ($success) {
            self::setDefaultFilePerms($destination);
        }

        return $success;
    }

    /**
     * Changes the file permissions
     *
     * @param string $filename the name and path of the file
     * @param int $mode the new access mode : php chmod needs octal value
     * @throws cInvalidArgumentException if the file with the given filename
     *         does not exist
     * @return bool true on success
     */
    public static function chmod($filename, $mode) {
        if (!cFileHandler::exists($filename)) {
            throw new cInvalidArgumentException('The file ' . $filename . ' could not be accessed because it does not exist.');
        }
        // chmod needs octal value for correct execution.
        $mode = intval($mode, 8);
        return chmod($filename, $mode);
    }

    /**
     * Returns an array containing information about the file.
     * Currently
     * following elements are in it: 'size' - the file size (in byte) 'atime' -
     * the time the file was last accessed (unix timestamp) 'ctime' - time the
     * file was created (unix timestamp) 'mtime' - time the file was last
     * modified (unix timestamp) 'perms' - permissions of the file represented
     * in 4 octal digits 'extension' - the file extension or '' if there's no
     * extension 'mime' - the mime type of the file
     *
     * @param string $filename the name and path to the file
     * @throws cInvalidArgumentException if the file with the given filename
     *         does not exist
     * @return array Returns an array containing information about the file
     */
    public static function info($filename) {
        if (!cFileHandler::exists($filename)) {
            throw new cInvalidArgumentException('The file ' . $filename . ' could not be accessed because it does not exist.');
        }

        $ret = array();

        $ret['size'] = @filesize($filename);
        $ret['atime'] = @fileatime($filename);
        $ret['ctime'] = @filectime($filename);
        $ret['mtime'] = @filemtime($filename);

        $temp = @decoct(fileperms($filename));
        $ret['perms'] = substr($temp, strlen($temp) - 4);

        $ret['extension'] = substr(basename($filename), (int) strrpos(basename($filename), '.') + 1);
        if ($ret['extension'] == basename($filename)) {
            $ret['extension'] = '';
        }

        if (version_compare(PHP_VERSION, '5.3', '<') && function_exists('mime_content_type')) {
            // function is deprecated in PHP 5.3
            $ret['mime'] = @mime_content_type($filename);
        } else if (function_exists('finfo_open')) {
            // extension has to be installed seperately in versions prior to 5.3
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            $ret['mime'] = @finfo_file($finfo, $filename);
        } else {
            $ret['mime'] = '';
        }

        return $ret;
    }

    /**
     * Returns the extension of passed filename
     *
     * @param string $basename
     * @return string
     */
    public static function getExtension($basename) {
        $aFileName = explode('.', trim($basename, '.'));

        return (count($aFileName) > 1) ? $aFileName[count($aFileName) - 1] : '';
    }

    /**
     * Sets the default file permissions on the given file.
     *
     * @param string $filename the name of the file
     * @return boolean true on success or false on failure
     */
    public static function setDefaultFilePerms($filename) {
        $cfg = cRegistry::getConfig();

        if (isset($cfg['default_perms']['file']) === false) {
            return false;
        }

        $filePerms = $cfg['default_perms']['file'];
        return cFileHandler::chmod($filename, $filePerms);
    }

    /**
     *
     * @see cDirHandler::recursiveRmdir()
     * @deprecated 2013-11-11 - Use the class cDirHandler instead.
     */
    public static function recursiveRmdir($dirname) {
        cDeprecated("Use the class cDirHandler instead.");
        return cDirHandler::recursiveRmdir($dirname);
    }

    /**
     *
     * @see cDirHandler::recursiveCopy()
     * @deprecated 2013-11-11 - Use the class cDirHandler instead.
     */
    public static function recursiveCopy($filename, $destination) {
        cDeprecated("Use the class cDirHandler instead.");
        return cDirHandler::recursiveCopy($filename, $destination);
    }

    /**
     *
     * @see cDirHandler::isDirectoryEmpty()
     * @deprecated 2013-11-11 - Use the class cDirHandler instead.
     */
    public static function isDirectoryEmpty($dir) {
        cDeprecated("Use the class cDirHandler instead.");
        return cDirHandler::isDirectoryEmpty($dir);
    }

    /**
     *
     * @see cDirHandler::setDefaultDirPerms()
     * @deprecated 2013-11-11 - Use the class cDirHandler instead.
     */
    public static function setDefaultDirPerms($dirname) {
        cDeprecated("Use the class cDirHandler instead.");
        return cDirHandler::setDefaultDirPerms($dirname);
    }

    /**
     * Validates the given filename.
     *
     * @param string $filename the filename to validate
     * @param bool $notifyAndExitOnFailure if set, function will show a
     *        notification and will exit the script
     * @return bool true if the given filename is valid, false otherwise
     */
    public static function validateFilename($filename, $notifyAndExitOnFailure = true) {
        // check if filename only contains valid characters
        if (preg_match('/[^a-z0-9._-]/i', $filename)) {
            // validation failure...
            if ($notifyAndExitOnFailure) {
                // display notification and exit
                cRegistry::addErrorMessage(i18n('Wrong file name.'));
                $page = new cGuiPage('generic_page');
                $page->abortRendering();
                $page->render();
                exit();
            }

            return false;
        }

        // check if filename is empty
        if (strlen(trim($filename)) == 0) {
            // validation failure...
            if ($notifyAndExitOnFailure) {
                // display notification and exit
                $notification = new cGuiNotification();
                $notification->displayNotification("error", i18n("Please insert file name."));
                exit();
            }

            return false;
        }

        return true;
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
