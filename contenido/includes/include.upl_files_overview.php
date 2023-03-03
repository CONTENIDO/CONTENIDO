<?php

/**
 * This file contains the backend page for displaying files of a directory in upload section.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var cApiUser $currentuser
 * @var cAuth $auth
 * @var cGuiNotification $notification
 * @var cPermission $perm
 * @var string $area
 */

global $upl_last_path;

$backendPath = cRegistry::getBackendPath();
$sess = cRegistry::getSession();
$client = cRegistry::getClientId();
$cfgClient = cRegistry::getClientConfig();
$action = cRegistry::getAction();

cInclude('includes', 'api/functions.frontend.list.php');
cInclude('includes', 'functions.file.php');

$page = new cGuiPage('upl_files_overview', '', 0);

$oClient = cRegistry::getClient();
$oLanguage = cRegistry::getLanguage();

// Display critical error if client or language does not exist
if (!$oClient->isLoaded() || !$oLanguage->isLoaded()) {
    $message = !$oClient->isLoaded() ? i18n('No Client selected') : i18n('No language selected');
    $oPage = new cGuiPage("upl_files_upload");
    $oPage->displayCriticalError($message);
    $oPage->render();
    return;
}

$client = cSecurity::toInteger(cRegistry::getClientId());
$lang = cSecurity::toInteger(cRegistry::getLanguageId());
$cfgClient = cRegistry::getClientConfig();

$appendparameters = $_REQUEST['appendparameters'] ?? '';
$file             = cSecurity::escapeString($_REQUEST['file'] ?? '');
$startpage        = cSecurity::toInteger($_REQUEST['startpage'] ?? '1');
$sortby           = cSecurity::escapeString($_REQUEST['sortby'] ?? '');
$sortmode         = cSecurity::escapeString($_REQUEST['sortmode'] ?? '');
$thumbnailmode    = cSecurity::escapeString($_REQUEST['thumbnailmode'] ?? '');

if (!empty($file)) {
    $file = basename($file);
}

if ((empty($browserparameters) || !is_array($browserparameters)) && ($appendparameters != 'imagebrowser' || $appendparameters != 'filebrowser')) {
    $browserparameters = [];
}

if (!$sess->isRegistered('upl_last_path')) {
    // register last path (upl_last_path) in session
    $sess->register('upl_last_path');
} elseif (!isset($path)) {
    // if no path is given the last path is used
    $path = $upl_last_path ?? '';
}

// if path doesn't exist use root path this might happen when the last path
// is that of another client or deleted outside CONTENIDO
if (empty($path) || (!cApiDbfs::isDbfs($path) && !cFileHandler::exists($cfgClient[$client]['upl']['path'] . $path))) {
    $path = '';
}
// remember current path as last path
$upl_last_path = $path;

$uploadCollection = new cApiUploadCollection();

$dbfsCollection = new cApiDbfsCollection();

if (cApiDbfs::isDbfs($path)) {
    $qpath = $path . '/';
} else {
    $qpath = $path;
}

if ((is_writable($cfgClient[$client]['upl']['path'] . $path) || cApiDbfs::isDbfs($path)) && (int) $client > 0) {
    $bDirectoryIsWritable = true;
} else {
    $bDirectoryIsWritable = false;
}

if ($action === 'upl_modify_file' && !empty($file)) {

    $extractFolder = NULL;
    $uplPath = $cfgClient[$client]['upl']['path'];

    $requestPath = cSecurity::escapeString($_REQUEST['path'] ?? '');
    if (!empty($requestPath)) {
        $uplPath .= $requestPath;
    }

    $extractFolder = cSecurity::escapeString($_REQUEST['efolder'] ?? '');

    $extractZip = cSecurity::toBoolean($_REQUEST['extractZip'] ?? '0');
    $overwrite = cSecurity::toBoolean($_REQUEST['overwrite'] ?? '0');

    if ($extractZip && !$overwrite) {
        $zipFile = $uplPath . cSecurity::escapeString($_REQUEST['file']);
        cZipArchive::extract($zipFile, $uplPath, $extractFolder);
    }
    if ($extractZip && $overwrite) {
        $zipFile = $uplPath . cSecurity::escapeString($_REQUEST['file'] ?? '');
        cZipArchive::extractOverRide($zipFile, $uplPath, $extractFolder);
    }

    // Did the user upload a new file?
    if ($bDirectoryIsWritable && count($_FILES) == 1 && ($_FILES['file']['size'] > 0) && ($_FILES['file']['name'] != '')) {
        if ($_FILES['file']['tmp_name'] != '') {
            $tmp_name = $_FILES['file']['tmp_name'];
            $_cecIterator = $_cecRegistry->getIterator('Contenido.Upload.UploadPreprocess');

            if ($_cecIterator->count() > 0) {
                // Copy file to a temporary location
                move_uploaded_file($tmp_name, $backendPath . $cfg['path']['temp'] . $file);
                $tmp_name = $backendPath . $cfg['path']['temp'] . $file;

                while ($chainEntry = $_cecIterator->next()) {
                    if (cApiDbfs::isDbfs($path)) {
                        $sPathPrepend = '';
                        $sPathAppend = '/';
                    } else {
                        $sPathPrepend = $cfgClient[$client]['upl']['path'];
                        $sPathAppend = '';
                    }

                    $modified = $chainEntry->execute($tmp_name, $sPathPrepend . $path . $sPathAppend . uplCreateFriendlyName($_FILES['file']['name']));

                    if ($modified !== false) {
                        $tmp_name = $modified;
                    }
                }
            }

            if (cApiDbfs::isDbfs($path)) {
                $dbfsCollection->writeFromFile($tmp_name, $qpath . $file);
                unlink($_FILES['file']['tmp_name']);
            } else {
                unlink($cfgClient[$client]['upl']['path'] . $path . $file);

                if (is_uploaded_file($tmp_name)) {
                    move_uploaded_file($tmp_name, $cfgClient[$client]['upl']['path'] . $path . $file);
                } else {
                    rename($tmp_name, $cfgClient[$client]['upl']['path'] . $path . $file);
                }
            }
        }
    }

    $uploadCollection->select("idclient = '$client' AND dirname='" . $uploadCollection->escape($qpath) . "' AND filename='" . $uploadCollection->escape($file) . "'");
    $upload = $uploadCollection->next();
    if ($upload) {
        // $upload->set('description', stripslashes($description));
        $upload->store();
    }

    $protected = !empty($_REQUEST['protected']) && $_REQUEST['protected'] === '1' ? '1' : '';
    $properties = new cApiPropertyCollection();
    $properties->setValue('upload', $qpath . $file, 'file', 'protected', $protected);

    $timeMgmt = !empty($_REQUEST['timemgmt']) && $_REQUEST['timemgmt'] === '1' ? '1' : '';
    $properties->setValue('upload', $qpath . $file, 'file', 'timemgmt', $timeMgmt);
    if ($timeMgmt) {
        $dateStart = cSecurity::escapeString($_REQUEST['datestart'] ?? '');
        $dateEnd = cSecurity::escapeString($_REQUEST['dateend'] ?? '');
        $properties->setValue('upload', $qpath . $file, 'file', 'datestart', $dateStart);
        $properties->setValue('upload', $qpath . $file, 'file', 'dateend', $dateEnd);
    }

    $author = $auth->auth['uid'];
    $created = date('Y-m-d H:i:s');

    /**
     * Form variables
     * @var string $medianame
     * @var string $description
     * @var string $medianame
     * @var string $keywords
     * @var string $medianotes
     * @var string $copyright
     */

    $iIdupl = is_object($upload) ? $upload->get('idupl') : 0;
    if (!empty($iIdupl) && $iIdupl > 0) {
        // check for new entry:
        $oUploadMeta = new cApiUploadMeta((int) $iIdupl);
        if ($oUploadMeta->loadByUploadIdAndLanguageId($iIdupl, $lang)) {
            // Update existing entry
            $oUploadMeta->set('medianame', $medianame);
            $oUploadMeta->set('description', $description);
            $oUploadMeta->set('keywords', $keywords);
            $oUploadMeta->set('internal_notice', $medianotes);
            $oUploadMeta->set('copyright', $copyright);
            $oUploadMeta->set('modified', $created);
            $oUploadMeta->set('modifiedby', $author);
            $oUploadMeta->store();
        } else {
            // Create new entry
            $oUploadMetaColl = new cApiUploadMetaCollection();
            $oUploadMeta = $oUploadMetaColl->create($iIdupl, $lang, $medianame, $description, $keywords, $medianotes, $copyright, $author, $created, $created, $author);
        }
    }
}

// Delete upload folder, but only id it is empty!
if ($action === 'upl_delete' && $perm->have_perm_area_action($area, $action) && $bDirectoryIsWritable) {
    $res = null;
    // The application logic prevents deleting upload folder having a subfolder or a file,
    // therefore we simply delete empty upload folder.
    // There is also no need to call chains for deleting empty upload folders.

    if (cApiDbfs::isDbfs($path)) {
        if (!$dbfsCollection->hasFiles($path)) {
            $res = $dbfsCollection->remove($path . '/.');
        }
    } else {
        // Check for files
        if (!uplHasFiles($path)) {
            $res = @rmdir($cfgClient[$client]['upl']['path'] . $path);
        }
    }
    if ($res === false) {
        $notification->displayNotification('warning', sprintf(i18n("Failed to remove directory %s"), $path));
    }
}

// Delete single file or multiple files
if ($action === 'upl_multidelete' && $perm->have_perm_area_action($area, $action) && $bDirectoryIsWritable) {
    $fdelete = $_REQUEST['fdelete'] ?? '';
    if (is_array($fdelete)) {
        // array of cApiUpload objects to be passed to chain function
        $uploadObjects = [];

        // Check if it is in the upload table
        foreach ($fdelete as $fileNameToDelete) {
            $fileNameToDelete = basename(cSecurity::escapeString($fileNameToDelete));
            $uploadCollection->select("idclient = '$client' AND dirname='" . $uploadCollection->escape($qpath) . "' AND filename='" . $uploadCollection->escape($fileNameToDelete) . "'");
            if (false !== $item = $uploadCollection->next()) {
                if (cApiDbfs::isDbfs($qpath)) {
                    $dbfsCollection->remove($qpath . $fileNameToDelete);

                    // call chain once for each deleted file
                    $_cecIterator = cRegistry::getCecRegistry()->getIterator('Contenido.Upl_edit.Delete');
                    if ($_cecIterator->count() > 0) {
                        while (false !== $chainEntry = $_cecIterator->next()) {
                            $chainEntry->execute($item->get('idupl'), $qpath, $fileNameToDelete);
                        }
                    }
                } else {
                    $uploadCollection->delete($item->get('idupl'));
                }

                // add current upload object to array in order to be processed
                $uploadObjects[] = $item;
            }
        }

        // call chain once for all deleted files
        $_cecIterator = cRegistry::getCecRegistry()->getIterator('Contenido.Upl_edit.DeleteBatch');
        if ($_cecIterator->count() > 0) {
            while (false !== $chainEntry = $_cecIterator->next()) {
                $chainEntry->execute($uploadObjects);
            }
        }
    }
}

if ($action === 'upl_upload' && $bDirectoryIsWritable) {
    if ($perm->have_perm_area_action($area, 'upl_upload')) {
        if (count($_FILES) == 1) {
            foreach ($_FILES['file']['name'] as $key => $value) {
                if ($_FILES['file']['tmp_name'][$key] != '') {
                    $tmp_name = $_FILES['file']['tmp_name'][$key];
                    $_cecIterator = $_cecRegistry->getIterator('Contenido.Upload.UploadPreprocess');

                    if ($_cecIterator->count() > 0) {
                        // Copy file to a temporary location
                        move_uploaded_file($tmp_name, $backendPath . $cfg['path']['temp'] . $_FILES['file']['name'][$key]);
                        $tmp_name = $backendPath . $cfg['path']['temp'] . $_FILES['file']['name'][$key];

                        while (false !== $chainEntry = $_cecIterator->next()) {
                            if (cApiDbfs::isDbfs($path)) {
                                $sPathPrepend = '';
                                $sPathAppend = '/';
                            } else {
                                $sPathPrepend = $cfgClient[$client]['upl']['path'];
                                $sPathAppend = '';
                            }

                            $modified = $chainEntry->execute($tmp_name, $sPathPrepend . $path . $sPathAppend . uplCreateFriendlyName($_FILES['file']['name'][$key]));
                            if ($modified !== false) {
                                $tmp_name = $modified;
                            }
                        }
                    }

                    if (cApiDbfs::isDbfs($qpath)) {
                        $dbfsCollection->writeFromFile($tmp_name, $qpath . uplCreateFriendlyName($_FILES['file']['name'][$key]));
                        unlink($tmp_name);
                    } else {
                        if (is_uploaded_file($tmp_name)) {
                            $final_filename = $cfgClient[$client]['upl']['path'] . $path . uplCreateFriendlyName($_FILES['file']['name'][$key]);

                            move_uploaded_file($tmp_name, $final_filename);

                            $iterator = $_cecRegistry->getIterator('Contenido.Upload.UploadPostprocess');
                            while ($chainEntry = $iterator->next()) {
                                $chainEntry->execute($final_filename);
                            }
                        } else {
                            rename($tmp_name, $cfgClient[$client]['upl']['path'] . $path . uplCreateFriendlyName($_FILES['file']['name'][$key]));
                        }
                    }
                }
            }
        }
    } else {
        $page->displayError(i18n("Permission denied"));
        $page->render();
        die();
    }
}

if ($action === 'upl_renamefile' && $bDirectoryIsWritable) {
    $oldname = basename(cSecurity::escapeString($oldname));
    $newname = basename(cSecurity::escapeString($newname));
    rename($cfgClient[$client]['upl']['path'] . $path . $oldname, $cfgClient[$client]['upl']['path'] . $path . $newname);
}

/**
 * @author unknown
 */
class UploadList extends FrontendList {

    /**
     *
     * @var string
     */
    protected $_dark;

    /**
     *
     * @var int
     */
    protected $_size;

    /**
     * @var int
     */
    protected $_data_count = 0;

    /**
     * Field converting facility.
     *
     * @see FrontendList::convert()
     *
     * @param int   $field
     *         Field index
     * @param mixed $data
     *         Field value
     *
     * @return mixed
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function convert($field, $data) {
        global $path, $appendparameters, $startpage, $sortby, $sortmode, $thumbnailmode;

        $cfg = cRegistry::getConfig();
        $sess = cRegistry::getSession();
        $client = cRegistry::getClientId();
        $cfgClient = cRegistry::getClientConfig();
        $backendUrl = cRegistry::getBackendUrl();

        if ($field == 4) {
            return humanReadableSize($data);
        }

        if ($field == 3) {
            if ($appendparameters == 'imagebrowser' || $appendparameters == 'filebrowser') {
                // fix for IE11 popup:
                // selecting link with tiny out of popup does not work with Con.getFrame in IE11
                // reverting to the old call solves this problem
                if (cApiDbfs::isDbfs($path . '/' . $data)) {
                    $mstr = '<a href="javascript:void(0)" onclick="parent.parent.frames[\'left\'].frames[\'left_top\'].document.getElementById(\'selectedfile\').value= \'' . $cfgClient[$client]['htmlpath']['frontend'] . 'dbfs.php?file=' . $path . '/' . $data . '\'; window.returnValue=\'' . $cfgClient[$client]['htmlpath']['frontend'] . 'dbfs.php?file=' . $path . '/' . $data . '\'; window.close();"><img alt="" src="' . $backendUrl . $cfg['path']['images'] . 'but_ok.gif" title="' . i18n("Use file") . '">&nbsp;' . $data . '</a>';
                } else {
                    $mstr = '<a href="javascript:void(0)" onclick="parent.parent.frames[\'left\'].frames[\'left_top\'].document.getElementById(\'selectedfile\').value= \'' . $cfgClient[$client]['htmlpath']['frontend'] . $cfgClient[$client]['upl']['frontendpath'] . $path . $data . '\'; window.returnValue=\'' . $cfgClient[$client]['htmlpath']['frontend'] . $cfgClient[$client]['upl']['frontendpath'] . $path . $data . '\'; window.close();"><img alt="" src="' . $backendUrl . $cfg['path']['images'] . 'but_ok.gif" title="' . i18n("Use file") . '">&nbsp;' . $data . '</a>';
                }
            } else {
                $tmp_mstr = '<a href="javascript:Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>';
                $mstr = sprintf($tmp_mstr, 'right_bottom', $sess->url("main.php?area=upl_edit&frame=4&path=$path&file=$data&appendparameters=$appendparameters&startpage=" . $startpage . "&sortby=" . $sortby . "&sortmode=" . $sortmode . "&thumbnailmode=" . $thumbnailmode), 'right_top', $sess->url("main.php?area=upl&frame=3&path=$path&file=$data"), $data);
            }
            return $mstr;
        }

        if ($field == 5) {
            return uplGetFileTypeDescription($data);
        }

        if ($field == 2) {
            // If this file is an image, try to open
            $fileType = cString::toLowerCase(cFileHandler::getExtension($data));
            switch ($fileType) {
                case 'bmp':
                case 'gif':
                case 'iff':
                case 'jpeg':
                case 'jpg':
                case 'png':
                case 'tif':
                case 'tiff':
                case 'wbmp':
                case 'webp':
                case 'xbm':
                    $frontendURL = cRegistry::getFrontendUrl();

                    $sCacheThumbnail = uplGetThumbnail($data, 150);
                    $sCacheName = basename($sCacheThumbnail);
                    $sFullPath = $cfgClient[$client]['cache']['path'] . $sCacheName;
                    if (cFileHandler::isFile($sFullPath)) {
                        $aDimensions = getimagesize($sFullPath);
                        $iWidth = $aDimensions[0];
                        $iHeight = $aDimensions[1];
                    } else {
                        $iWidth = 0;
                        $iHeight = 0;
                    }

                    if (cApiDbfs::isDbfs($data)) {
                        $href = $frontendURL . 'dbfs.php?file=' . $data;
                    } else {
                        $href = $frontendURL . $cfgClient[$client]['upload'] . $data;
                    }
                    return '<a href="' . $href . '" data-action="zoom" data-action-mouseover="zoom">
                           <img class="hover" alt="" src="' . $sCacheThumbnail . '" data-width="' . $iWidth . '" data-height="' . $iHeight . '">
                           <img class="preview" alt="" src="' . $sCacheThumbnail . '">
                       </a>';
                default:
                    $sCacheThumbnail = uplGetThumbnail($data, 150);
                    return '<img class="hover_none" alt="" src="' . $sCacheThumbnail . '">';
            }
        }

        return $data;
    }

    /**
     * @return int $size
     */
    public function getSize() {
        return $this->_size;
    }

    /**
     * @param int $size
     */
    public function setSize($size) {
        $this->_size = $size;
    }

    /**
     * Sets the total count of data entries. This is needed for calculating the pages.
     * @param int $totalUploadsCount
     */
    public function setDataCount($totalUploadsCount) {
        $this->_data_count = $totalUploadsCount;
    }

    /**
     * Returns the number of pages.
     * If the data count variable is set it will be used instead counting the data array.
     * @return float|int
     */
    public function getNumPages() {
        if ($this->_data_count > 0) {
            return ceil($this->_data_count / $this->_resultsPerPage);
        }

        return parent::getNumPages();
    }

    /**
     * Outputs or optionally returns
     *
     * @param bool $return
     *         if true, returns the list
     *
     * @return string|void
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function output($return = false) {
        // if the data count variable is not set, proceed with the previous logic
        if ($this->_data_count === 0) {
            return parent::output($return);
        }

        // if the data count variable is set, display all contents from data array

        $output = $this->_startwrap;

        $count = count($this->_data);

        for ($i = 1; $i <= $count; $i++) {
            $currentPos = $i - 1;
            if (is_array($this->_data[$currentPos])) {
                $items = "";
                foreach ($this->_data[$currentPos] as $key => $value) {
                    $items .= ", '" . addslashes($this->convert($key, $value)) . "'";
                }

                $itemWrap = str_replace('{LIST_ITEM_POS}', $currentPos, $this->_itemwrap);
                $execute = '$output .= sprintf($itemWrap ' . $items . ');';
                eval($execute);
            }
        }

        $output .= $this->_endwrap;

        $output = stripslashes($output);

        if ($return == true) {
            return $output;
        } else {
            echo $output;
        }
    }

}

uplSyncDirectory($path);

if ($sortby == '') {
    $sortby = 3;
    $sortmode = 'ASC';
}

if ($startpage == '') {
    $startpage = 1;
}

$thisfile = $sess->url("main.php?idarea=$area&frame=$frame&path=$path&thumbnailmode=$thumbnailmode&appendparameters=$appendparameters");
$scrollthisfile = $thisfile . "&sortmode=$sortmode&sortby=$sortby&appendparameters=$appendparameters";

if ($sortby == 3 && $sortmode == 'DESC') {
    $fnsort = '<a class="gray" href="' . $thisfile . '&sortby=3&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Filename / Description") . '<img src="images/sort_down.gif" alt=""></a>';
} else {
    if ($sortby == 3) {
        $fnsort = '<a class="gray" href="' . $thisfile . '&sortby=3&sortmode=DESC&startpage=' . $startpage . '">' . i18n("Filename / Description") . '<img src="images/sort_up.gif" alt=""></a>';
    } else {
        $fnsort = '<a class="gray" href="' . $thisfile . '&sortby=3&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Filename / Description") . '</a>';
    }
}

if ($sortby == 4 && $sortmode == 'DESC') {
    $sizesort = '<a class="gray" href="' . $thisfile . '&sortby=4&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Size") . '<img src="images/sort_down.gif" alt=""></a>';
} else {
    if ($sortby == 4) {
        $sizesort = '<a class="gray" href="' . $thisfile . '&sortby=4&sortmode=DESC&startpage=' . $startpage . '">' . i18n("Size") . '<img src="images/sort_up.gif" alt=""></a>';
    } else {
        $sizesort = '<a class="gray" href="' . $thisfile . '&sortby=4&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Size") . "</a>";
    }
}

if ($sortby == 5 && $sortmode == 'DESC') {
    $typesort = '<a class="gray" href="' . $thisfile . '&sortby=5&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Type") . '<img src="images/sort_down.gif" alt=""></a>';
} else {
    if ($sortby == 5) {
        $typesort = '<a class="gray" class="gray" href="' . $thisfile . '&sortby=5&sortmode=DESC&startpage=' . $startpage . '">' . i18n("Type") . '<img src="images/sort_up.gif" alt=""></a>';
    } else {
        $typesort = '<a class="gray" href="' . $thisfile . '&sortby=5&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Type") . "</a>";
    }
}

// Multiple deletes at top of table
if ($perm->have_perm_area_action('upl', 'upl_multidelete') && $bDirectoryIsWritable) {
    $sDelete = '<a class="tableElement vAlignMiddle jsDeleteSelected" href="javascript:void(0)" data-action="delete_selected"><img class="tableElement vAlignMiddle" src="images/delete.gif" title="' . i18n("Delete selected files") . '" alt="' . i18n("Delete selected files") . '"><span class="tableElement">' . i18n("Delete selected files") . '</span></a>';
} else {
    $sDelete = '';
}

$mpath = cApiDbfs::isDbfs($path) ? $path : 'upload/' . $path;
$mpath = trim($mpath, '/') . '/';

$sDisplayPath = generateDisplayFilePath($mpath, 85);

$sToolsRow = '<tr>
               <td colspan="6" class="con_navbar">
                   <a class="tableElement vAlignMiddle" href="javascript:void(0);" data-action="invert_selection">
                   <img class="tableElement vAlignMiddle" src="images/but_invert_selection.gif" title="' . i18n("Flip Selection") . '" alt="' . i18n("Flip Selection") . '"> ' . i18n("Flip Selection") . '
                   </a>
                       ' . $sDelete . '
                   <div class="toolsRight">
                   ' . i18n("Path:") . " " . $sDisplayPath . '
                   </div>
               </td>
           </tr>';
$sSpacedRow = '<tr>
                   <td colspan="6" class="empty_cell"></td>
              </tr>';

// List wraps

$pagerwrap = '<tr>
               <th colspan="6" class="con_navbar">
                    <table>
                        <tr>
                            <td class="align_middle no_wrap">
                               <span>' . i18n("Files per Page") . ' -C-FILESPERPAGE-</span>
                            </td>
                            <td class="text_right align_middle no_wrap col_100p">
                               <span>-C-SCROLLLEFT-</span>
                               <span>-C-PAGE-</span>
                               <span>-C-SCROLLRIGHT-</span>
                            </td>
                        </tr>
                    </table>
               </th>
           </tr>';

$startwrap = '<table class="hoverbox generic">
               ' . $pagerwrap . $sSpacedRow . $sToolsRow . $sSpacedRow . '
              <tr>
                   <th>' . i18n("Mark") . '</th>
                   <th>' . i18n("Preview") . '</th>
                   <th width="100%">' . $fnsort . '</th>
                   <th>' . $sizesort . '</th>
                   <th>' . $typesort . '</th>
                   <th>' . i18n("Actions") . '</th>
               </tr>';
$itemwrap = '<tr data-list-item="{LIST_ITEM_POS}">
                   <td class="text_center">%s</td>
                   <td class="text_center">%s</td>
                   <td class="vAlignTop no_wrap">%s</td>
                   <td class="vAlignTop no_wrap">%s</td>
                   <td class="vAlignTop no_wrap">%s</td>
                   <td class="vAlignTop no_wrap">%s</td>
               </tr>';
$endwrap = $sSpacedRow . $sToolsRow . $sSpacedRow . $pagerwrap . '</table>';

// Object initializing
$list2 = new UploadList($startwrap, $endwrap, $itemwrap);

$uploadCollection = new cApiUploadCollection();

// Fetch data
if (cString::getPartOfString($path, cString::getStringLength($path) - 1, 1) != "/") {
    if ($path != "") {
        $qpath = $path . "/";
    } else {
        // view the root folder
        $qpath = "";
    }
} else {
    $qpath = $path;
}

$uploadCollection->select("idclient = '$client' AND dirname = '$qpath'");

if ($thumbnailmode == '') {
    $current_mode = $currentuser->getUserProperty('upload_folder_thumbnailmode', md5($path));
    if ($current_mode != '') {
        $thumbnailmode = $current_mode;
    } else {
        $thumbnailmode = getEffectiveSetting('backend', 'thumbnailmode', 100);
    }
}

switch ($thumbnailmode) {
    case 10:
        $numpics = 10;
        break;
    case 25:
        $numpics = 25;
        break;
    case 50:
        $numpics = 50;
        break;
    case 100:
        $numpics = 100;
        break;
    case 200:
        $numpics = 200;
        break;
    default:
        $thumbnailmode = 100;
        $numpics = 15;
        break;
}

$currentuser->setUserProperty('upload_folder_thumbnailmode', md5($path), $thumbnailmode);

$list2->setResultsPerPage($numpics);
$list2->setSize($thumbnailmode);

$totalUploadsCount = $uploadCollection->count();
$list2->setDataCount($totalUploadsCount);

if ($startpage > $list2->getNumPages()) {
    $startpage = $list2->getNumPages();
}

if ($startpage < 1) {
    $startpage = 1;
}

$uploadCollection->resetQuery();
$uploadCollection->select("idclient = '$client' AND dirname = '$qpath'", '', '', $numpics * ($startpage - 1) . ", " .  $numpics);

$rownum = 0;

$properties = new cApiPropertyCollection();

while ($item = $uploadCollection->next()) {

    // Get name of directory, filename and size of file
    $dirname = $item->get('dirname');
    $filename = $item->get('filename');
    $filesize = $item->get('size');

    // Do not display directories and "filenames" begin with a dot
    if (true === cDirHandler::exists($cfgClient[$client]['upl']['path'] . $dirname . $filename) || cString::findFirstPos($filename, ".") === 0) {
        continue;
    }

    $bAddFile = true;

    if ($appendparameters == 'imagebrowser') {
        $restrictvar = 'restrict_' . $appendparameters;
        if (array_key_exists($restrictvar, $browserparameters)) {
            $fileType = cString::toLowerCase(cFileHandler::getExtension($filename));
            if (count($browserparameters[$restrictvar]) > 0) {
                $bAddFile = false;
                if (in_array($fileType, $browserparameters[$restrictvar])) {
                    $bAddFile = true;
                }
            }
        }
    }

    if ($filesize == 0) {
        if (cFileHandler::exists($cfgClient[$client]['upl']['path'] . $dirname . $filename)) {
            $filesize = filesize($cfgClient[$client]['upl']['path'] . $dirname . $filename);
        }
    }

    $actions = '';

    $medianame = $properties->getValue('upload', $path . $filename, 'file', 'medianame');
    $medianotes = $properties->getValue('upload', $path . $filename, 'file', 'medianotes');

    $todo = new TODOLink('upload', $path . $filename, "File $path$filename", '');

    $proptitle = i18n("Display properties");

    if ($appendparameters == 'imagebrowser' || $appendparameters == 'filebrowser') {
        $mstr = '';
    } else {
        $tmp_mstr = '<a class="con_img_button mgl3" href="javascript:Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>';
        $mstr = sprintf(
            $tmp_mstr,
            'right_bottom',
            $sess->url("main.php?area=upl_edit&frame=4&path=$path&file=$filename&startpage=$startpage&sortby=$sortby&sortmode=$sortmode&thumbnailmode=$thumbnailmode"),
            'right_top',
            $sess->url("main.php?area=upl&frame=3&path=$path&file=$filename"),
            cHTMLImage::img('images/but_art_conf2.gif', $proptitle)
        );
    }

    $actions = $mstr . $actions;

    $showfilename = $filename;

    $check = new cHTMLCheckbox('fdelete[]', $filename);

    $mark = $check->toHtml(false);

    if ($bAddFile) {
        // 'bgcolor' is just a placeholder...
        $list2->setData($rownum, $mark, $dirname . $filename, $showfilename, $filesize, cString::toLowerCase(cFileHandler::getExtension($filename)), $todo->render() . $actions);
        $rownum++;
    }
}

if ($rownum == 0) {
    header('Location: ' . cRegistry::getBackendUrl() . 'main.php?area=upl_upload&frame=4&path=' . $path . '&contenido=' . $contenido . '&appendparameters=' . $appendparameters);
}

if ($sortmode == 'ASC') {
    $list2->sort($sortby, SORT_ASC);
} else {
    $list2->sort($sortby, SORT_DESC);
}

$list2->setListStart($startpage);

// Create scroller
if ($list2->getCurrentPage() > 1) {
    $prevpage = '<a href="' . $scrollthisfile . '&startpage=' . ($list2->getCurrentPage() - 1) . '" class="invert_hover">' . i18n("Previous Page") . '</a>';
} else {
    $prevpage = '&nbsp;';
}

if ($list2->getCurrentPage() < $list2->getNumPages()) {
    $nextpage = '<a href="' . $scrollthisfile . '&startpage=' . ($list2->getCurrentPage() + 1) . '" class="invert_hover">' . i18n("Next Page") . '</a>';
} else {
    $nextpage = '&nbsp;';
}

// curpage = $list2->getCurrentPage() . " / ". $list2->getNumPages();

$paging_form = '';
if ($list2->getNumPages() > 1) {
    $num_pages = $list2->getNumPages();

    $paging_form .= "<script type='text/javascript'>
       function jumpToPage(select) {
           var pagenumber = select.selectedIndex + 1;
           url = '" . $sess->url('main.php') . "';
           document.location.href = url + '&area=upl&frame=4&appendparameters=$appendparameters&path=$path&sortmode=$sortmode&sortby=$sortby&thumbnailmode=$thumbnailmode&startpage=' + pagenumber;
       }
   </script>";
    $paging_form .= "<select name=\"start_page\" class=\"text_medium\" onChange=\"jumpToPage(this);\">";
    for ($i = 1; $i <= $num_pages; $i++) {
        if ($i == $startpage) {
            $selected = ' selected';
        } else {
            $selected = '';
        }
        $paging_form .= "<option value=\"$i\"$selected>$i</option>";
    }

    $paging_form .= '</select>';
} else {
    $paging_form = '1';
}
$curpage = $paging_form . ' / ' . $list2->getNumPages();

$scroller = $prevpage . $nextpage;
$output = $list2->output(true);
$output = str_replace('-C-SCROLLLEFT-', $prevpage, $output);
$output = str_replace('-C-SCROLLRIGHT-', $nextpage, $output);
$output = str_replace('-C-PAGE-', i18n("Page") . ' ' . $curpage, $output);

$select = new cHTMLSelectElement('thumbnailmode');
$values = [
#    2 => '2',
    10 => '10',
    25 => '25',
    50 => '50',
    100 => '100',
    200 => '200'
];
$select->autoFill($values);
$select->setDefault($thumbnailmode);

$button = cHTMLButton::image('images/submit.gif', i18n('Search'), ['class' => 'con_img_button align_middle mgl3']);
$topbar = $select->render() . $button;

$output = str_replace('-C-FILESPERPAGE-', $topbar, $output);

$form = new cHTMLForm('upl_file_list');
$form->setClass('upl_files_overview');
$form->setVar('area', $area);
$form->setVar('action', '');
$form->setVar('startpage', $startpage);
$form->setVar('thumbnailmode', $thumbnailmode);
$form->setVar('sortmode', $sortmode);
$form->setVar('sortby', $sortby);
$form->setVar('appendparameters', $appendparameters);
$form->setVar('path', $path);
$form->setVar('frame', 4);
// Table with (preview) images
$form->appendContent($output);

if (!$bDirectoryIsWritable) {
    $page->displayError(i18n("Directory not writable") . ' (' . $cfgClient[$client]['upl']['path'] . $path . ')');
}

$jsCode = '
<script type="text/javascript">
(function(Con, $) {
    $(function() {
        // Instantiate upload files overview component
        new Con.UplFilesOverview({
            rootSelector: ".upl_files_overview",
            filesPerPageSelector: "select[name=thumbnailmode]",
            filesCheckBoxSelector: "input[name=\'fdelete[]\']",
            deleteSelectedSelector: ".jsDeleteSelected",
            text_close: "' . i18n("Click to close") . '",
            text_delete_question: "' . i18n('Are you sure you want to delete the selected files?') . '",
        });
    });
})(Con, Con.$);
</script>
';

$page->setContent([
    $form,
    $jsCode
]);

$page->render();
