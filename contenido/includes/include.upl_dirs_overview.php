<?php

/**
 * This file contains the backend page for the directory overview in upload
 * section.
 *
 * @package Core
 * @subpackage Backend
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.con.php');
cInclude('includes', 'functions.str.php');

// display critical error if no valid client is selected
if ((int) $client < 1) {
    $oPage = new cGuiPage('upl_dirs_overview');
    $oPage->displayCriticalError(i18n("No Client selected"));
    $oPage->render();
    return;
}

$appendparameters = !empty($_REQUEST['appendparameters']) ? $_REQUEST['appendparameters'] : '';
$collapse = !empty($_REQUEST['collapse']) ? $_REQUEST['collapse'] : '';
$expand = !empty($_REQUEST['expand']) ? $_REQUEST['expand'] : '';

/**
 *
 * @param TreeItem $item
 *
 * @return string
 * 
 * @throws cException
 */
function getUplExpandCollapseButton($item) {
    global $sess, $frame, $area, $appendparameters;
    $selflink = 'main.php';

    if (count($item->getSubItems()) > 0) {
        if ($item->isCollapsed() == true) {
            $expandlink = $sess->url($selflink . "?area=$area&frame=$frame&appendparameters=$appendparameters&expand=" . $item->getId());
            return ('<a href="' . $expandlink . '" alt="' . i18n('Open category') . '" title="' . i18n('Open category') . '"><img src="' . $item->getCollapsedIcon() . '" alt="" border="0" align="middle" width="18"></a>');
        } else {
            $collapselink = $sess->url($selflink . "?area=$area&appendparameters=$appendparameters&frame=$frame&collapse=" . $item->getId());
            return ('<a href="' . $collapselink . '" alt="' . i18n('Close category') . '" title="' . i18n('Close category') . '"><img src="' . $item->getExpandedIcon() . '" alt="" border="0" align="middle" width="18"></a>');
        }
    } else {
        if ($item->getCustom('lastitem')) {
            return '<img class="vAlignMiddle" alt="" src="images/but_lastnode.gif" width="18" height="18">';
        } else {
            return '<img class="vAlignMiddle" alt="" src="images/grid_collapse.gif" width="18" height="18">';
        }
    }
}

// Create Folder
// ixxed by Timo Trautmann double database entries also called by action upl_mkdir
// Use remembered path from upl_last_path (from session)
if (!isset($path) && $sess->isRegistered('upl_last_path')) {
    $path = $upl_last_path;
}

if (!isset($action)) {
    $action = '';
}

if (empty($tmp_area)) {
    $tmp_area = $area; // $tmp_area used at two places for unknown reasons...
}

$uplexpandedList = unserialize($currentuser->getUserProperty('system', 'upl_expandstate'));
$upldbfsexpandedList = unserialize($currentuser->getUserProperty('system', 'upl_dbfs_expandstate'));

if (!is_array($uplexpandedList)) {
    $uplexpandedList = array();
}

if (!is_array($upldbfsexpandedList)) {
    $upldbfsexpandedList = array();
}

$dbfs = new cApiDbfsCollection();

if ($action == 'upl_delete') {
    if (cApiDbfs::isDbfs($path)) {
        $dbfs->remove($path . '/.');
    } else {
        $failedFiles = array();

        // Check for files
        if (uplHasFiles($path)) {
            if (is_dir($cfgClient[$client]['upl']['path'] . $path)) {
                if (false !== ($directory = cDirHandler::read($uploadPath))) {
                    foreach ($directory as $dir_entry) {
                        if (cFileHandler::fileNameIsDot($dir_entry) === false) {
                            $res = cFileHandler::remove($cfgClient[$client]['upl']['path'] . $path . $dir_entry);

                            if ($res == false) {
                                $failedFiles[] = $dir_entry;
                            }
                        }
                    }
                }
            }
        }

        if (count($failedFiles) > 0) {
            $notification->displayNotification('warning', i18n("Failed to delete the following files:") . '<br><br>' . implode('<br>', $failedFiles));
        } else {
            $res = @rmdir($cfgClient[$client]['upl']['path'] . $path);
            if ($res == false) {
                $notification->displayNotification('warning', sprintf(i18n("Failed to remove directory %s"), $path));
            }
        }
    }
}

$tpl->reset();

// Show notification for error in dir name from upl_mkdir.action
if (!empty($errno)) {
    if ($errno === '0703') {
    $tpl->set('s', 'WARNING', $notification->returnNotification('error', i18n('Directories with special characters and spaces are not allowed.')));
    } elseif($errno === '0704') {
    $tpl->set('s', 'WARNING', $notification->returnNotification('error', i18n('Can not write directory.')));
    }
}

// Uploadfiles tree on file system

$file = 'Upload';
$pathstring = '';

$rootTreeItem = new TreeItem();
$rootTreeItem->setCustom('level', 0);
$rootTreeItem->setName(i18n("Upload directory"));
$aInvalidDirectories = uplRecursiveDirectoryList($cfgClient[$client]["upl"]["path"], $rootTreeItem, 2);
if (count($aInvalidDirectories) > 0) {
    $sWarningInfo = i18n('The following directories contains invalid characters and were ignored: ');
    $sSeperator = '<br>';
    $sFiles = implode(', ', $aInvalidDirectories);
    $sRenameString = i18n('Please click here in order to rename automatically.');
    $sRenameHref = $sess->url("main.php?area=$area&frame=$frame&force_rename=true");
    $sRemameLink = '<a href="' . $sRenameHref . '">' . $sRenameString . '</a>';
    $sNotificationString = $sWarningInfo . $sSeperator . $sFiles . $sSeperator . $sSeperator . $sRemameLink;

    $sErrorString = $notification->returnNotification('warning', $sNotificationString);
    $tpl->set('s', 'WARNING', $sErrorString);
} else {
    $tpl->set('s', 'WARNING', '');
}

// Mark all items in the expandedList as expanded
foreach ($uplexpandedList as $key => $value) {
    $rootTreeItem->markExpanded($value);
}

// Collapse and expand the tree
if (!empty($collapse)) {
    $rootTreeItem->markCollapsed($collapse);
}

if (!empty($expand)) {
    $rootTreeItem->markExpanded($expand);
}

$uplexpandedList = array();
$rootTreeItem->getExpandedList($uplexpandedList);

$currentuser->setUserProperty('system', 'upl_expandstate', serialize($uplexpandedList));

$objects = array();
$rootTreeItem->traverse($objects);
unset($objects[0]);

if ($appendparameters == 'filebrowser') {
    $mtree = new cGuiTree('b58f0ae3-8d4e-4bb3-a754-5f0628863364');
    $cattree = conFetchCategoryTree();
    $marray = array();

    foreach ($cattree as $key => $catitem) {
        $no_start = true;
        $no_online = true;
        $no_start = !strHasStartArticle($catitem['idcat'], $lang);
        $no_online = !$catitem['visible'];

        $icon = 'images/';
        if ($catitem['visible'] == 1) {
            if ($catitem['public'] == 0) {
                // Category is not public
                $icon .= ($no_start || $no_online) ? 'folder_on_error_locked.gif' : 'folder_on_locked.gif';
            } else {
                // Category is public
                $icon .= ($no_start || $no_online) ? 'folder_on_error.gif' : 'folder_on.gif';
            }
        } else {
            // Category is offline
            if ($catitem['public'] == 0) {
                // Category is locked
                $icon .= ($no_start || $no_online) ? 'folder_off_error_locked.gif' : 'folder_off_locked.gif';
            } else {
                // Category is public
                $icon .= ($no_start || $no_online) ? 'folder_off_error.gif' : 'folder_off.gif';
            }
        }

        $idcat = $catitem['idcat'];

        $name = '&nbsp;<a href="' . $sess->url("main.php?area=$area&frame=5&idcat=$idcat&appendparameters=$appendparameters") . '" target="right_bottom">' . $catitem['name'] . '</a>';
        $marray[] = array(
            'id' => $catitem['idcat'],
            'name' => $name,
            'level' => $catitem['level'],
            'attributes' => array(
                'icon' => $icon
            )
        );
    }

    $mtree->setTreeName(i18n("Categories"));
    $mtree->setIcon('images/grid_folder.gif');
    $mtree->importTable($marray);

    $baselink = new cHTMLLink();
    $baselink->setCLink($area, $frame, '');
    $baselink->setCustom('appendparameters', $appendparameters);

    $mtree->setBaseLink($baselink);
    $mtree->setCollapsed($collapsed);
    $mtree->processParameters();

    $collapsed = array();
    $mtree->getCollapsedList($collapsed);

    $tpl->set('s', 'CATBROWSER', $mtree->render());
    $tpl->set('s', 'APPENDPARAMETERS', '\'&appendparameters=' . $appendparameters . '\'');
} else {
    $tpl->set('s', 'CATBROWSER', '');
    $tpl->set('s', 'APPENDPARAMETERS', '\'&appendparameters=' . $appendparameters . '\'');
}

chdir(cRegistry::getBackendPath());

$idFsPathPrefix = 'fs_';

// create javascript multilink
$tmp_mstr = '<a id="root" href="javascript:Con.multiLink(\'%s\', \'%s\',\'%s\', \'%s\')">%s</a>';
$mstr = sprintf($tmp_mstr, 'right_top', $sess->url("main.php?area=$area&frame=3&path=$pathstring&appendparameters=$appendparameters"), 'right_bottom', $sess->url("main.php?area=$area&frame=4&path=$pathstring&appendparameters=$appendparameters"), '<img class="vAlignMiddle" src="images/ordner_oben.gif" align="middle" alt="" border="0"><img src="images/spacer.gif" width="5" border="0">' . $file);

$tpl->set('d', 'ID_PATH', $idFsPathPrefix . 'root');
$tpl->set('d', 'DATA_PATH', $pathstring);
$tpl->set('d', 'INDENT', 3);
$tpl->set('d', 'DIRNAME', $mstr);
$tpl->set('d', 'EDITBUTTON', '');
$tpl->set('d', 'DELETEBUTTON', '');
$tpl->set('d', 'COLLAPSE', '');
$tpl->next();

if (is_array($objects)) {
    foreach ($objects as $a_file) {
        $file = $a_file->getName();
        $depth = $a_file->getCustom('level') - 1;
        $pathstring = str_replace($cfgClient[$client]['upl']['path'], '', $a_file->getId());
        $a_file->setCollapsedIcon('images/grid_expand.gif');
        $a_file->setExpandedIcon('images/grid_collapse.gif');
        $dlevels[$depth] = $a_file->getCustom('lastitem');
        $imgcollapse = getUplExpandCollapseButton($a_file);
        $fileurl = rawurlencode($path . $file . '/');
        $pathurl = rawurlencode($path);

        // Indent for every level
        $indent = 18 + (($depth - 1) * 18);

        // create javascript multilink
        $tmp_mstr = '<a href="javascript:Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>';
        $mstr = sprintf($tmp_mstr, 'right_top', $sess->url("main.php?area=$area&frame=3&path=$pathstring&appendparameters=$appendparameters"), 'right_bottom', $sess->url("main.php?area=$area&frame=4&path=$pathstring&appendparameters=$appendparameters"), '<img class="vAlignMiddle" src="images/grid_folder.gif" border="0" alt=""><img src="images/spacer.gif" align="middle" width="5" border="0">' . $file);

        $hasFiles = uplHasFiles($pathstring);
        $hasSubdirs = uplHasSubdirs($pathstring);

        if ((!$hasSubdirs) && (!$hasFiles) && $perm->have_perm_area_action($tmp_area, "upl_rmdir")) {
            // $deletebutton = '
            // <a title="' . i18n("Delete directory") . '"
            // href="javascript:void(0)"
            // onclick="event.cancelBubble=true;Con.showConfirmation(&quot;' .
            // i18n("Do you really want to delete the following directory:") .
            // '<b>' . $file . '</b>&quot;, function() { deleteDirectory(&quot;'
            // . $pathstring . '&quot;); });return false;">
            // aa<img src="' . $cfg['path']['images'] . 'delete.gif" border="0"
            // title="' . i18n("Delete directory") . '" alt="' . i18n("Delete
            // directory") . '">
            // </a>';
            $deletebutton = '
    <a class="jsDelete" title="' . i18n("Delete directory") . '" href="javascript:void(0)">
        <img src="' . $cfg['path']['images'] . 'delete.gif" border="0" title="' . i18n("Delete directory") . '" alt="' . i18n("Delete directory") . '">
    </a>';
        } else {
            if ($hasFiles) {
                $message = i18n("Directory contains files");
            } else {
                $message = i18n("Permission denied");
            }
            $deletebutton = '<img src="' . $cfg['path']['images'] . 'delete_inact.gif" border="0" alt="' . $message . '" title="' . $message . '">';
        }

        $gline = '';
        for ($i = 1; $i < $depth; $i++) {
            if ($dlevels[$i] == false && $i != 0) {
                $gline .= '<img class="vAlignMiddle" alt="" src="images/grid_linedown.gif" width="18">';
            } else {
                $gline .= '<img class="vAlignMiddle" alt="" src="images/spacer.gif" width="18" height="18">';
            }
        }

        $parent = str_replace($cfgClient[$client]['upl']['path'], '', $a_file->getCustom('parent'));

        $idAttrPath = str_replace(array(
            '/',
            ':'
        ), array(
            '_',
            ''
        ), trim($pathstring, '/'));
        $tpl->set('d', 'ID_PATH', $idFsPathPrefix . $idAttrPath);
        $tpl->set('d', 'DATA_PATH', $pathstring);
        $tpl->set('d', 'INDENT', 0);
        $tpl->set('d', 'DIRNAME', $mstr);
        $tpl->set('d', 'EDITBUTTON', '');
        $tpl->set('d', 'DELETEBUTTON', $deletebutton);
        $tpl->set('d', 'COLLAPSE', $gline . $imgcollapse);
        $tpl->next();
    }
}

$tpl->set('d', 'DELETEBUTTON', '&nbsp;');
$tpl->set('d', 'DIRNAME', '');
$tpl->set('d', 'EDITBUTTON', '');
$tpl->set('d', 'COLLAPSE', "");
$tpl->next();

// Database-based filesystem (DBFS)

$idDbfsPathPrefix = 'dbfs_';
$file = i18n("Database file system");
$pathstring = cApiDbfs::PROTOCOL_DBFS;
$rootTreeItem = new TreeItem();
$rootTreeItem->setCustom('level', 0);

uplRecursiveDBDirectoryList('', $rootTreeItem, 2, $client);

// Mark all items in the expandedList as expanded
foreach ($upldbfsexpandedList as $key => $value) {
    $rootTreeItem->markExpanded($value);
}

// Collapse and expand the tree
if (!empty($collapse)) {
    $rootTreeItem->markCollapsed($collapse);
}

if (!empty($expand)) {
    $rootTreeItem->markExpanded($expand);
}

$upldbfsexpandedList = array();
$rootTreeItem->getExpandedList($upldbfsexpandedList);

$currentuser->setUserProperty('system', 'upl_dbfs_expandstate', serialize($upldbfsexpandedList));

$objects = array();
$rootTreeItem->traverse($objects);

unset($objects[0]);

$tmp_mstr = '<a href="javascript:Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>';
$mstr = sprintf($tmp_mstr, 'right_top', $sess->url("main.php?area=$area&frame=3&path=$pathstring&appendparameters=$appendparameters"), 'right_bottom', $sess->url("main.php?area=$area&frame=4&path=$pathstring&appendparameters=$appendparameters"), '<img class="vAlignMiddle" src="images/ordner_oben.gif" alt="" border="0"><img src="images/spacer.gif" width="5" border="0">' . $file);

$tpl->set('d', 'ID_PATH', $idDbfsPathPrefix . 'root');
$tpl->set('d', 'DATA_PATH', $pathstring);
$tpl->set('d', 'INDENT', 3);
$tpl->set('d', 'DIRNAME', $mstr);
$tpl->set('d', 'EDITBUTTON', '');
$tpl->set('d', 'DELETEBUTTON', '');
$tpl->set('d', 'COLLAPSE', '');
$tpl->next();

$dbfsc = new cApiDbfsCollection();

$dlevels = array();

if (is_array($objects)) {
    foreach ($objects as $a_file) {
        $file = $a_file->getName();
        $depth = $a_file->getCustom('level') - 1;
        $pathstring = $a_file->getId();
        $a_file->setCollapsedIcon('images/grid_expand.gif');
        $a_file->setExpandedIcon('images/grid_collapse.gif');
        $dlevels[$depth] = $a_file->getCustom('lastitem');
        $collapse = getUplExpandCollapseButton($a_file);
        $fileurl = rawurlencode($path . $file . '/');
        $pathurl = rawurlencode($path);

        if ($file == 'tmp') {
            echo 'tmp2<br>';
        }

        // Indent for every level
        $indent = 18 + (($depth - 1) * 18);

        // create javascript multilink
        $tmp_mstr = '<a href="javascript:Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>';
        $mstr = sprintf($tmp_mstr, 'right_top', $sess->url("main.php?area=$area&frame=3&path=$pathstring&appendparameters=$appendparameters"), 'right_bottom', $sess->url("main.php?area=$area&frame=4&path=$pathstring&appendparameters=$appendparameters"), '<img class="vAlignMiddle" src="images/grid_folder.gif" border="0" alt=""><img src="images/spacer.gif" align="middle" width="5" border="0">' . $file);

        $hasFiles = $dbfsc->hasFiles($pathstring);

        if (!$hasFiles && $perm->have_perm_area_action($tmp_area, 'upl_rmdir')) {
            // $deletebutton = '
            // <a title="' . i18n("Delete directory") . '"
            // href="javascript:void(0)"
            // onclick="event.cancelBubble=true;Con.showConfirmation(&quot;' .
            // i18n("Do you really want to delete the following directory:") .
            // '<b>' . $file . '</b>' . '&quot;, function() {
            // deleteDirectory(&quot;' . $pathstring . '&quot;); });return
            // false;">
            // <img class="vAlignMiddle" src="' . $cfg['path']['images'] .
            // 'delete.gif" border="0" title="' . i18n("Delete directory") . '"
            // alt="' . i18n("Delete directory") . '">
            // </a>';
            $deletebutton = '
    <a class="jsDelete" title="' . i18n("Delete directory") . '" href="javascript:void(0)">
       <img class="vAlignMiddle" src="' . $cfg['path']['images'] . 'delete.gif" border="0" title="' . i18n("Delete directory") . '" alt="' . i18n("Delete directory") . '">
    </a>';
        } else {
            if ($hasFiles) {
                $message = i18n("Directory contains files");
            } else {
                $message = i18n("Permission denied");
            }
            $deletebutton = '<img class="vAlignMiddle" alt="" src="' . $cfg['path']['images'] . 'delete_inact.gif" border="0" alt="' . $message . '" title="' . $message . '">';
        }

        $gline = '';
        for ($i = 1; $i < $depth; $i++) {
            if ($dlevels[$i] == false && $i != 0) {
                $gline .= '<img class="vAlignMiddle" src="images/grid_linedown.gif" alt="" align="middle">';
            } else {
                $gline .= '<img class="vAlignMiddle" src="images/spacer.gif" width="18" height="18" alt="" align="middle">';
            }
        }

        $parent = str_replace($cfgClient[$client]['upl']['path'], '', $a_file->getCustom('parent'));

        $idAttrPath = str_replace(array(
            '/',
            ':'
        ), array(
            '_',
            ''
        ), trim($pathstring, '/'));
        $tpl->set('d', 'ID_PATH', $idDbfsPathPrefix . $idAttrPath);
        $tpl->set('d', 'DATA_PATH', $pathstring);
        $tpl->set('d', 'INDENT', 0);
        $tpl->set('d', 'DIRNAME', $mstr);
        $tpl->set('d', 'EDITBUTTON', '');
        $tpl->set('d', 'DELETEBUTTON', $deletebutton);
        $tpl->set('d', 'COLLAPSE', $gline . $collapse);
        $tpl->next();
    }
}

$pathPrefix = (cApiDbfs::isDbfs($path)) ? $idDbfsPathPrefix : $idFsPathPrefix;
$idAttrPath = str_replace(array(
    '/',
    ':'
), array(
    '_',
    ''
), trim($path, '/'));
$tpl->set('s', 'ID_PATH', $pathPrefix . $idAttrPath);
$tpl->set('s', 'DELETE_MSG', i18n("Do you really want to delete the following directory:") . '<b>{path}</b>');

chdir(cRegistry::getBackendPath());

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['upl_dirs_overview']);
