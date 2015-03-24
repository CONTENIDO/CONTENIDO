<?php
/**
 * This file contains the the system purge class.
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Munkh-Ulzii Balidar
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * CONTENIDO cSystemPurge class to reset some datas and files
 *
 * @package Core
 * @subpackage Backend
 */
class cSystemPurge {

    /**
     * These directories should not be deleted.
     *
     * @var array
     */
    private $_dirsExcluded = array(
        'code',
        'templates_c'
    );

    /**
     * These directories and the included files should not be cleared.
     *
     * @var array
     */
    private $_dirsExcludedWithFiles = array(
        '.',
        '..',
        '.svn',
        '.cvs',
        '.htaccess',
        '.git',
        '.gitignore',
        '.keep',
    );

    /**
     *
     * @var array
     */
    private $_logFileTypes = array(
        'txt'
    );

    /**
     *
     * @var array
     */
    private $_cronjobFileTypes = array(
        'job'
    );

    /**
     * Constructor of class
     */
    public function __construct() {
        // check and set the system directories to exclude from purge
        $dirsToExcludeWithFiles = getSystemProperty('system', 'purge-dirstoexclude-withfiles');
        $aDirsToExcludeWithFiles = array_map('trim', explode(',', $dirsToExcludeWithFiles));
        if (count($aDirsToExcludeWithFiles) < 1 || empty($aDirsToExcludeWithFiles[0])) {
            $aDirsToExcludeWithFiles = $this->_dirsExcludedWithFiles;
            setSystemProperty('system', 'purge-dirstoexclude-withfiles', implode(',', $aDirsToExcludeWithFiles));
        }

        $this->_dirsExcludedWithFiles = $aDirsToExcludeWithFiles;
    }

    /**
     * Deletes the PHP files in cms/cache/code
     *
     * @param int $clientId
     * @return bool
     */
    public function resetClientConCode($clientId) {
        global $perm, $currentuser;
        $cfgClient = cRegistry::getClientConfig();

        if (cFileHandler::exists($cfgClient[$clientId]['cache']['path'] . 'code/') === false) {
            return false;
        }

        if ($perm->isClientAdmin($clientId, $currentuser) === false && $perm->isSysadmin($currentuser) === false) {
            return false;
        }

        /** @var $file SplFileInfo */
        foreach (new DirectoryIterator($cfgClient[$clientId]['code']['path']) as $file) {
            if ($file->isFile() === false) {
                continue;
            }

            $extension = substr($file, strrpos($file->getBasename(), '.') + 1);
            if ($extension != 'php') {
                continue;
            }

            if (cFileHandler::remove($cfgClient[$clientId]['code']['path'] . '/' . $file->getFilename()) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Reset the table con_cat_art for a client
     *
     * @param int $clientId
     * @return bool
     */
    public function resetClientConCatArt($clientId) {
        global $perm, $currentuser;
        $db = cRegistry::getDb();
        $cfg = cRegistry::getConfig();

        if ($perm->isClientAdmin($clientId, $currentuser) || $perm->isSysadmin($currentuser)) {
            $sSql = ' UPDATE ' . $cfg['tab']['cat_art'] . ' cca, ' . $cfg['tab']['cat'] . ' cc, ' . $cfg['tab']['art'] . ' ca ' . ' SET cca.createcode=1 ' . ' WHERE cc.idcat = cca.idcat ' . ' AND ca.idart = cca.idart ' . ' AND cc.idclient = ' . (int) $clientId . ' AND ca.idclient = ' . (int) $clientId;
            $db->query($sSql);

            return ($db->getErrorMessage() == '') ? true : false;
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
        $db = cRegistry::getDb();
        $cfg = cRegistry::getConfig();

        if ($perm->isSysadmin($currentuser)) {
            $sql = 'DELETE FROM ' . $cfg['tab']['inuse'];
            $db->query($sql);

            return ($db->getErrorMessage() == '') ? true : false;
        } else {
            return false;
        }
    }

    /**
     * Clear the cache directory for a client
     *
     * @param int $clientId
     * @return bool
     */
    public function clearClientCache($clientId) {
        global $perm, $currentuser;
        $cfgClient = cRegistry::getClientConfig();

        if ($perm->isClientAdmin($clientId, $currentuser) || $perm->isSysadmin($currentuser)) {
            $cacheDir = $cfgClient[$clientId]['cache']['path'];
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
     * @param int $clientId
     * @param bool $keep
     * @param int $fileNumber
     * @return bool
     */
    public function clearClientHistory($clientId, $keep, $fileNumber) {
        global $perm, $currentuser;
        $cfgClient = cRegistry::getClientConfig();

        if ($perm->isClientAdmin($clientId, $currentuser) || $perm->isSysadmin($currentuser)) {
            $versionDir = $cfgClient[$clientId]['version']['path'];
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
     * Clear the client content versioning
     *
     * @param int $clientId
     * @param bool $keep
     * @param int $fileNumber
     * @return bool
     */
    public function clearClientContentVersioning($clientId) {
        global $perm, $currentuser;
        
        if ($perm->isClientAdmin($clientId, $currentuser) || $perm->isSysadmin($currentuser)) {
            
            $artLangVersionColl = new cApiArticleLanguageVersionCollection();
            $artLangVersionColl->deleteByWhereClause('idartlangversion != 0');
            
            $contentVersionColl = new cApiContentVersionCollection();
            $contentVersionColl->deleteByWhereClause('idcontentversion != 0');
            
            $metaTagVersionColl = new cApiMetaTagVersionCollection();
            $metaTagVersionColl->deleteByWhereClause('idmetatagversion != 0');
            
            return true;
        } else {
            return false;
        }
    }

    /**
     * Clear client log file
     *
     * @param int $clientId
     * @return bool
     */
    public function clearClientLog($clientId) {
        global $perm, $currentuser;
        $cfgClient = cRegistry::getClientConfig();

        if ($perm->isClientAdmin($clientId, $currentuser) || $perm->isSysadmin($currentuser)) {
            $logDir = $cfgClient[$clientId]['log']['path'];
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
     * @return bool
     */
    public function clearConLog() {
        global $perm, $currentuser;
        $cfg = cRegistry::getConfig();

        $logDir = $cfg['path']['contenido_logs'];
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
     * @return bool
     */
    public function clearConCronjob() {
        global $perm, $currentuser;
        $cfg = cRegistry::getConfig();

        $cronjobDir = $cfg['path']['contenido_cronlog'];
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
     * @return bool
     */
    public function clearConCache() {
        global $perm, $currentuser;
        $cfg = cRegistry::getConfig();

        $cacheDir = $cfg['path']['contenido_cache'];
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
     * Clears the article cache of the article which is defined by the given
     * parameters.
     *
     * @param int $idartlang the idartlang of the article
     */
    public function clearArticleCache($idartlang) {
        $cfgClient = cRegistry::getClientConfig();
        $client = cRegistry::getClientId();

        $artLang = new cApiArticleLanguage($idartlang);
        $idlang = $artLang->get('idlang');
        $idart = $artLang->get('idart');
        $art = new cApiArticle($idart);
        $idclient = $art->get('idclient');

        $catArtColl = new cApiCategoryArticleCollection();
        $catArtColl->select('idart=' . $idart);
        while (($item = $catArtColl->next()) !== false) {
            $filename = $cfgClient[$client]['code']['path'] . $idclient . '.' . $idlang . '.' . $item->get('idcatart') . '.php';
            if (cFileHandler::exists($filename)) {
                cFileHandler::remove($filename);
            }
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
                if (!in_array($file, $this->_dirsExcludedWithFiles)) {
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
            ), '', $tmpDirPath) && $keep === false && !in_array($dirName, $this->_dirsExcludedWithFiles) && !in_array($dirName, $this->_dirsExcluded)) {
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
     * @param string $dirPath
     * @param array $types
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
        $cfgClient = cRegistry::getClientConfig();

        return $cfgClient[$clientId]['path']['frontend'];
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
     */
    public function setCronjobFileTypes($types) {
        if (count($types) > 0) {
            foreach ($types as $type) {
                $this->_cronjobFileTypes[] = $type;
            }
        }
    }

}
