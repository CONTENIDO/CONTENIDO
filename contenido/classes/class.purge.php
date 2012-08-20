<?php
/**
 * Description:
 * CONTENIDO cSystemPurge class to reset some datas and files.
 *
 * @package CONTENIDO Backend Classes
 * @version 1.0.2
 * @author Munkh-Ulzii Balidar
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 * @since file available since CONTENIDO release <= 4.8.12
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * class cSystemPurge
 */
class cSystemPurge {

    /**
     * @var DB_Contenido
     */
    private $_db;

    /**
     * @var array
     */
    private $_cfg;

    /**
     * @var array
     */
    private $_cfgClient;

    /**
     * @var array
     */
    private $dirsExcludedWithFiles = array(
        '.', '..', '.svn', '.cvs', '.htaccess'
    );

    /**
     * @var array
     */
    private $_logFileTypes = array();

    /**
     *
     * @var array
     */
    private $_cronjobFileTypes = array();

    /**
     * Constructor of class
     *
     * @param DB_Contenido $db
     * @param array $cfg
     * @param array $cfgClient
     */
    public function __construct(&$db, $cfg, $cfgClient) {
        $this->_db = $db;
        $this->_cfg = $cfg;
        $this->_cfgClient = $cfgClient;

        $this->setLogFileTypes(array(
            'txt'
        ));
        $this->setCronjobFileTypes(array(
            'job'
        ));

        $this->_setSystemDirectory();
    }

    /**
     * Deletes the PHP files in cms/cache/code
     *
     * @param int $clientId
     * @return bool
     */
    public function resetClientConCode($clientId) {
        global $perm, $currentuser;

        if ($perm->isClientAdmin($clientId, $currentuser) || $perm->isSysadmin($currentuser)) {
            $mask = $this->_cfgClient[$clientId]['cache']['path'] . 'code/*.php';
            $arr = glob($mask);
            foreach ($arr as $file) {
                if (!unlink($file)) {
                    return false;
                }
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Reset the table con_cat_art for a client
     *
     * @param int $clientId
     * @return bool
     */
    public function resetClientConCatArt($clientId) {
        global $perm, $currentuser;

        if ($perm->isClientAdmin($clientId, $currentuser) || $perm->isSysadmin($currentuser)) {
            $sSql = ' UPDATE ' . $this->_cfg['tab']['cat_art'] . ' cca, ' . $this->_cfg['tab']['cat'] . ' cc, ' . $this->_cfg['tab']['art'] . ' ca ' . ' SET cca.createcode=1 ' . ' WHERE cc.idcat = cca.idcat ' . ' AND ca.idart = cca.idart ' . ' AND cc.idclient = ' . (int) $clientId . ' AND ca.idclient = ' . (int) $clientId;
            $this->_db->query($sSql);

            return ($this->_db->Error == '') ? true : false;
        } else {
            return false;
        }
    }

    /**
     * Reset the table con_inuse
     *
     * @return bool
     */
    public function resetConInuse() {
        global $perm, $currentuser;

        if ($perm->isSysadmin($currentuser)) {
            $sql = 'DELETE FROM ' . $this->_cfg['tab']['inuse'];
            $this->_db->query($sql);

            return ($this->_db->Error == '') ? true : false;
        } else {
            return false;
        }
    }

    /**
     * Clear the cache directory for a client
     *
     * @param int $sClientName
     * @return bool
     */
    public function clearClientCache($clientId) {
        global $perm, $currentuser;

        if ($perm->isClientAdmin($clientId, $currentuser) || $perm->isSysadmin($currentuser)) {
            $cacheDir = $this->_cfgClient[$clientId]['cache']['path'];
            if (is_dir($cacheDir)) {
                return ($this->clearDir($cacheDir, $cacheDir) ? true : false);
            }
            return false;
        } else {
            return false;
        }
    }

    /**
     * Clear the cache directory for a client
     *
     * @param int $sClientName
     * @return bool
     */
    public function clearClientHistory($clientId, $keep, $fileNumber) {
        global $perm, $currentuser;

        if ($perm->isClientAdmin($clientId, $currentuser) || $perm->isSysadmin($currentuser)) {
            $versionDir = $this->_cfgClient[$clientId]['version']['path'];
            if (is_dir($versionDir)) {
                $tmpFile = array();
                $this->clearDir($versionDir, $versionDir, $keep, $tmpFile);
                if (count($tmpFile) > 0) {
                    foreach ($tmpFile as $sKey => $aFiles) {
                        // sort the history files with filename
                        array_multisort($tmpFile[$sKey]);

                        $count = count($tmpFile[$sKey]);
                        // find the total number to delete
                        $countDelete = ($count <= $fileNumber) ? 0 : ($count - $fileNumber);
                        // delete the files
                        for ($i = 0; $i < $countDelete; $i++) {
                            if (cFileHandler::exists($tmpFile[$sKey][$i]) && is_writable($tmpFile[$sKey][$i])) {
                                unlink($tmpFile[$sKey][$i]);
                            }
                        }
                    }
                }

                return true;
            }
            return false;
        } else {
            return false;
        }
    }

    /**
     * Clear client log file
     *
     * @param int $clientId
     * @param string $logDir
     * @return bool
     */
    public function clearClientLog($clientId) {
        global $perm, $currentuser;

        if ($perm->isClientAdmin($clientId, $currentuser) || $perm->isSysadmin($currentuser)) {
            $logDir = $this->_cfgClient[$clientId]['log']['path'];
            if (is_dir($logDir)) {
                return $this->emptyFile($logDir, $this->_logFileTypes);
            }
            return false;
        } else {
            return false;
        }
    }

    /**
     * Clear CONTENIDO log files
     *
     * @param string $sLogDir
     * @return bool
     */
    public function clearConLog() {
        global $perm, $currentuser;

        $logDir = $this->_cfg['path']['contenido_logs'];
        if ($perm->isSysadmin($currentuser)) {
            if (is_dir($logDir)) {
                return $this->emptyFile($logDir, $this->_logFileTypes);
            }
            return false;
        } else {
            return false;
        }
    }

    /**
     * Clear the cronjob directory
     *
     * @param string $sLogDir
     * @return bool
     */
    public function clearConCronjob() {
        global $perm, $currentuser;

        $cronjobDir = $this->_cfg['path']['contenido_cronlog'];
        if ($perm->isSysadmin($currentuser)) {
            if (is_dir($cronjobDir)) {
                return $this->emptyFile($cronjobDir, $this->_cronjobFileTypes);
            }
            return false;
        } else {
            return false;
        }
    }

    /**
     * Clear the cache directory for a client
     *
     * @param int $sClientName
     * @return bool
     */
    public function clearConCache() {
        global $perm, $currentuser;

        $cacheDir = $this->_cfg['path']['contenido_cache'];
        if ($perm->isSysadmin($currentuser)) {
            if (is_dir($cacheDir)) {
                return ($this->clearDir($cacheDir, $cacheDir) ? true : false);
            }
            return false;
        } else {
            return false;
        }
    }

    /**
     * Delete all files and sub directories in a directory
     *
     * @param string $dirPath
     * @param string $tmpDirPath - root directory not deleted
     * @param bool $keep
     * @param array $tmpFileList - files are temporarily saved
     * @return bool
     */
    public function clearDir($dirPath, $tmpDirPath, $keep = false, &$tmpFileList = array()) {
        if (is_dir($dirPath) && ($handle = opendir($dirPath))) {
            $tmp = str_replace(array(
                '/',
                '..'
                    ), '', $dirPath);
            while (false !== ($file = readdir($handle))) {
                if (!in_array($file, $this->dirsExcludedWithFiles)) {
                    $filePath = $dirPath . '/' . $file;
                    $filePath = str_replace('//', '/', $filePath);
                    if (is_dir($filePath)) {
                        $this->clearDir($filePath, $tmpDirPath, $keep, $tmpFileList);
                    } else {
                        if ($keep === false) {
                            unlink($filePath);
                        } else {
                            $tmpFileList[$tmp][] = $filePath;
                        }
                    }
                }
            }

            $dirs = explode('/', $dirPath);
            if (end($dirs) == '') {
                array_pop($dirs);
            }
            $dirName = end($dirs);

            if (str_replace(array(
                        '/',
                        '..'
                            ), '', $dirPath) != str_replace(array(
                        '/',
                        '..'
                            ), '', $tmpDirPath) && $keep === false && !in_array($dirName, $this->dirsExcludedWithFiles)) {
                rmdir($dirPath);
            }

            closedir($handle);

            return true;
        } else {
            return false;
        }
    }

    /**
     * Empty a file content
     *
     * @param string $filePath
     * @return bool
     */
    public function emptyFile($dirPath, $types) {
        $count = 0;
        $countCleared = 0;
        if (is_dir($dirPath) && ($handle = opendir($dirPath))) {
            while (false !== ($file = readdir($handle))) {
                $fileExt = trim(end(explode('.', $file)));

                if ($file != '.' && $file != '..' && in_array($fileExt, $types)) {
                    $filePath = $dirPath . '/' . $file;

                    if (cFileHandler::exists($filePath) && is_writable($filePath)) {
                        $count++;

                        if (cFileHandler::truncate($filePath)) {
                            $countCleared++;
                        }
                    }
                }
            }

            // true if all files are cleaned
            return ($count == $countCleared) ? true : false;
        }

        return false;
    }

    /**
     * Get frontend directory name for a client
     *
     * @param int $clientId
     * @return string $sClientDir
     */
    public function getClientDir($clientId) {
        return $this->_cfgClient[$clientId]['path']['frontend'];
    }

    /**
     * Set log file types
     *
     * @param array $types
     */
    public function setLogFileTypes($types) {
        if (count($types) > 0) {
            foreach ($types as $type) {
                $this->_logFileTypes[] = $type;
            }
        }
    }

    /**
     * Set cronjob file types
     *
     * @param array $types
     * @return void
     */
    public function setCronjobFileTypes($types) {
        if (count($types) > 0) {
            foreach ($types as $type) {
                $this->_cronjobFileTypes[] = $type;
            }
        }
    }

    /**
     * Check and set the system directories to exclude from purge
     *
     * @return void
     */
    private function _setSystemDirectory() {
        $dirsToExcludeWithFiles = getSystemProperty('system', 'purge-dirstoexclude-withfiles');
        $aDirsToExcludeWithFiles = array_map('trim', explode(',', $dirsToExcludeWithFiles));
        if (count($aDirsToExcludeWithFiles) < 1 || empty($aDirsToExcludeWithFiles[0])) {
            $aDirsToExcludeWithFiles = $this->dirsExcludedWithFiles;
            setSystemProperty('system', 'purge-dirstoexclude-withfiles', implode(',', $aDirsToExcludeWithFiles));
        }

        $this->dirsExcludedWithFiles = $aDirsToExcludeWithFiles;
    }

}

class Purge extends cSystemPurge {

    /** @deprecated Class was renamed to cSystemPurge */
    public function __construct(&$db, $cfg, $cfgClient) {
        cDeprecated('Class was renamed to cSystemPurge');
        parent::__construct($db, $cfg, $cfgClient);
    }

}