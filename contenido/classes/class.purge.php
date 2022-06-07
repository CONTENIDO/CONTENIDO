<?php
/**
 * This file contains the the system purge class.
 *
 * @package Core
 * @subpackage Backend
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
    private $_dirsExcluded = [
        'code',
        'templates_c'
    ];

    /**
     * These directories and the included files should not be cleared.
     *
     * @var array
     */
    private $_dirsExcludedWithFiles = [
        '.',
        '..',
        '.svn',
        '.cvs',
        '.htaccess',
        '.git',
        '.gitignore',
        '.keep',
    ];

    /**
     *
     * @var array
     */
    private $_logFileTypes = [
        'txt'
    ];

    /**
     *
     * @var array
     */
    private $_cronjobFileTypes = [
        'job'
    ];

    /**
     * Constructor to create an instance of this class.
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
     * Deletes the PHP files in cms/cache/code.
     *
     * @param int $clientId
     *
     * @return bool
     *
     * @throws cInvalidArgumentException
     */
    public function resetClientConCode($clientId) {
        global $currentuser;

        $perm = cRegistry::getPerm();
        $clientCfg = cRegistry::getClientConfig($clientId);
        $codePath = $clientCfg['code']['path'];

        if (cFileHandler::exists($codePath) === false) {
            return false;
        }

        if ($perm->isClientAdmin($clientId, $currentuser) === false && $perm->isSysadmin($currentuser) === false) {
            return false;
        }

        /* @var $file SplFileInfo */
        foreach (new DirectoryIterator($codePath) as $file) {
            if ($file->isFile() === false) {
                continue;
            }

            if ($file->getExtension() === 'php') {
                if (cFileHandler::remove($file->getPathname()) === false) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Reset the table con_cat_art for a client.
     *
     * @param int $clientId
     *
     * @return bool
     *
     * @throws cDbException
     */
    public function resetClientConCatArt($clientId) {
        global $perm, $currentuser;
        $db = cRegistry::getDb();
        $cfg = cRegistry::getConfig();

        if ($perm->isClientAdmin($clientId, $currentuser) || $perm->isSysadmin($currentuser)) {
            $db->query('
                UPDATE
                    ' . $cfg['tab']['cat_art'] . ' cca,
                    ' . $cfg['tab']['cat'] . ' cc,
                    ' . $cfg['tab']['art'] . ' ca
                SET
                    cca.createcode=1
                WHERE
                    cc.idcat = cca.idcat
                    AND ca.idart = cca.idart
                    AND cc.idclient = ' . (int) $clientId . '
                    AND ca.idclient = ' . (int) $clientId);

            return ($db->getErrorMessage() == '') ? true : false;
        } else {
            return false;
        }
    }

    /**
     * Reset the table con_inuse.
     *
     * @return bool
     *
     * @throws cDbException
     */
    public function resetConInuse() {
        global $currentuser;

        $perm = cRegistry::getPerm();
        $db = cRegistry::getDb();
        $cfg = cRegistry::getConfig();

        if ($perm->isSysadmin($currentuser)) {
            $sql = 'DELETE FROM ' . $cfg['tab']['inuse'];
            $db->query($sql);

            return $db->getErrorMessage() == '';
        } else {
            return false;
        }
    }

    /**
     * Clear the cache directory for a client.
     *
     * @param int $clientId
     *
     * @return bool
     *
     * @throws cInvalidArgumentException
     */
    public function clearClientCache($clientId) {
        global $currentuser;

        $perm = cRegistry::getPerm();
        $cfgClient = cRegistry::getClientConfig();

        if ($perm->isClientAdmin($clientId, $currentuser) || $perm->isSysadmin($currentuser)) {
            $cacheDir = $cfgClient[$clientId]['cache']['path'];
            if (cDirHandler::exists($cacheDir)) {
                return $this->clearDir($cacheDir, $cacheDir);
            }
            return false;
        } else {
            return false;
        }
    }

    /**
     * Clear the cache directory for a client.
     *
     * @param int  $clientId
     * @param bool $keep
     * @param int  $fileNumber
     *
     * @return bool
     *
     * @throws cInvalidArgumentException
     */
    public function clearClientHistory($clientId, $keep, $fileNumber) {
        global $currentuser;

        $perm = cRegistry::getPerm();
        $cfgClient = cRegistry::getClientConfig();

        if ($perm->isClientAdmin($clientId, $currentuser) || $perm->isSysadmin($currentuser)) {
            $versionDir = $cfgClient[$clientId]['version']['path'];
            if (cDirHandler::exists($versionDir)) {
                $tmpFile = [];
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
     * Clear the clients content versioning.
     *
     * @param int $idclient
     *
     * @return bool
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function clearClientContentVersioning($idclient) {
        global $currentuser;

        $perm = cRegistry::getPerm();

        if ($perm->isClientAdmin($idclient, $currentuser) || $perm->isSysadmin($currentuser)) {
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
     * Clear client log file.
     *
     * @param int $idclient
     *
     * @return bool
     *
     * @throws cInvalidArgumentException
     */
    public function clearClientLog($idclient) {
        global $currentuser;

        $perm = cRegistry::getPerm();
        $cfgClient = cRegistry::getClientConfig();

        if ($perm->isClientAdmin($idclient, $currentuser) || $perm->isSysadmin($currentuser)) {
            $logDir = $cfgClient[$idclient]['log']['path'];
            if (cDirHandler::exists($logDir)) {
                return $this->emptyFile($logDir, $this->_logFileTypes);
            }
            return false;
        } else {
            return false;
        }
    }

    /**
     * Clear CONTENIDO log files.
     *
     * @return bool
     *
     * @throws cInvalidArgumentException
     */
    public function clearConLog() {
        global $currentuser;

        $perm = cRegistry::getPerm();
        $cfg = cRegistry::getConfig();

        $logDir = $cfg['path']['contenido_logs'];
        if ($perm->isSysadmin($currentuser)) {
            if (cDirHandler::exists($logDir)) {
                return $this->emptyFile($logDir, $this->_logFileTypes);
            }
            return false;
        } else {
            return false;
        }
    }

    /**
     * Clear the cronjob directory.
     *
     * @return bool
     *
     * @throws cInvalidArgumentException
     */
    public function clearConCronjob() {
        global $currentuser;

        $perm = cRegistry::getPerm();
        $cfg = cRegistry::getConfig();

        $cronjobDir = $cfg['path']['contenido_cronlog'];
        if ($perm->isSysadmin($currentuser)) {
            if (cDirHandler::exists($cronjobDir)) {
                return $this->emptyFile($cronjobDir, $this->_cronjobFileTypes);
            }
            return false;
        } else {
            return false;
        }
    }

    /**
     * Clear the cache directory for a client.
     *
     * @return bool
     *
     * @throws cInvalidArgumentException
     */
    public function clearConCache() {
        global $currentuser;

        $perm = cRegistry::getPerm();
        $cfg = cRegistry::getConfig();

        $cacheDir = $cfg['path']['contenido_cache'];
        if ($perm->isSysadmin($currentuser)) {
            if (cDirHandler::exists($cacheDir)) {
                return $this->clearDir($cacheDir, $cacheDir);
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
     * @param int $idartlang
     *         the idartlang of the article
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
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
     * Delete all files and sub directories in a directory.
     *
     * @param string $dirPath
     * @param string $tmpDirPath
     *                            root directory not deleted
     * @param bool   $keep        [optional]
     * @param array  $tmpFileList [optional]
     *                            files are temporarily saved
     *
     * @return bool
     *
     * @throws cInvalidArgumentException
     */
    public function clearDir($dirPath, $tmpDirPath, $keep = false, &$tmpFileList = []) {
        if (cDirHandler::exists($dirPath) && false !== ($handle = cDirHandler::read($dirPath))) {
            $bCanDelete = false;
            $tmp = str_replace([
                '/',
                '..'
            ], '', $dirPath);
            foreach ($handle as $file) {
                if (!in_array($file, $this->_dirsExcludedWithFiles)) {
                    $filePath = $dirPath . '/' . $file;
                    $filePath = str_replace('//', '/', $filePath);
                    if (cDirHandler::exists($filePath)) {
                        $this->clearDir($filePath, $tmpDirPath, $keep, $tmpFileList);
                    } else {
                        if ($keep === false) {
                            cFileHandler::remove($filePath);
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

            if (str_replace([
                '/',
                '..'
                ], '', $dirPath) != str_replace([
                '/',
                '..'
                ], '', $tmpDirPath)
            && $keep === false) {
                // check if directory contains reserved files folders
                $bCanDelete = true;
                $dirContent = cDirHandler::read($dirPath);
                foreach ($dirContent as $sContent) {
                    if (in_array($sContent, $this->_dirsExcludedWithFiles)
                        || in_array($dirContent, $this->_dirsExcluded)) {
                        $bCanDelete = false;
                        break;
                    }
                }
                if (true === $bCanDelete
                    && in_array($dirName, $this->_dirsExcluded)) {
                    $bCanDelete = false;
                }
            }
            // reserved files or folders, do not delete
            if (true === $bCanDelete) {
                cDirHandler::remove($dirPath);
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * Empty a file content.
     *
     * @param string $dirPath
     * @param array  $types
     *
     * @return bool
     *
     * @throws cInvalidArgumentException
     */
    public function emptyFile($dirPath, $types) {
        $count = 0;
        $countCleared = 0;

        if (cDirHandler::exists($dirPath) && false !== ($handle = cDirHandler::read($dirPath))) {
            foreach ($handle as $file) {
                $fileParts = explode('.', $file);
                $fileExt = trim(end($fileParts));

                if ($file != '.' && $file != '..' && in_array($fileExt, $types)) {
                    $filePath = $dirPath . '/' . $file;

                    if (cFileHandler::exists($filePath) && cFileHandler::writeable($filePath)) {
                        $count++;

                        if (cFileHandler::truncate($filePath)) {
                            $countCleared++;
                        }
                    }
                }
            }

            // true if all files are cleaned
            return $count == $countCleared;
        }

        return false;
    }

    /**
     * Get frontend directory name for a client.
     *
     * @param int $clientId
     * @return string
     */
    public function getClientDir($clientId) {
        $cfgClient = cRegistry::getClientConfig();

        return $cfgClient[$clientId]['path']['frontend'];
    }

    /**
     * Set log file types.
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
     * Set cronjob file types.
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
