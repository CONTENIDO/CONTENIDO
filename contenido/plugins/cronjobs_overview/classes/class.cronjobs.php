<?php

/**
 * This file contains the main class for the plugin content allocation.
 *
 * @package    Plugin
 * @subpackage CronjobOverview
 * @author     Rusmir Jusufovic
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

plugin_include('repository', 'custom/FrontendNavigation.php');

/**
 * Main class for cronjob overview
 *
 * @package    Plugin
 * @subpackage CronjobOverview
 */
class Cronjobs
{
    /**
     * @var string
     */
    public static $CRONTAB_FILE = 'crontab.txt';

    /**
     * @var string
     */
    public static $JOB_ENDING = '.job';

    /**
     * @var string
     */
    public static $LOG_ENDING = '.log';

    /**
     * @var string
     */
    protected $_phpFile = '';

    /**
     * Filename without the mimetype
     *
     * @var string
     */
    private $_fileName = '';

    /**
     * Path to the cronjob Directory
     *
     * @var string
     */
    protected $_cronjobDirectory = '';

    /**
     * Path to the cronlog Directory
     *
     * @var string
     */
    protected $_cronlogDirectory = '';

    /**
     * Cronjobs constructor.
     *
     * @param string $phpFile
     */
    public function __construct($phpFile = '')
    {
        $this->_phpFile = $phpFile;

        //get the name of the file withouth the mime type
        if ($phpFile != '') {
            $this->_fileName = cString::getPartOfString($phpFile, 0, -4);
        }

        $cfg = cRegistry::getConfig();
        $this->_cronjobDirectory = cRegistry::getBackendPath() . $cfg['path']['cronjobs'];
        $this->_cronlogDirectory = $cfg['path']['contenido_cronlog'];
    }

    /**
     * Return the name of file
     *
     * @return string
     */
    public function getFile()
    {
        return $this->_phpFile;
    }

    /**
     * Get the directory path of cronjobs
     *
     * @return string
     */
    public function getCronjobDirectory()
    {
        return $this->_cronjobDirectory;
    }

    /**
     * Get the directory path of cronlog
     *
     * @return string
     */
    public function getCronlogDirectory()
    {
        return $this->_cronlogDirectory;
    }

    /**
     *
     * Get date of last execution of cronjob
     *
     * @return string date
     * @throws cInvalidArgumentException
     */
    public function getDateLastExecute()
    {
        $timestamp = '';
        if (cFileHandler::exists($this->_cronlogDirectory . $this->_phpFile . self::$JOB_ENDING)) {
            if (($timestamp = cFileHandler::read($this->_cronlogDirectory . $this->_phpFile . self::$JOB_ENDING))) {
                return date("d.m.Y H:i:s", $timestamp);
            }
        }

        return $timestamp;
    }

    /**
     * Get the contents of the crontab.txt file
     *
     * @return string
     *      contents of the file or ''
     * @throws cInvalidArgumentException
     */
    public function getContentsCrontabFile()
    {
        if (cFileHandler::exists($this->_cronlogDirectory . self::$CRONTAB_FILE)) {
            return cFileHandler::read($this->_cronlogDirectory . self::$CRONTAB_FILE);
        } else {
            return '';
        }
    }

    /**
     *
     * Save the data to crontab.txt file
     *
     * @param string $data
     *
     * @return bool
     * @throws cInvalidArgumentException
     */
    public function saveCrontabFile($data)
    {
        return cFileHandler::write($this->_cronlogDirectory . self::$CRONTAB_FILE, $data);
    }

    /**
     * Set the execute-time to $this->_phpFile.job file.
     *
     * @param int $timestamp
     *
     * @throws cInvalidArgumentException
     */
    public function setRunTime($timestamp)
    {
        cFileHandler::write($this->_cronlogDirectory . $this->_phpFile . self::$JOB_ENDING, $timestamp);
    }

    /**
     * Get the last lines of log file
     *
     * @param int $lines
     *
     * @return string
     * @throws cInvalidArgumentException
     */
    public function getLastLines($lines = 25)
    {
        if (cFileHandler::exists($this->_cronlogDirectory . $this->_phpFile . self::$LOG_ENDING)) {
            $content = explode("\n", cFileHandler::read($this->_cronlogDirectory . $this->_phpFile . self::$LOG_ENDING));
            $number = count($content);
            $pos = $number - $lines;
            if ($pos < 0) {
                $lines += $pos;
                $pos = 0;
            }

            return implode('<br>', array_slice($content, $pos, $lines));
        }

        return '';
    }

    /**
     * Exist the file and is it a php file
     *
     * @return bool if exist
     */
    public function existFile()
    {
        if (!cFileHandler::exists($this->_cronjobDirectory . $this->_phpFile) && !is_dir($this->_cronjobDirectory . $this->_phpFile)) {
            return false;
        } elseif (cString::getPartOfString($this->_phpFile, -4) == '.php') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get all Cronjobs in directory cronjobs from contenido
     */
    public function getAllCronjobs()
    {
        $retArray = [];

        if (is_dir($this->_cronjobDirectory)) {
            // get only files
            if (false !== ($handle = cDirHandler::read($this->_cronjobDirectory, false, false, true))) {
                foreach ($handle as $file) {
                    if (cFileHandler::fileNameIsDot($file) === false
                        && cString::getPartOfString($file, -4) == '.php' && $file != 'index.php') {
                        $retArray[] = $file;
                    }
                }
            }
        }

        return $retArray;
    }
}
