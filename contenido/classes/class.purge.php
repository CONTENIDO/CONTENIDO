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
     *
     * @var $oDb
     */
    private $oDb;

    /**
     *
     * @var $cfg
     */
    private $cfg;

    /**
     *
     * @var $cfgClient
     */
    private $cfgClient;

    /**
     *
     * @var string $sDefaultCacheDir
     */
    private $sDefaultCacheDir = 'cache/';

    /**
     *
     * @var string $sDefaultLogDir
     */
    private $sDefaultLogDir = 'logs/';

    /**
     *
     * @var string $sDefaultVersionDir
     */
    private $sDefaultVersionDir = 'version/';

    /**
     *
     * @var string $sDefaultCronjobDir
     */
    private $sDefaultCronjobDir = 'cronjobs/';

    /**
     *
     * @var string $aDirsExcludedWithFiles
     */
    private $aDirsExcludedWithFiles = array(
        '.',
        '..',
        '.svn',
        '.cvs'
    );

    /**
     *
     * @var array $aLogFileTypes
     */
    private $aLogFileTypes;

    /**
     *
     * @var array $aCronjobFileTypes
     */
    private $aCronjobFileTypes;

    /**
     * Constructor of class
     *
     * @param DB_Contenido $db
     * @param array $cfg
     * @param array $cfgClient
     */
    public function __construct(&$db, $cfg, $cfgClient) {
        $this->oDb = $db;
        $this->cfg = $cfg;
        $this->cfgClient = $cfgClient;

        $this->setLogFileTypes(array(
            'txt'
        ));
        $this->setCronjobFileTypes(array(
            'job'
        ));

        $this->_setSystemDirectory();
    }

    /**
     * Reset the table con_code for a client
     *
     * @param int $iClientId
     * @return bool
     */
    public function resetClientConCode($iClientId) {
        global $perm, $currentuser;

        if ($perm->isClientAdmin($iClientId, $currentuser) || $perm->isSysadmin($currentuser)) {
            $mask = $this->cfgClient[$iClientId]['code_path'] . '*.php';
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
     * @param int $iClientId
     * @return bool
     */
    public function resetClientConCatArt($iClientId) {
        global $perm, $currentuser;

        if ($perm->isClientAdmin($iClientId, $currentuser) || $perm->isSysadmin($currentuser)) {
            $sSql = " UPDATE " . $this->cfg['tab']['cat_art'] . " cca, " . $this->cfg['tab']['cat'] . " cc, " . $this->cfg['tab']['art'] . " ca " . " SET cca.createcode=1 " . " WHERE cc.idcat = cca.idcat " . " AND ca.idart = cca.idart " . " AND cc.idclient = " . (int) $iClientId . " AND ca.idclient = " . (int) $iClientId;
            $this->oDb->query($sSql);

            return ($this->oDb->Error == '') ? true : false;
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
            $sSql = "DELETE FROM " . $this->cfg['tab']['inuse'];
            $this->oDb->query($sSql);

            return ($this->oDb->Error == '') ? true : false;
        } else {
            return false;
        }
    }

    /**
     * Reset the table con_phplib_active_sessions
     *
     * @return bool
     */
    public function resetPHPLibActiveSession() {
        global $perm, $currentuser;

        if ($perm->isSysadmin($currentuser)) {
            $sSql = "DELETE FROM " . $this->cfg['tab']['phplib_active_sessions'];
            $this->oDb->query($sSql);

            return ($this->oDb->Error == '') ? true : false;
        } else {
            return false;
        }
    }

    /**
     * Reset the table con_inuse
     *
     * @return bool
     */
    public function resetUnusedSession() {
        global $perm, $currentuser;

        if ($perm->isSysadmin($currentuser)) {
            $sSql = "DELETE FROM " . $this->cfg['tab']['inuse'];
            $this->oDb->query($sSql);

            return ($this->oDb->Error == '') ? true : false;
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
    public function clearClientCache($iClientId, $sCacheDir = 'cache/') {
        global $perm, $currentuser;

        if ($perm->isClientAdmin($iClientId, $currentuser) || $perm->isSysadmin($currentuser)) {
            // $sClientDir = $this->getClientDir($iClientId);
            $sClientDir = $this->cfgClient[$iClientId]['data_path'];

            $sCacheDir = (trim($sCacheDir) == '' || trim($sCacheDir) == '/') ? $this->sDefaultCacheDir : $sCacheDir;
            if (is_dir($sClientDir . $sCacheDir)) {
                $sCachePath = $sClientDir . $sCacheDir;
                return ($this->clearDir($sCachePath, $sCachePath) ? true : false);
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
    public function clearClientHistory($iClientId, $bKeep, $iFileNumber, $sVersionDir = 'version/') {
        global $perm, $currentuser;

        if ($perm->isClientAdmin($iClientId, $currentuser) || $perm->isSysadmin($currentuser)) {
            $sClientDir = $this->getClientDir($iClientId);

            $sCacheDir = (trim($sVersionDir) == '' || trim($sVersionDir) == '/') ? $this->sDefaultVersionDir : $sVersionDir;

            if (is_dir($sClientDir . $sVersionDir)) {
                $sVersionPath = $sClientDir . $sVersionDir;
                $aTmpFile = array();
                $this->clearDir($sVersionPath, $sVersionPath, $bKeep, $aTmpFile);
                if (count($aTmpFile) > 0) {
                    foreach ($aTmpFile as $sKey => $aFiles) {
                        // sort the history files with filename
                        array_multisort($aTmpFile[$sKey]);

                        $iCount = count($aTmpFile[$sKey]);
                        // find the total number to delete
                        $iCountDelete = ($iCount <= $iFileNumber) ? 0 : ($iCount - $iFileNumber);
                        // delete the files
                        for ($i = 0; $i < $iCountDelete; $i++) {
                            if (cFileHandler::exists($aTmpFile[$sKey][$i]) && is_writable($aTmpFile[$sKey][$i])) {
                                unlink($aTmpFile[$sKey][$i]);
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
     * @param int $iClientId
     * @param string $sLogDir
     * @return bool
     */
    public function clearClientLog($iClientId, $sLogDir = 'logs/') {
        global $perm, $currentuser;

        if ($perm->isClientAdmin($iClientId, $currentuser) || $perm->isSysadmin($currentuser)) {
            $sClientDir = $this->getClientDir($iClientId);

            $sLogDir = (trim($sLogDir) == '' || trim($sLogDir) == '/') ? $this->sDefaultLogDir : $sLogDir;

            if (is_dir($sClientDir . $sLogDir)) {
                return $this->emptyFile($sClientDir . $sLogDir, $this->aLogFileTypes);
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
    public function clearConLog($sLogDir = '') {
        global $perm, $currentuser;

        if ($sLogDir == "") {
            $sLogDir = $this->cfg['path']['data'] . "logs/";
        }

        if ($perm->isSysadmin($currentuser)) {
            $sLogDir = (trim($sLogDir) == '' || trim($sLogDir) == '/') ? $this->sDefaultLogDir : $sLogDir;

            if (is_dir($sLogDir)) {
                return $this->emptyFile($sLogDir, $this->aLogFileTypes);
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
    public function clearConCronjob($sCronjobDir = 'cronjobs/') {
        global $perm, $currentuser;

        if ($perm->isSysadmin($currentuser)) {
            $sCronjobDir = (trim($sCronjobDir) == '' || trim($sCronjobDir) == '/') ? $this->sDefaultCronjobDir : $sCronjobDir;

            if (is_dir($sCronjobDir)) {
                return $this->emptyFile($sCronjobDir, $this->aCronjobFileTypes);
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
    public function clearConCache($sCacheDir = 'cache/') {
        global $perm, $currentuser;

        if ($perm->isClientAdmin($iClientId, $currentuser) || $perm->isSysadmin($currentuser)) {
            $sCacheDir = (trim($sCacheDir) == '' || trim($sCacheDir) == '/') ? $this->sDefaultCacheDir : $sCacheDir;

            if (is_dir($sCacheDir)) {
                return ($this->clearDir($sCacheDir, $sCacheDir) ? true : false);
            }

            return false;
        } else {
            return false;
        }
    }

    /**
     * Delete all files and sub directories in a directory
     *
     * @param string $sDirPath
     * @param string $sTmpDirPath - root directory not deleted
     * @param bool $bKeep
     * @param array $aTmpFileList - files are temporarily saved
     * @return bool
     */
    public function clearDir($sDirPath, $sTmpDirPath, $bKeep = false, &$aTmpFileList = array()) {
        if (is_dir($sDirPath) && ($handle = opendir($sDirPath))) {
            $sTmp = str_replace(array(
                '/',
                '..'
                    ), '', $sDirPath);
            while (false !== ($file = readdir($handle))) {
                if (!in_array($file, $this->aDirsExcludedWithFiles)) {
                    $sFilePath = $sDirPath . '/' . $file;
                    $sFilePath = str_replace('//', '/', $sFilePath);
                    if (is_dir($sFilePath)) {
                        $this->clearDir($sFilePath, $sTmpDirPath, $bKeep, $aTmpFileList);
                    } else {
                        if ($bKeep === false) {
                            unlink($sFilePath);
                        } else {
                            $aTmpFileList[$sTmp][] = $sFilePath;
                        }
                    }
                }
            }

            $aDirs = explode('/', $sDirPath);
            if (end($aDirs) == '') {
                array_pop($aDirs);
            }
            $sDirName = end($aDirs);

            if (str_replace(array(
                        '/',
                        '..'
                            ), '', $sDirPath) != str_replace(array(
                        '/',
                        '..'
                            ), '', $sTmpDirPath) && $bKeep === false && !in_array($sDirName, $this->aDirsExcludedWithFiles)) {
                rmdir($sDirPath);
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
     * @param string $sFilePath
     * @return bool
     */
    public function emptyFile($sDirPath, $aTypes) {
        $iCount = 0;
        $iCountCleared = 0;
        if (is_dir($sDirPath) && ($handle = opendir($sDirPath))) {
            while (false !== ($file = readdir($handle))) {
                $sFileExt = trim(end(explode('.', $file)));

                if ($file != "." && $file != ".." && in_array($sFileExt, $aTypes)) {
                    $sFilePath = $sDirPath . '/' . $file;

                    if (cFileHandler::exists($sFilePath) && is_writable($sFilePath)) {
                        $iCount++;

                        // chmod($sFilePath, 0777);
                        if (cFileHandler::truncate($sFilePath)) {
                            $iCountCleared++;
                        }
                    }
                }
            }

            // true if all files are cleaned
            return ($iCount == $iCountCleared) ? true : false;
        }

        return false;
    }

    /**
     * Get frontend directory name for a client
     *
     * @param int $iClientId
     * @return string $sClientDir
     */
    public function getClientDir($iClientId) {
        // $sClientDir = str_replace($this->cfg['path']['frontend'], '..',
        // $this->cfgClient[$iClientId]['path']['frontend']);
        $sClientDir = $this->cfgClient[$iClientId]['path']['frontend'];

        return $sClientDir;
    }

    /**
     * Set log file types
     *
     * @param array $aTypes
     */
    public function setLogFileTypes($aTypes) {
        if (count($aTypes) > 0) {
            foreach ($aTypes as $sType) {
                $this->aLogFileTypes[] = $sType;
            }
        }
    }

    /**
     * Set cronjob file types
     *
     * @param array $aTypes
     * @return void
     */
    public function setCronjobFileTypes($aTypes) {
        if (count($aTypes) > 0) {
            foreach ($aTypes as $sType) {
                $this->aCronjobFileTypes[] = $sType;
            }
        }
    }

    /**
     * Check and set the system directories to exclude from purge
     *
     * @return void
     */
    private function _setSystemDirectory() {
        $sDirsToExcludeWithFiles = getSystemProperty('system', 'purge-dirstoexclude-withfiles');
        $aDirsToExcludeWithFiles = array_map('trim', explode(',', $sDirsToExcludeWithFiles));
        if (count($aDirsToExcludeWithFiles) < 1 || empty($aDirsToExcludeWithFiles[0])) {
            $aDirsToExcludeWithFiles = $this->aDirsExcludedWithFiles;
            setSystemProperty('system', 'purge-dirstoexclude-withfiles', implode(',', $aDirsToExcludeWithFiles));
        }

        $this->aDirsExcludedWithFiles = $aDirsToExcludeWithFiles;
    }

}

class Purge extends cSystemPurge {
    /** @deprecated Class was renamed to cSystemPurge */
    public function __construct(&$db, $cfg, $cfgClient) {
        cDeprecated('Class was renamed to cSystemPurge');
        parent::__construct($db, $cfg, $cfgClient);
    }

}