<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Class for file handling. This class should never be instanciated
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.0
 * @author     Mischa Holz
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Provides functions for dealing with files
 *
 */
class cFileHandler
{
    /**
     * Creates a new file
     * @param string $filename the name and path of the new file
     * @param string $content optional content of the new file. Optional.
     * @return bool true on success. Otherwise false.
     */
    public static function create($filename, $content = "") {
        return file_put_contents($filename, $content) === strlen($content);
    }

    /**
     * Reads bytes from a file
     * @param string $filename the name and path of the file
     * @param int $length the number of bytes to read. Optional.
     * @param int $offset this will be the first byte which is read. Optional.
     * @param bool $reverse if true, the function will start from the back of the file. Optional.
     * @return string|bool On success it returns the bytes which have been read. Otherwise false.
     */
    public static function read($filename, $length = 0, $offset = 0, $reverse = false) {
        if ($reverse) {
            return file_get_contents($filename, false, null, filesize($filename) - $length - $offset, $length);
        } else if ($length > 0 && $offset == 0) {
            return file_get_contents($filename, false, null, 0, $length);
        } else if ($offset > 0 && $length == 0) {
            return file_get_contents($filename, false, null, $offset);
        } else if ($offset > 0 && $length > 0) {
            return file_get_contents($filename, false, null, $offset, $length);
        } else {
            return file_get_contents($filename);
        }
    }

    /**
     * Reads a file line by line
     * @param string $filename the name and path of the file
     * @param int $lines the number of lines to be read. Optional.
     * @param int $lineoffset this will be the first line which is read. Optional.
     * @return string|array|bool If only one line was read the function will return it. If more than one line was read the function will return an array containing the lines. Otherwise false is returned
     */
    public static function readLine($filename, $lines = 0, $lineoffset = 0) {
        $f = fopen($filename, "r");

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

        $ret = null;
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
     * @param string $filename the name and path of the file
     * @param string $content the data which should be written
     * @param bool $append if true the data will be appended to the file. Optional.
     * @return bool true on success, false otherwise
     */
    public static function write($filename, $content, $append = true) {
        $flag = 0;
        if ($append) {
            $flag = FILE_APPEND;
        }
        return file_put_contents($filename, $content, $flag) === strlen($content);
    }

    /**
     * Writes a line to a file (this is similar to cFileHandler::write($filename, $data."\n", $apppend)
     *
     * @see cFileHandler::write($filename, $content, $append)
     * @param string $filename the name and path to the file
     * @param string $content the data of the line
     * @param bool $append if true the data will be appended to file. Optional.
     * @return bool true on success, false otherwise
     */
    public static function writeLine($filename, $content, $append = true) {
        return self::write($filename, $content."\n", $append);
    }

    /**
     * Checks if a file exists
     * @param string $filename the name and path of the file
     * @return bool true if the file exists
     */
    public static function exists($filename) {
        return file_exists($filename);
    }

    /**
     * Checks if the file is writable for the PHP user
     * @param string $filename the name and path of the file
     * @return bool true if the file can be written
     */
    public static function writeable($filename) {
        return is_writeable(dirname($filename));
    }

    /**
     * Checks if a file is readable for the PHP user
     * @param string $filename the name and path of the file
     * @return bool true if the file is readable
     */
    public static function readable($filename) {
        return is_readable($filename);
    }

    /**
     * Removes a file from the filesystem
     * @param string $filename the name and path of the file
     * @return bool true on success
     */
    public static function remove($filename) {
        return unlink($filename);
    }

    /**
     * Truncates a file so that it is empty
     * @param string $filename the name and path of the file
     * @return bool true on success
     */
    public static function truncate($filename) {
        return file_put_contents($filename, "") === 0;
    }

    /**
     * Moves a file
     * @param string $filename the name of the source file
     * @param string $destination the destination. Note that the file can also be renamed in the process of moving it
     * @return bool true on success
     */
    public static function move($filename, $destination) {
        return rename($filename, $destination);
    }

    /**
     * Renames a file
     * @param string $filename the name and path of the file
     * @param string $new_filename the new name of the file
     * @return bool true on success
     */
    public static function rename($filename, $new_filename) {
        return rename($filename, dirname($filename)."/".$new_filename);
    }

    /**
     * Copies a file
     * @param string $filename the name and path of the file
     * @param string $destination the destination. Note that existing files get overwritten
     * @return bool true on success
     */
    public static function copy($filename, $destination) {
        return copy($filename, $destination);
    }

    /**
     * Changes the file permissions
     * @param string $filename the name and path of the file
     * @param string $mode the new access mode
     * @return bool true on success
     */
    public static function chmod($filename, $mode) {
        return chmod($filename, $mode);
    }

    /**
     * Returns an array containing information about the file.
     *
     * Currently following elements are in it:
     *         'size'         - the file size (in byte)
     *         'atime'        - the time the file was last accessed (unix timestamp)
     *         'ctime'        - time the file was created (unix timestamp)
     *         'mtime'        - time the file was last modified (unix timestamp)
     *         'perms'        - permissions of the file represented in 4 octal digits
     *         'extension'    - the file extension or "" if there's no extension
     *         'mime'        - the mime type of the file
     *
     * @param string $filename the name and path to the file
     * @return array Returns an array containing information about the file
     */
    public static function info($filename) {
        $ret = array();

        $ret['size'] = @filesize($filename);
        $ret['atime'] = @fileatime($filename);
        $ret['ctime'] = @filectime($filename);
        $ret['mtime'] = @filemtime($filename);

        $temp = @decoct(fileperms($filename));
        $ret['perms'] = substr($temp, strlen($temp) - 4);

        $ret['extension'] = substr(basename($filename), (int) strrpos(basename($filename), ".") + 1);
        if ($ret['extension'] == basename($filename)) {
            $ret['extension'] = "";
        }

        if (version_compare(PHP_VERSION, "5.3", "<")) {
            $ret['mime'] = @mime_content_type($filename); //function is deprecated in PHP 5.3
        } else {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE); //extension has to be installed seperately in versions prior to 5.3
            $ret['mime'] = @finfo_file($finfo, $filename);
        }

        foreach ($ret as $value) {
            if ($value === false) {
                cWarning(__FILE__, __LINE__, "Couldn't read ".$filename);
                return $ret;
            }
        }
        return $ret;
    }
}

?>