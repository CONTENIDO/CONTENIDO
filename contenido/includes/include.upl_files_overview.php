<?php
/**
 * This file contains the backend page for displaying files of a directory in upload section.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Timo Hummel
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$backendPath = cRegistry::getBackendPath();

cInclude('includes', 'api/functions.frontend.list.php');
cInclude('includes', 'functions.file.php');
cInclude('classes', 'class.cziparchive.php');

//cInclude('includes', 'class.ziparchive.php');

if (!(int) $client > 0) {
    // if there is no client selected, display empty page
    $oPage = new cGuiPage('upl_files_overview');
    $oPage->render();
    return;
}

$page = new cGuiPage('upl_files_overview', '', 0);

$appendparameters = $_REQUEST['appendparameters'];
$file = $_REQUEST['file'];

if (!is_array($browserparameters) && ($appendparameters != 'imagebrowser' || $appendparameters != 'filebrowser')) {
    $browserparameters = array();
}

if (!$sess->isRegistered('upl_last_path')) {
    // register last path (upl_last_path) in session
    $sess->register('upl_last_path');
} elseif (!isset($path)) {
    // if no path is given the last path is used
    $path = $upl_last_path;
}
// if path doesn't exist use root path
// this might happen when the last path is that of another client or deleted outside CONTENIDO
if (!cApiDbfs::isDbfs($path) && !cFileHandler::exists($cfgClient[$client]['upl']['path'] . $path)) {
    $path = '';
}
// remember current path as last path
$upl_last_path = $path;

$uploads = new cApiUploadCollection();

$dbfs = new cApiDbfsCollection();

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


if ($action == 'upl_modify_file') {

    $extractFolder = NULL;
    $uplPath = $cfgClient[$client]['upl']['path'];

    if (isset($_REQUEST['path']) && $_REQUEST['path'] != NULL) {
        $uplPath .= $_REQUEST['path'];
    }

    if (isset($_REQUEST['efolder']) && $_REQUEST['efolder'] != NULL) {
        $extractFolder = $_REQUEST['efolder'];
    }

    if (isset($_REQUEST['extractZip']) && !isset($_REQUEST['overwrite'])) {
        $zipFile = $uplPath . $_REQUEST['file'];
        cZipArchive::extract($zipFile, $uplPath, $extractFolder);
    }
    if (isset($_REQUEST['extractZip']) && isset($_REQUEST['overwrite'])) {
        $zipFile = $uplPath . $_REQUEST['file'];
        cZipArchive::extractOverRide($zipFile, $uplPath, $extractFolder);
    }
    // Did the user upload a new file?
    if ($bDirectoryIsWritable == true && count($_FILES) == 1 && ($_FILES['file']['size'] > 0) && ($_FILES['file']['name'] != '')) {
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
                        $sPathApppend = '/';
                    } else {
                        $sPathPrepend = $cfgClient[$client]['upl']['path'];
                        $sPathApppend = '';
                    }

                    $modified = $chainEntry->execute($tmp_name, $sPathPrepend . $path . $sPathApppend . uplCreateFriendlyName($_FILES['file']['name']));

                    if ($modified !== false) {
                        $tmp_name = $modified;
                    }
                }
            }

            if (cApiDbfs::isDbfs($path)) {
                $dbfs->writeFromFile($tmp_name, $qpath . $file);
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

    $uploads->select("idclient = '$client' AND dirname = '$qpath' AND filename='$file'");
    $upload = $uploads->next();

    // $upload->set('description', stripslashes($description));
    $upload->store();

    $properties = new cApiPropertyCollection();
    $properties->setValue('upload', $qpath . $file, 'file', 'protected', stripslashes($protected));

    $bTimeMng = (isset($_REQUEST['timemgmt']) && strlen($_REQUEST['timemgmt']) > 1);
    $properties->setValue('upload', $qpath . $file, 'file', 'timemgmt', ($bTimeMng) ? 1 : 0);
    if ($bTimeMng) {
        $properties->setValue('upload', $qpath . $file, 'file', 'datestart', $_REQUEST['datestart']);
        $properties->setValue('upload', $qpath . $file, 'file', 'dateend', $_REQUEST['dateend']);
    }

    $author = $auth->auth['uid'];
    $created = date('Y-m-d H:i:s');

    $iIdupl = $upload->get('idupl');
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

if ($action == 'upl_multidelete' && $perm->have_perm_area_action($area, $action) && $bDirectoryIsWritable == true) {
    if (is_array($fdelete)) {
        // array of cApiUpload objects to be passed to chain function
        $uploadObjects = array();

        // Check if it is in the upload table
        foreach ($fdelete as $file) {
            $uploads->select("idclient = '$client' AND dirname='$qpath' AND filename='$file'");
            if (false !== $item = $uploads->next()) {
                if (cApiDbfs::isDbfs($qpath)) {
                    $dbfs->remove($qpath . $file);

                    // call chain once for each deleted file
                    $_cecIterator = cRegistry::getCecRegistry()->getIterator('Contenido.Upl_edit.Delete');
                    if ($_cecIterator->count() > 0) {
                        while (false !== $chainEntry = $_cecIterator->next()) {
                            $chainEntry->execute($item->get('idupl'), $qpath, $file);
                        }
                    }
                } else {
                    $uploads->delete($item->get('idupl'));
                }

                // add current upload object to array in order to be processed
                array_push($uploadObjects, $item);
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

if ($action == 'upl_delete' && $perm->have_perm_area_action($area, $action) && $bDirectoryIsWritable == true) {
    // array of cApiUpload objects to be passed to chain function
    $uploadObjects = array();

    $uploads->select("idclient = '$client' AND dirname='$qpath' AND filename='$file'");
    // FIXME Code is similar/redundant to cApiUploadCollection->delete(), in
    // previous version from UploadCollection->delete() too
    if (false !== $item = $uploads->next()) {
        if (cApiDbfs::isDbfs($qpath)) {
            $dbfs->remove($qpath . $file);
        } else {
            unlink($cfgClient[$client]['upl']['path'] . $qpath . $file);
        }

        // call chain for deleted file
        $_cecIterator = cRegistry::getCecRegistry()->getIterator('Contenido.Upl_edit.Delete');
        if ($_cecIterator->count() > 0) {
            while (false !== $chainEntry = $_cecIterator->next()) {
                $chainEntry->execute($uploads->f('idupl'), $qpath, $file);

                // add current upload object to array in order to be processed
                array_push($uploadObjects, $item);
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

if ($action == 'upl_upload' && $bDirectoryIsWritable == true) {
    if ($perm->have_perm_area_action($area, 'upl_upload')) {
        if (count($_FILES) == 1) {
            foreach ($_FILES['file']['name'] as $key => $value) {
                if (isUtf8($_FILES['file']['name'][$key])) {
                    $_FILES['file']['name'][$key] = utf8_decode($_FILES['file']['name'][$key]);
                }
                if ($_FILES['file']['tmp_name'][$key] != '') {
                    $tmp_name = $_FILES['file']['tmp_name'][$key];
                    $_cecIterator = $_cecRegistry->getIterator('Contenido.Upload.UploadPreprocess');

                    if ($_cecIterator->count() > 0) {
                        // Copy file to a temporary location
                        move_uploaded_file($tmp_name, $backendPath . $cfg['path']['temp'] . $_FILES['file']['name'][$key]);
                        $tmp_name = $backendPath . $cfg['path']['temp'] . $_FILES['file']['name'][$key];

                        while ($chainEntry = $_cecIterator->next()) {
                            if (cApiDbfs::isDbfs($path)) {
                                $sPathPrepend = '';
                                $sPathApppend = '/';
                            } else {
                                $sPathPrepend = $cfgClient[$client]['upl']['path'];
                                $sPathApppend = '';
                            }

                            $modified = $chainEntry->execute($tmp_name, $sPathPrepend . $path . $sPathApppend . uplCreateFriendlyName($_FILES['file']['name'][$key]));
                            if ($modified !== false) {
                                $tmp_name = $modified;
                            }
                        }
                    }

                    if (cApiDbfs::isDbfs($qpath)) {
                        $dbfs->writeFromFile($tmp_name, $qpath . uplCreateFriendlyName($_FILES['file']['name'][$key]));
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

if ($action == 'upl_renamefile' && $bDirectoryIsWritable == true) {
    $newname = str_replace('/', '', $newname);
    rename($cfgClient[$client]['upl']['path'] . $path . $oldname, $cfgClient[$client]['upl']['path'] . $path . $newname);
}

class UploadList extends FrontendList {

    var $dark;
    var $size;

    function convert($field, $data) {
        global $cfg, $path, $sess, $cfgClient, $client, $appendparameters;

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
                    $mstr = '<a href="javascript://" onclick="parent.parent.frames[\'left\'].frames[\'left_top\'].document.getElementById(\'selectedfile\').value= \'' . $cfgClient[$client]['htmlpath']['frontend'] . 'dbfs.php?file=' . $path . '/' . $data . '\'; window.returnValue=\'' . $cfgClient[$client]['htmlpath']['frontend'] . 'dbfs.php?file=' . $path . '/' . $data . '\'; window.close();"><img src="' . $backendUrl . $cfg['path']['images'] . 'but_ok.gif" title="' . i18n("Use file") . '">&nbsp;' . $data . '</a>';
                } else {
                    $mstr = '<a href="javascript://" onclick="parent.parent.frames[\'left\'].frames[\'left_top\'].document.getElementById(\'selectedfile\').value= \'' . $cfgClient[$client]['htmlpath']['frontend'] . $cfgClient[$client]['upl']['frontendpath'] . $path . $data . '\'; window.returnValue=\'' . $cfgClient[$client]['htmlpath']['frontend'] . $cfgClient[$client]['upl']['frontendpath'] . $path . $data . '\'; window.close();"><img src="' . $backendUrl . $cfg['path']['images'] . 'but_ok.gif" title="' . i18n("Use file") . '">&nbsp;' . $data . '</a>';
                }
            } else {
                $tmp_mstr = '<a onmouseover="this.style.cursor=\'pointer\'" href="javascript:Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>';
                $mstr = sprintf($tmp_mstr, 'right_bottom', $sess->url("main.php?area=upl_edit&frame=4&path=$path&file=$data&appendparameters=$appendparameters&startpage=" . $_REQUEST['startpage'] . "&sortby=" . $_REQUEST['sortby'] . "&sortmode=" . $_REQUEST['sortmode'] . "&thumbnailmode=" . $_REQUEST['thumbnailmode']), 'right_top', $sess->url("main.php?area=upl&frame=3&path=$path&file=$data"), $data);
            }
            return $mstr;
        }

        if ($field == 5) {
            return uplGetFileTypeDescription($data);
        }

        if ($field == 2) {
            // If this file is an image, try to open
            $fileType = strtolower(getFileType($data));
            switch ($fileType) {
                case 'png':
                case 'gif':
                case 'tiff':
                case 'bmp':
                case 'jpeg':
                case 'jpg':
                case 'iff':
                case 'xbm':
                case 'wbmp':
                    $frontendURL = cRegistry::getFrontendUrl();

                    $sCacheThumbnail = uplGetThumbnail($data, 150);
                    $sCacheName = substr($sCacheThumbnail, strrpos($sCacheThumbnail, '/') + 1, strlen($sCacheThumbnail) - (strrchr($sCacheThumbnail, '/') + 1));
                    $sFullPath = $cfgClient[$client]['cache']['path'] . $sCacheName;
                    if (cFileHandler::exists($sFullPath)) {
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
                    $retValue = '<a class="jsZoom" href="' . $href . '">
                           <img class="hover" name="smallImage" src="' . $sCacheThumbnail . '" data-width="' . $iWidth . '" data-height="' . $iHeight . '">
                           <img class="preview" name="prevImage" src="' . $sCacheThumbnail . '">
                       </a>';
                    return $retValue;
                    break;
                default:
                    $sCacheThumbnail = uplGetThumbnail($data, 150);
                    return '<img class="hover_none" name="smallImage" src="' . $sCacheThumbnail . '">';
            }
        }

        return $data;
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
    $fnsort = '<a class="gray" href="' . $thisfile . '&sortby=3&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Filename / Description") . '<img src="images/sort_down.gif" border="0"></a>';
} else {
    if ($sortby == 3) {
        $fnsort = '<a class="gray" href="' . $thisfile . '&sortby=3&sortmode=DESC&startpage=' . $startpage . '">' . i18n("Filename / Description") . '<img src="images/sort_up.gif" border="0"></a>';
    } else {
        $fnsort = '<a class="gray" href="' . $thisfile . '&sortby=3&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Filename / Description") . '</a>';
    }
}

if ($sortby == 4 && $sortmode == 'DESC') {
    $sizesort = '<a class="gray" href="' . $thisfile . '&sortby=4&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Size") . '<img src="images/sort_down.gif" border="0"></a>';
} else {
    if ($sortby == 4) {
        $sizesort = '<a class="gray" href="' . $thisfile . '&sortby=4&sortmode=DESC&startpage=' . $startpage . '">' . i18n("Size") . '<img src="images/sort_up.gif" border="0"></a>';
    } else {
        $sizesort = '<a class="gray" href="' . $thisfile . '&sortby=4&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Size") . "</a>";
    }
}

if ($sortby == 5 && $sortmode == 'DESC') {
    $typesort = '<a class="gray" href="' . $thisfile . '&sortby=5&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Type") . '<img src="images/sort_down.gif" border="0"></a>';
} else {
    if ($sortby == 5) {
        $typesort = '<a class="gray" class="gray" href="' . $thisfile . '&sortby=5&sortmode=DESC&startpage=' . $startpage . '">' . i18n("Type") . '<img src="images/sort_up.gif" border="0"></a>';
    } else {
        $typesort = '<a class="gray" href="' . $thisfile . '&sortby=5&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Type") . "</a>";
    }
}

// Multiple deletes at top of table
if ($perm->have_perm_area_action('upl', 'upl_multidelete') && $bDirectoryIsWritable == true) {
    $sConfirmation = "Con.showConfirmation('" . i18n('Are you sure you want to delete the selected files?') . "', function() { document.del.action.value = \'upl_multidelete\'; document.del.submit(); });return false;";
    $sDelete = '<a class="tableElement vAlignMiddle" href="javascript:void(0)" onclick="' . $sConfirmation . '"><img class="tableElement vAlignMiddle" src="images/delete.gif" title="' . i18n("Delete selected files") . '" alt="' . i18n("Delete selected files") . '" onmouseover="this.style.cursor=\'pointer\'"><span class="tableElement">' . i18n("Delete selected files") . '</span></a>';
} else {
    $sDelete = '';
}

if (cApiDbfs::isDbfs($path)) {
    $mpath = $path . '/';
} else {
    $mpath = 'upload/' . $path;
}

$sDisplayPath = generateDisplayFilePath($mpath, 85);

$sToolsRow = '<tr>
               <th colspan="6" id="cat_navbar">
                   <a class="tableElement vAlignMiddle" href="javascript:invertSelection();"><img class="tableElement vAlignMiddle" src="images/but_invert_selection.gif" title="' . i18n("Flip Selection") . '" alt="' . i18n("Flip Selection") . '" onmouseover="this.style.cursor=\'pointer\'"> ' . i18n("Flip Selection") . '</a>
                       ' . $sDelete . '
                   <div class="toolsRight">
                   ' . i18n("Path:") . " " . $sDisplayPath . '
                   </div>
               </th>
           </tr>';
$sSpacedRow = '<tr height="10">
                   <td colspan="6" class="emptyCell"></td>
              </tr>';

// List wraps

$pagerwrap = '<tr>
               <th colspan="6" id="cat_navbar">
                   <div class="toolsRight">
                       <div class="vAlignMiddle">-C-SCROLLLEFT-</div>
                       <div class="vAlignMiddle">-C-PAGE-</div>
                       <div class="vAlignMiddle">-C-SCROLLRIGHT-</div>
                   </div>
                   <span class="vAlignMiddle">' . i18n("Files per Page") . ' -C-FILESPERPAGE-</span>
               </th>
           </tr>';

$startwrap = '<table class="hoverbox generic" cellspacing="0" cellpadding="2" border="0">
               ' . $pagerwrap . $sSpacedRow . $sToolsRow . $sSpacedRow . '
              <tr>
                   <th>' . i18n("Mark") . '</th>
                   <th>' . i18n("Preview") . '</th>
                   <th width="100%">' . $fnsort . '</th>
                   <th>' . $sizesort . '</th>
                   <th>' . $typesort . '</th>
                   <th>' . i18n("Actions") . '</th>
               </tr>';
$itemwrap = '<tr>
                   <td align="center">%s</td>
                   <td align="center">%s</td>
                   <td class="vAlignTop nowrap">%s</td>
                   <td class="vAlignTop nowrap">%s</td>
                   <td class="vAlignTop nowrap">%s</td>
                   <td class="vAlignTop nowrap">%s</td>
               </tr>';
$endwrap = $sSpacedRow . $sToolsRow . $sSpacedRow . $pagerwrap . '</table>';

// Object initializing
$list2 = new UploadList($startwrap, $endwrap, $itemwrap);

$uploads = new cApiUploadCollection();

// Fetch data
if (substr($path, strlen($path) - 1, 1) != "/") {
    if ($path != "") {
        $qpath = $path . "/";
    } else {
        // view the root folder
        $qpath = "";
    }
} else {
    $qpath = $path;
}

$uploads->select("idclient = '$client' AND dirname = '$qpath'");

$user = new cApiUser($auth->auth['uid']);

if ($thumbnailmode == '') {
    $current_mode = $user->getUserProperty('upload_folder_thumbnailmode', md5($path));
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

$user->setUserProperty('upload_folder_thumbnailmode', md5($path), $thumbnailmode);

$list2->setResultsPerPage($numpics);

$list2->size = $thumbnailmode;

$rownum = 0;

$properties = new cApiPropertyCollection();

while ($item = $uploads->next()) {
    $filename = $item->get('filename');

    if (strpos($filename, ".") === 0) {
        continue;
    }

    $bAddFile = true;

    if ($appendparameters == 'imagebrowser') {
        $restrictvar = 'restrict_' . $appendparameters;
        if (array_key_exists($restrictvar, $browserparameters)) {
            $fileType = strtolower(getFileType($filename));
            if (count($browserparameters[$restrictvar]) > 0) {
                $bAddFile = false;
                if (in_array($fileType, $browserparameters[$restrictvar])) {
                    $bAddFile = true;
                }
            }
        }
    }

    $dirname = $item->get('dirname');
    $filesize = $item->get('size');

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
        $tmp_mstr = '<a href="javascript:Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>';
        $mstr = sprintf($tmp_mstr, 'right_bottom', $sess->url("main.php?area=upl_edit&frame=4&path=$path&file=$filename&startpage=$startpage&sortby=$sortby&sortmode=$sortmode&thumbnailmode=$thumbnailmode"), 'right_top', $sess->url("main.php?area=upl&frame=3&path=$path&file=$filename"), '<img class="vAlignMiddle tableElement" alt="' . $proptitle . '" title="' . $proptitle . '" src="images/but_art_conf2.gif" onmouseover="this.style.cursor=\'pointer\'">');
    }

    $actions = $mstr . $actions;

    $showfilename = $filename;

    $check = new cHTMLCheckbox('fdelete[]', $filename);

    $mark = $check->toHTML(false);

    if ($bAddFile == true) {
        // 'bgcolor' is just a placeholder...
        $list2->setData($rownum, $mark, $dirname . $filename, $showfilename, $filesize, strtolower(getFileType($filename)), $todo->render() . $actions);
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

if ($startpage < 1) {
    $startpage = 1;
}

if ($startpage > $list2->getNumPages()) {
    $startpage = $list2->getNumPages();
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

if ($list2->getNumPages() > 1) {
    $num_pages = $list2->getNumPages();

    $paging_form .= "<script type=\"text/javascript\">
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

$values = array(
    10 => '10',
    25 => '25',
    50 => '50',
    100 => '100',
    200 => '200'
);

$select->autoFill($values);

$select->setDefault($thumbnailmode);
$select->setEvent('change', "if (document.del.thumbnailmode[0] != 'undefined') document.del.thumbnailmode[0].value = this.value; if (document.del.thumbnailmode[1] != 'undefined') document.del.thumbnailmode[1].value = this.value; if (document.del.thumbnailmode[2] != 'undefined') document.del.thumbnailmode[2].value = this.value;");

$topbar = $select->render() . '<input class="vAlignMiddle tableElement" type="image" onmouseover="this.style.cursor=\'pointer\'" src="images/submit.gif">';

$output = str_replace('-C-FILESPERPAGE-', $topbar, $output);

$delform = new cHTMLForm('del');
$delform->setVar('area', $area);
$delform->setVar('action', '');
$delform->setVar('startpage', $startpage);
$delform->setVar('thumbnailmode', $thumbnailmode);
$delform->setVar('sortmode', $sortmode);
$delform->setVar('sortby', $sortby);
$delform->setVar('appendparameters', $appendparameters);
$delform->setVar('path', $path);
$delform->setVar('frame', 4);
// Table with (preview) images
$delform->appendContent($output);

$page->addScript($sess->url('iZoom.js.php'));

if ($bDirectoryIsWritable == false) {
    $page->displayError(i18n("Directory not writable") . ' (' . $cfgClient[$client]['upl']['path'] . $path . ')');
}

$jsScript = new cHTMLScript();
$jsScript->setAttribute('type', 'text/javascript');

$jsCode = '
(function(Con, $) {
   $(function() {
       var $body = $("body");

       // Handler for clicked image anchors
       $body.delegate("a.jsZoom", "click", function() {
           iZoom($(this).attr("href"));
           return false;
       });

       // Handler for mouseover/mouseout on images
       $body.delegate("a.jsZoom img.hover", "mouseover", function() {
           correctPosition(this, $(this).attr("data-width"), $(this).attr("data-height"));
       });
       $body.delegate("a.jsZoom img.hover", "mouseout", function() {
           if (typeof(previewHideIe6) == "function") {
               previewHideIe6(this);
           };
       });
   });
})(Con, Con.$);
';
$jsScript->setContent($jsCode);

$page->setContent(array(
    $delform,
    $jsScript
));

$page->render();
