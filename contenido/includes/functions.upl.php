<?php
/**
 * This file contains the CONTENIDO upload functions.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Jan Lengowski, Timo Trautmann
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.file.php');


/**
 * Function reduces long path names and creates a dynamic tooltipp which shows
 * the full path name on mouseover
 *
 * @param string $sDisplayPath Original filepath
 * @param int $iLimit Limit of chars which were displayed directly. If the path
 *        string is shorter there will be no tooltipp
 * @return string Contains short path name and tooltipp if neccessary
 */
function generateDisplayFilePath($sDisplayPath, $iLimit) {
    $sDisplayPath = (string) $sDisplayPath;
    $iLimit = (int) $iLimit;

    if (strlen($sDisplayPath) > $iLimit) {
        $sDisplayPathShort = cApiStrTrimHard($sDisplayPath, $iLimit);

        $sTooltippString = '';
        $iCharcount = 0;

        $aPathFragments = explode('/', $sDisplayPath);

        foreach ($aPathFragments as $sFragment) {
            if ($sFragment != '') {
                if (strlen($sFragment) > ($iLimit - 5)) {
                    $sFragment = cApiStrTrimHard($sFragment, $iLimit);
                }

                if ($iCharcount + strlen($sFragment) + 1 > $iLimit) {
                    $sTooltippString .= '<br>' . $sFragment . '/';
                    $iCharcount = strlen($sFragment);
                } else {
                    $iCharcount = $iCharcount + 1 + strlen($sFragment);
                    $sTooltippString .= $sFragment . '/';
                }
            }
        }

        $sDisplayPath = '<span title="' . $sTooltippString . '" class="tooltip">' . $sDisplayPathShort . '</span>';
    }
    return $sDisplayPath;
}

/**
 * Returns array structure of passed directory.
 * Parses the directory recursively and
 * collects informations about found subdirectories.
 *
 * @param string $sCurrentDir Directory to parse
 * @param string $sStartDir Start directory. Will be used by recursion.
 * @param array $aFiles Files array structure. Will be used by recursion.
 * @param int $iDepth Nesting depth of found files. Will be used by recursion.
 * @param string $sPathString Path used to create full path to files. Will be
 *        used by recursion.
 * @return array Indexed arraay containing assoziative directory informations
 */
function uplDirectoryListRecursive($sCurrentDir, $sStartDir = '', $aFiles = array(), $iDepth = -1, $sPathString = '') {
    $iDepth++;

    $aDirsToExclude = uplGetDirectoriesToExclude();

    // remember where we started from
    if (empty($sStartDir)) {
        $sStartDir = $sCurrentDir;
    }

    if (chdir($sCurrentDir) == false) {
        return $aFiles;
    }
    $hDir = opendir('.');

    // list the files in the dir
    $aCurrentFiles = array();
    while (false !== ($file = readdir($hDir))) {
    //foreach(cDirHandler::read('.',false,true) as $key => $file){
        if (is_dir($file) && !in_array(strtolower($file), $aDirsToExclude)) {
            $aCurrentFiles[] = $file;
        }
    }
    sort($aCurrentFiles);

    foreach ($aCurrentFiles as $file) {
        $sFilePathName = getcwd() . '/' . $file;
        if ((filetype($sFilePathName) == 'dir') && (opendir($sFilePathName) !== false)) {
            $_aFile = array(
                'name' => $file,
                'depth' => $iDepth,
                'pathstring' => $sPathString . $file . '/'
            );

            $aFiles[] = $_aFile;

            $aFiles = uplDirectoryListRecursive($sFilePathName, getcwd(), $aFiles, $iDepth, $_aFile['pathstring']);
        }
    }

    closedir($hDir);
    chdir($sStartDir);
    return $aFiles;
}

/**
 * Checks if passed upload directory contains at least one file or directory
 *
 * @param string $sDir
 * @return bool
 * @todo Function name is misleading, should be renamed to uplIsEmpty
 */
function uplHasFiles($sDir) {
    global $client, $cfgClient;

    if (!$hDir = @opendir($cfgClient[$client]['upl']['path'] . $sDir)) {
        return false;
    }

    $bHasContent = false;
    if(is_dir($cfgClient[$client]['upl']['path'] . $sDir)){
    while (false !== ($sDirEntry = readdir($hDir))) {
        if ($sDirEntry != '.' && $sDirEntry != '..') {
            $bHasContent = true;
            break;
        }
    }
    closedir($hDir);
    }
    return $bHasContent;
}

/**
 * Checks if passed upload directory contains at least one directory
 *
 * @param string $sDir
 * @return bool
 */
function uplHasSubdirs($sDir) {
    global $client, $cfgClient;

    if (!$hDir = @opendir($cfgClient[$client]['upl']['path'] . $sDir)) {
        return true;
    }

    $bHasSubdir = false;
    if(is_dir($cfgClient[$client]['upl']['path'] . $sDir)){
    while (false !== ($sDirEntry = readdir($hDir))) {
        if ($sDirEntry != '.' && $sDirEntry != '..') {
            if (is_dir($cfgClient[$client]['upl']['path'] . $sDir . $sDirEntry)) {
                $bHasSubdir = true;
                break;
            }
        }
    }
    closedir($hDir);
    }

    return $bHasSubdir;
}

/**
 * Sync database contents with directory and vice versa.
 * - Removes all db entries pointing to non existing directories
 * - Removes all db entries pointing to non existing upload files
 * - Syncs found files in passed path with the database
 *
 * @param string $sPath Specifies the path to scan
 */
function uplSyncDirectory($sPath) {
    global $cfgClient, $client, $cfg, $db;

    if (cApiDbfs::isDbfs($sPath)) {
        return uplSyncDirectoryDBFS($sPath);
    }

    $oUploadsColl = new cApiUploadCollection();

    // get current upload directory, it's subdirectories and remove all database
    // entries pointing to a non existing upload directory on the file system
    $sql = 'SELECT DISTINCT(dirname) AS dirname FROM ' . $cfg['tab']['upl'] . ' WHERE ' . 'idclient=' . (int) $client . ' AND dirname LIKE "' . $db->escape($sPath) . '%"';
    $db->query($sql);
    while ($db->nextRecord()) {
        $sCurrDirname = $db->f('dirname');
        $sSubDir = substr($sCurrDirname, strlen($sPath));
        if (substr_count($sSubDir, '/') <= 1 && !cApiDbfs::isDbfs($sCurrDirname)) {
            // subdirectory is a direct descendant, process this directory too
            $sFullPath = $cfgClient[$client]['upl']['path'] . $sCurrDirname;
            if (!is_dir($sFullPath)) {
                $oUploadsColl->deleteByDirname($sCurrDirname);
            }
        }
    }

    // delete all db entries related to current directory without existing file
    // on file system
    $oUploadsColl->select("dirname='" . $oUploadsColl->escape($sPath) . "' AND idclient=" . (int) $client);
    while (($oUpload = $oUploadsColl->next()) !== false) {
        if (!cFileHandler::exists($cfgClient[$client]['upl']['path'] . $oUpload->get('dirname') . $oUpload->get('filename'))) {
            $oUploadsColl->delete($oUpload->get('idupl'));
        }
    }

    // sync all files in current directory with database
    $sFullPath = $cfgClient[$client]['upl']['path'] . $sPath;
    if (is_dir($sFullPath)) {
        $aDirsToExclude = uplGetDirectoriesToExclude();
        if (($hDir = opendir($sFullPath)) !== false) {
            while (false !== ($file = readdir($hDir))) {
            //foreach(cDirHandler::read($sDirectory,false,true) as $key => $file){
                if (!in_array(strtolower($file), $aDirsToExclude)) {
                    if (is_file($sFullPath . $file)) {
                        $oUploadsColl->sync($sPath, $file);
                    }
                }
            }
            closedir($hDir);
        }
    }
}

/**
 * Sync database contents with DBFS
 *
 * @param string $sPath Specifies the path to scan
 */
function uplSyncDirectoryDBFS($sPath) {
    global $cfgClient, $client, $cfg, $db;

    $oUploadsColl = new cApiUploadCollection();
    $oPropertiesColl = new cApiPropertyCollection();
    $oDBFSColl = new cApiDbfsCollection();

    if ($oDBFSColl->dir_exists($sPath)) {
        $sStripPath = cApiDbfs::stripPath($sPath);
        $oDBFSColl->select("dirname = '$sStripPath'");
        while (($oFile = $oDBFSColl->next()) !== false) {
            if ($oFile->get('filename') != '.') {
                $oUploadsColl->sync($sPath . "/", $oFile->get('filename'));
            }
        }
    }

    $oUploadsColl->select("dirname='$sPath/' AND idclient='$client'");
    while (($oUpload = $oUploadsColl->next()) !== false) {
        if (!$oDBFSColl->file_exists($oUpload->get('dirname') . $oUpload->get('filename'))) {
            $oUploadsColl->delete($oUpload->get("idupl"));
        }
    }

    $oPropertiesColl->select("idclient='$client' AND itemtype='upload' AND type='file' AND itemid LIKE '" . $sPath . "%'");
    while (($oProperty = $oPropertiesColl->next()) !== false) {
        if (!$oDBFSColl->file_exists($oProperty->get('itemid'))) {
            $oPropertiesColl->delete($oProperty->get('idproperty'));
        }
    }

    return;
}

/**
 * Creates a upload directory, either in filesystem or in dbfs.
 *
 * @param string $sPath Path to directory to create, either path from client
 *        upload
 *        directory or a dbfs path
 * @param string $sName Name of directory to create
 * @return string|void Octal value of filemode as string ('0702') or nothing
 */
function uplmkdir($sPath, $sName) {
    global $cfgClient, $client, $action;

    //Check DB filesystem
    if (cApiDbfs::isDbfs($sPath)) {
        $sPath = cApiDbfs::stripPath($sPath);
        $sFullPath = $sPath . '/' . $sName . '/.';

        $dbfs = new cApiDbfsCollection();
        $dbfs->create($sFullPath);
        return;
    }

    //Check directory name
    $dName = uplCreateFriendlyName($sName);
    $dName = strtr($dName, "'", '.');
    if ($dName != $sName) {
        $action = 'upl_mkdir';
        return '0703';
    }

    //Check dir or create new
    $dPath = $cfgClient[$client]['upl']['path'] . $sPath . $dName;
    if (cDirHandler::read($dPath) !== false) {
        //Directory already exist
        $action = 'upl_mkdir';
        return '0702';
    } else {
        //Create new dir
        return cDirHandler::create($dPath);
    }
}

/**
 * Renames a upload directory, updates all found upoad files containing the old
 * directory name and updates also all entries in propertoes table related to
 * affected upload files.
 *
 * @param string $sOldName
 * @param string $sNewName
 * @param string $sParent
 * @throws cException if the upload path can not be renamed
 */
function uplRenameDirectory($sOldName, $sNewName, $sParent) {
    global $cfgClient, $client, $cfg, $db;

    // rename directory
    $sOldUplPath = $cfgClient[$client]['upl']['path'] . $sParent . $sOldName;
    $sNewUplPath = $cfgClient[$client]['upl']['path'] . $sParent . $sNewName . '/';
    if (!$bResult = rename($sOldUplPath, $sNewUplPath)) {
        throw new cException("Couldn't rename upload path {$sOldUplPath} to {$sNewUplPath}");
    }

    // fetch all directory strings starting with the old path, and replace them
    // with the new path
    $oUploadColl = new cApiUploadCollection();
    $oUploadColl->select("idclient=" . (int) $client . " AND dirname LIKE '" . $oUploadColl->escape($sParent . $sOldName) . "%'");
    while (($oUpload = $oUploadColl->next()) !== false) {
        $sDirName = $oUpload->get('dirname');
        $sJunk = substr($sDirName, strlen($sParent) + strlen($sOldName));
        $sNewName2 = $sParent . $sNewName . $sJunk;
        $oUpload->set('dirname', $oUpload->escape($sNewName2), false);
        $oUpload->store();
    }

    // update all upload item properties starting with the old path, replace
    // itemid with the new path
    $oPropertyColl = new cApiPropertyCollection();
    $oPropertyColl->select("idclient=" . (int) $client . " AND itemtype='upload' AND type='file' AND itemid LIKE '" . $oPropertyColl->escape($sParent . $sOldName) . "%'");
    while (($oProperty = $oPropertyColl->next()) !== false) {
        $sDirName = $oProperty->get('itemid');
        $sJunk = substr($sDirName, strlen($sParent) + strlen($sOldName));
        $sNewName2 = $sParent . $sNewName . $sJunk;
        $oProperty->set('itemid', $oProperty->escape($sNewName2), false);
        $oProperty->store();
    }
}

/**
 * Parses passed directory recursively and stores some properties in TreeItem
 *
 * @param string $sDirectory
 * @param TreeItem $oRootItem
 * @param int $iLevel
 * @param string $sParent
 * @param int $iRenameLevel
 * @return array List of invalid directories
 */
function uplRecursiveDirectoryList($sDirectory, TreeItem $oRootItem, $iLevel, $sParent = '', $iRenameLevel = 0) {
    $aInvalidDirectories = array();

    $hDir = @opendir($sDirectory);

    if ($hDir) {
        $aDirsToExclude = uplGetDirectoriesToExclude();

        $aFiles = array();

        // list the files in the dir
        foreach(cDirHandler::read($sDirectory,false,true) as $key => $file){
        //while (false !== ($file = readdir($hDir))) {
            if (!in_array(strtolower($file), $aDirsToExclude)) {
                if (@chdir($sDirectory . $file . '/')) {
                    if (uplCreateFriendlyName($file) == $file) {
                        $aFiles[] = $file;
                    } else {
                        if ($_GET['force_rename'] == 'true') {
                            if ($iRenameLevel == 0 || $iRenameLevel == $iLevel) {
                                uplRenameDirectory($file, uplCreateFriendlyName($file), $sParent);
                                $iRenameLevel = $iLevel;
                                $aFiles[] = uplCreateFriendlyName($file);
                            } else {
                                $aInvalidDirectories[] = $file;
                            }
                        } else {
                            $aInvalidDirectories[] = $file;
                        }
                    }
                }
            }
        }

        sort($aFiles);
        foreach ($aFiles as $key => $file) {
            $oItem = new TreeItem($file, $sDirectory . $file . '/', true);
            $oItem->custom['level'] = $iLevel;
            $oItem->custom['lastitem'] = ($key == count($aFiles) - 1);
            $oItem->custom['parent'] = $sDirectory;

            $oRootItem->addItem($oItem);
            $aArrayTemp = uplRecursiveDirectoryList($sDirectory . $file . '/', $oItem, $iLevel + 1, $sParent . $file . '/', $iRenameLevel);
            $aInvalidDirectories = array_merge($aInvalidDirectories, $aArrayTemp);
            unset($oItem);
        }

        closedir($hDir);
    }

    return $aInvalidDirectories;
}

/**
 * Collects informations about all available dbfs directories stored in TreeItem
 *
 * @param string $directory Not used at te moment!
 * @param TreeItem $oRootItem
 * @param int $level Not used at te moment!
 */
function uplRecursiveDBDirectoryList($directory, TreeItem $oRootItem, $level, $client) {
    $dbfs = new cApiDbfsCollection();
    $dbfs->select("filename = '.' AND idclient=" . cSecurity::toInteger($client), 'dirname', 'dirname ASC');
    $count = 0;
    $lastlevel = 0;
    $item['.'] = $oRootItem;

    $prevobj = array(); // TODO: what was this array supposed to be?
    $lprevobj = new stdClass(); // TODO: what was this object supposed to be?

    while (($dbitem = $dbfs->next()) !== false) {
        $dirname = $dbitem->get('dirname');
        $level = substr_count($dirname, '/') + 2;
        $file = basename($dbitem->get('dirname'));
        $parent = dirname($dbitem->get('dirname'));

        if ($dirname != '.' && $file != '.') {
            $item[$dirname] = new TreeItem($file, 'dbfs:/' . $dirname, true);
            $item[$dirname]->custom['level'] = $level;
            $item[$dirname]->custom['parent'] = $parent;
            $item[$dirname]->custom['lastitem'] = true;

            if ($prevobj[$level]->custom['level'] == $level) {
                if (is_object($prevobj[$level])) {
                    $prevobj[$level]->custom['lastitem'] = false;
                }
            }

            if ($lastlevel > $level) {
                unset($prevobj[$lastlevel]);
                $lprevobj->custom['lastitem'] = true;
            }

            $prevobj[$level] = $item[$dirname];
            $lprevobj = $item[$dirname];

            $lastlevel = $level;

            if (is_object($item[$parent])) {
                $item[$parent]->addItem($item[$dirname]);
            }

            $count++;
        }
    }
}

/**
 * Returns thumbnail for a specific upload file
 *
 * @param string $sFile Filename to retrieve the thumbnail for
 * @param int $iMaxSize Thumb dimension (size of with and heigth)
 * @return string
 */
function uplGetThumbnail($sFile, $iMaxSize) {
    global $client, $cfgClient;

    if ($iMaxSize == -1) {
        return uplGetFileIcon($sFile);
    }

    $sFileType = strtolower(getFileType($sFile));

    switch ($sFileType) {
        case "png":
        case "gif":
        case "tiff":
        case "tif":
        case "bmp":
        case "jpeg":
        case "jpg":
        case "bmp":
        case "iff":
        case "xbm":
        case "wbmp":
            $img = cApiImgScale($cfgClient[$client]['upl']['path'] . $sFile, $iMaxSize, $iMaxSize, false, false, 50);
            if ($img !== false) {
                return $img;
            }
            $img = cApiImgScale(cRegistry::getBackendPath() . 'images/unknown.jpg', $iMaxSize, $iMaxSize, false, false, 50);
            if ($img !== false) {
                return $img;
            } else {
                return uplGetFileIcon($sFile);
            }
            break;
        default:
            return uplGetFileIcon($sFile);
            break;
    }
}

/**
 * Returns the icon for a file type
 *
 * @param string $sFile Filename to retrieve the extension for
 * @return string Icon for the file type
 */
function uplGetFileIcon($sFile) {
    global $cfg;

    $sPathFiletypes = cRegistry::getBackendUrl() . $cfg['path']['images'] . 'filetypes/';
    $sFileType = strtolower(getFileType($sFile));

    switch ($sFileType) {
        case "sxi":
        case "sti":
        case "pps":
        case "pot":
        case "kpr":
        case "pptx":
        case "potx":
        case "pptm":
        case "potm":
        case "ppt":
            $icon = "ppt.gif";
            break;
        case "doc":
        case "dot":
        case "sxw":
        case "stw":
        case "sdw":
        case "docx":
        case "dotx":
        case "docm":
        case "dotm":
        case "kwd":
            $icon = "word.gif";
            break;
        case "xls":
        case "sxc":
        case "stc":
        case "xlw":
        case "xlt":
        case "csv":
        case "ksp":
        case "xlsx":
        case "xltx":
        case "xlsm":
        case "xlsb":
        case "xltm":
        case "sdc":
            $icon = "excel.gif";
            break;
        case "txt":
        case "rtf":
            $icon = "txt.gif";
            break;
        case "gif":
            $icon = "gif.gif";
            break;
        case "png":
            $icon = "png.gif";
            break;
        case "jpeg":
        case "jpg":
            $icon = "jpg.gif";
            break;
        case "html":
        case "htm":
            $icon = "html.gif";
            break;
        case "lha":
        case "rar":
        case "arj":
        case "bz2":
        case "bz":
        case "gz":
        case "tar":
        case "tbz2":
        case "tbz":
        case "tgz":
        case "zip":
            $icon = "zip.gif";
            break;
        case "pdf":
            $icon = "pdf.gif";
            break;
        case "mov":
        case "avi":
        case "mpg":
        case "mpeg":
        case "wmv":
            $icon = "movie.gif";
            break;
        case "swf":
            $icon = "swf.gif";
            break;
        case "js":
            $icon = "js.gif";
            break;
        case "vcf":
            $icon = "vcf.gif";
            break;
        case "odf":
            $icon = "odf.gif";
            break;
        case "php":
            $icon = "php.gif";
            break;
        case "mp3":
        case "wma":
        case "ogg":
        case "mp4":
            $icon = "sound.gif";
            break;
        case "psd":
        case "ai":
        case "eps":
        case "cdr":
        case "qxp":
        case "ps":
            $icon = "design.gif";
            break;
        case "css":
            $icon = "css.gif";
            break;
        default:
            if (cFileHandler::exists($sPathFiletypes . $sFileType . '.gif')) {
                $icon = $sFileType . '.gif';
            } else {
                $icon = "unknown.gif";
            }
            break;
    }

    return $sPathFiletypes . $icon;
}

/**
 * Returns the description for a file type
 *
 * @param string $sExtension Extension to use
 * @return string Text for the file type
 */
function uplGetFileTypeDescription($sExtension) {
    global $cfg;

    switch ($sExtension) {
        // Presentation files
        case "sxi":
            return (i18n("OpenOffice.org Presentation"));
        case "sti":
            return (i18n("OpenOffice.org Presentation Template"));
        case "pps":
            return (i18n("Microsoft PowerPoint Screen Presentation"));
        case "pot":
            return (i18n("Microsoft PowerPoint Presentation Template"));
        case "kpr":
            return (i18n("KDE KPresenter Document"));
        case "ppt":
            return (i18n("Microsoft PowerPoint Presentation Template"));

        // Document files
        case "doc":
            return (i18n("Microsoft Word Document or regular text file"));
        case "dot":
            return (i18n("Microsoft Word Template"));
        case "sxw":
            return (i18n("OpenOffice.org Text Document"));
        case "stw":
            return (i18n("OpenOffice.org Text Document Template"));
        case "sdw":
            return (i18n("StarOffice 5.0 Text Document"));
        case "kwd":
            return (i18n("KDE KWord Document"));

        // Spreadsheet files
        case "xls":
            return (i18n("Microsoft Excel Worksheet"));
        case "sxc":
            return (i18n("OpenOffice.org Table"));
        case "stc":
            return (i18n("OpenOffice.org Table Template"));
        case "xlw":
            return (i18n("Microsoft Excel File"));
        case "xlt":
            return (i18n("Microsoft Excel Template"));
        case "csv":
            return (i18n("Comma Seperated Value File"));
        case "ksp":
            return (i18n("KDE KSpread Document"));
        case "sdc":
            return (i18n("StarOffice 5.0 Table"));

        // Text types
        case "txt":
            return (i18n("Plain Text"));
        case "rtf":
            return (i18n("Rich Text Format"));

        // Images
        case "gif":
            return (i18n("GIF Image"));
        case "png":
            return (i18n("PNG Image"));
        case "jpeg":
            return (i18n("JPEG Image"));
        case "jpg":
            return (i18n("JPEG Image"));
        case "tif":
            return (i18n("TIFF Image"));
        case "psd":
            return (i18n("Adobe Photoshop Image"));

        // HTML
        case "html":
            return (i18n("Hypertext Markup Language Document"));
        case "htm":
            return (i18n("Hypertext Markup Language Document"));
        case "css":
            return (i18n("Cascading Style Sheets"));

        // Archives
        case "lha":
            return (i18n("LHA Archive"));
        case "rar":
            return (i18n("RAR Archive"));
        case "arj":
            return (i18n("ARJ Archive"));
        case "bz2":
            return (i18n("bz2-compressed File"));
        case "bz":
            return (i18n("bzip-compressed File"));
        case "zip":
            return (i18n("ZIP Archive"));
        case "tar":
            return (i18n("TAR Archive"));
        case "gz":
            return (i18n("GZ Compressed File"));

        // Source files
        case "c":
            return (i18n("C Program Code"));
        case "c++":
        case "cc":
        case "cpp":
            return (i18n("C++ Program Code"));
        case "hpp":
        case "h":
            return (i18n("C or C++ Program Header"));
        case "php":
        case "php3":
        case "php4":
            return (i18n("PHP Program Code"));
        case "phps":
            return (i18n("PHP Source File"));

        case "pdf":
            return (i18n("Adobe Acrobat Portable Document"));

        // Movies
        case "mov":
            return (i18n("QuickTime Movie"));
        case "avi":
            return (i18n("avi Movie"));
        case "mpg":
        case "mpeg":
            return (i18n("MPEG Movie"));
        case "wmv":
            return (i18n("Windows Media Video"));
        case "mp4":
            return (i18n("MPEG-4 Movie"));

        default:
            return (i18n($sExtension . "-File"));
    }
}

/**
 * Removes unwanted characters from passed filename.
 *
 * @param string $sFilename
 * @return string
 */
function uplCreateFriendlyName($filename) {
    global $cfg, $lang;

    $oLang = new cApiLanguage();
    $oLang->loadByPrimaryKey($lang);

    if (!is_array($cfg['upl']['allow_additional_chars'])) {
        $filename = str_replace(" ", "_", $filename);
    } elseif (in_array(' ', $cfg['upl']['allow_additional_chars']) === FALSE) {
        $filename = str_replace(" ", "_", $filename);
    }

    $chars = '';
    if (is_array($cfg['upl']['allow_additional_chars'])) {
        $chars = implode("", $cfg['upl']['allow_additional_chars']);
        $chars = str_replace(array(
            '-',
            '[',
            ']'
        ), '', $chars);
    }

    $filename = cApiStrReplaceDiacritics($filename, strtoupper($oLang->getField('encoding')));
    $filename = preg_replace("/[^A-Za-z0-9._\-" . $chars . "]/i", '', $filename);

    return $filename;
}

function uplSearch($searchfor) {
    $client = cRegistry::getClientId();
    $client = cSecurity::toInteger($client);
    $lang = cRegistry::getLanguageId();
    $lang = cSecurity::toInteger($lang);

    $uploadsColl = new cApiUploadCollection();
    $uplMetaColl = new cApiUploadMetaCollection();

    $searchfordb = $uplMetaColl->escape($searchfor);

    $items = array();

    // Search for description, ranking *5
    $uplMetaColl->link('cApiUploadCollection');
    $uplMetaColl->setWhereGroup('description', 'capiuploadcollection.idclient', $client);
    $uplMetaColl->setWhereGroup('description', 'capiuploadmetacollection.idlang', $lang);
    $uplMetaColl->setWhereGroup('description', 'capiuploadmetacollection.description', '%' . $searchfordb . '%', 'LIKE');
    $uplMetaColl->query();
    while (($item = $uplMetaColl->next()) !== false) {
        $items[$item->get('idupl')] += (substr_count(strtolower($item->get('description')), strtolower($searchfor)) * 5);
    }

    // Search for medianame, ranking *4
    $uplMetaColl->resetQuery();
    $uplMetaColl->link('cApiUploadCollection');
    $uplMetaColl->setWhereGroup('medianame', 'capiuploadcollection.idclient', $client);
    $uplMetaColl->setWhereGroup('medianame', 'capiuploadmetacollection.idlang', $lang);
    $uplMetaColl->setWhereGroup('medianame', 'capiuploadmetacollection.medianame', '%' . $searchfordb . '%', 'LIKE');
    $uplMetaColl->query();
    while (($item = $uplMetaColl->next()) !== false) {
        $items[$item->get('idupl')] += (substr_count(strtolower($item->get('medianame')), strtolower($searchfor)) * 4);
    }

    // Search for file name, ranking +4
    $uploadsColl->select("idclient='" . $client . "' AND filename LIKE '%" . $searchfordb . "%'");
    while (($item = $uploadsColl->next()) !== false) {
        $items[$item->get('idupl')] += 4;
    }

    // Search for keywords, ranking *3
    $uplMetaColl->resetQuery();
    $uplMetaColl->link('cApiUploadCollection');
    $uplMetaColl->setWhereGroup('keywords', 'capiuploadcollection.idclient', $client);
    $uplMetaColl->setWhereGroup('keywords', 'capiuploadmetacollection.idlang', $lang);
    $uplMetaColl->setWhereGroup('keywords', 'capiuploadmetacollection.keywords', '%' . $searchfordb . '%', 'LIKE');
    $uplMetaColl->query();
    while (($item = $uplMetaColl->next()) !== false) {
        $items[$item->get('idupl')] += (substr_count(strtolower($item->get('keywords')), strtolower($searchfor)) * 3);
    }

    // Search for copyright, ranking *2
    $uplMetaColl->resetQuery();
    $uplMetaColl->link('cApiUploadCollection');
    $uplMetaColl->setWhereGroup('copyright', 'capiuploadcollection.idclient', $client);
    $uplMetaColl->setWhereGroup('copyright', 'capiuploadmetacollection.idlang', $lang);
    $uplMetaColl->setWhereGroup('copyright', 'capiuploadmetacollection.copyright', '%' . $searchfordb . '%', 'LIKE');
    $uplMetaColl->query();
    while (($item = $uplMetaColl->next()) !== false) {
        $items[$item->get('idupl')] += (substr_count(strtolower($item->get('copyright')), strtolower($searchfor)) * 2);
    }

    // Search for internal_notice, ranking *1
    $uplMetaColl->resetQuery();
    $uplMetaColl->link('cApiUploadCollection');
    $uplMetaColl->setWhereGroup('internal_notice', 'capiuploadcollection.idclient', $client);
    $uplMetaColl->setWhereGroup('internal_notice', 'capiuploadmetacollection.idlang', $lang);
    $uplMetaColl->setWhereGroup('internal_notice', 'capiuploadmetacollection.internal_notice', '%' . $searchfordb . '%', 'LIKE');
    $uplMetaColl->query();
    while (($item = $uplMetaColl->next()) !== false) {
        $items[$item->get('idupl')] += (substr_count(strtolower($item->get('internal_notice')), strtolower($searchfor)));
    }

    return $items;
}

/**
 * Returns file extension
 *
 * @param string $sFile
 * @return string
 */
function uplGetFileExtension($sFile) {
    // Fetch the dot position
    $iDotPosition = strrpos($sFile, '.');
    $sExtension = substr($sFile, $iDotPosition + 1);
    if (strpos($sExtension, '/') !== false) {
        return false;
    } else {
        return $sExtension;
    }
}

/**
 * Returns list of directory names to exclude e.
 * g. from directory listings.
 *
 * @return array
 */
function uplGetDirectoriesToExclude() {
    static $mDirsToExclude = null;
    if (isset($mDirsToExclude)) {
        return $mDirsToExclude;
    }

    $mDirsToExclude = trim(getSystemProperty('system', 'upldirlist-dirstoexclude'));
    if ($mDirsToExclude === '') {
        $mDirsToExclude = '.,..,.svn,.cvs';
        setSystemProperty('system', 'upldirlist-dirstoexclude', $mDirsToExclude);
    }
    $mDirsToExclude = explode(',', $mDirsToExclude);
    foreach ($mDirsToExclude as $pos => $item) {
        $mDirsToExclude[$pos] = trim($item);
    }
    return $mDirsToExclude;
}
