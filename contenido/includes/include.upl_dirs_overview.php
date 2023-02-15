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

/**
 * @var cApiUser $currentuser
 * @var cPermission $perm
 * @var cSession $sess
 * @var cTemplate $tpl
 * @var cGuiNotification $notification
 * @var cDb $db
 * @var array $cfg
 * @var int $frame
 * @var string $area
 * @var string $upl_last_path Session variable
 */

cInclude('includes', 'functions.con.php');
cInclude('includes', 'functions.str.php');

$oClient = cRegistry::getClient();
$oLanguage = cRegistry::getLanguage();

// Display critical error if client or language does not exist
if (!$oClient->isLoaded() || !$oLanguage->isLoaded()) {
    $message = !$oClient->isLoaded() ? i18n('No Client selected') : i18n('No language selected');
    $oPage = new cGuiPage("upl_dirs_overview");
    $oPage->displayCriticalError($message);
    $oPage->render();
    return;
}

$appendparameters = $_REQUEST['appendparameters'] ?? '';
$collapse         = $_REQUEST['collapse'] ?? '';
$expand           = $_REQUEST['expand'] ?? '';

$client = cSecurity::toInteger(cRegistry::getClientId());
$lang = cSecurity::toInteger(cRegistry::getLanguageId());
$cfgClient = cRegistry::getClientConfig();

/**
 *
 * @param TreeItem $item
 *
 * @return string
 *
 * @throws cException
 */
function getUplExpandCollapseButton($item) {
    if (count($item->getSubItems()) > 0) {
        if ($item->isCollapsed() == true) {
            $title = i18n('Open category');
            // Attention: Render nodes without whitespace in between!
            $link = '<a href="javascript:void(0)" class="dir_collapse_link" data-action="expand_upl_dir" data-dir="' . $item->getId() . '" 
               title="' . $title . '"><img class="dir_collapse_img" 
                src="' . $item->getCollapsedIcon() . '" alt=""></a>';
        } else {
            $title = i18n('Close category');
            // Attention: Render nodes without whitespace in between!
            $link = '<a href="javascript:void(0)" class="dir_collapse_link" data-action="collapse_upl_dir" data-dir="' . $item->getId() . '" 
                title="' . $title . '"><img class="dir_collapse_img" 
                src="' . $item->getExpandedIcon() . '" alt=""></a>';
        }
    } else {
        if ($item->getCustom('lastitem')) {
            $link = '<img class="dir_collapse_img" src="images/but_lastnode.gif" alt="">';
        } else {
            $link = '<img class="dir_collapse_img" src="images/grid_collapse.gif" alt="">';
        }
    }

    return $link;
}

function getUplIdAttrPath($pathStr) {
    return str_replace(['/', ':'], ['_', ''], trim($pathStr, '/'));
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

$uplExpandedList = unserialize($currentuser->getUserProperty('system', 'upl_expandstate'));
$uplDbfsExpandedList = unserialize($currentuser->getUserProperty('system', 'upl_dbfs_expandstate'));

if (!is_array($uplExpandedList)) {
    $uplExpandedList = [];
}

if (!is_array($uplDbfsExpandedList)) {
    $uplDbfsExpandedList = [];
}

$tpl->reset();

// Show notification for error in dir name from upl_mkdir.action
if (isset($errno)) {
    if ($errno === '0703') {
        $tpl->set('s', 'WARNING', $notification->returnNotification('error', i18n('Directories with special characters and spaces are not allowed.')));
    } elseif ($errno === '0704') {
        $tpl->set('s', 'WARNING', $notification->returnNotification('error', i18n('Can not write directory.')));
    }
}

// Uploadfiles tree on file system

$file = 'Upload';

$rootTreeItem = new TreeItem();
$rootTreeItem->setCustom('level', 0);
$rootTreeItem->setName(i18n("Upload directory"));
$aInvalidDirectories = uplRecursiveDirectoryList($cfgClient[$client]["upl"]["path"], $rootTreeItem, 2);
if (count($aInvalidDirectories) > 0) {
    $sWarningInfo = i18n('The following directories contains invalid characters and were ignored: ');
    $sSeparator = '<br>';
    $sFiles = implode(', ', $aInvalidDirectories);
    $sRenameString = i18n('Please click here in order to rename automatically.');
    $sRenameHref = $sess->url("main.php?area=$area&frame=$frame&force_rename=true");
    $sRenameHref = '<a href="' . $sRenameHref . '">' . $sRenameString . '</a>';
    $sNotificationString = $sWarningInfo . $sSeparator . $sFiles . $sSeparator . $sSeparator . $sRenameHref;

    $sErrorString = $notification->returnNotification('warning', $sNotificationString);
    $tpl->set('s', 'WARNING', $sErrorString);
} else {
    $tpl->set('s', 'WARNING', '');
}

// Mark all items in the expandedList as expanded
foreach ($uplExpandedList as $key => $value) {
    $rootTreeItem->markExpanded($value);
}

// Collapse and expand the tree
if ($collapse) {
    $rootTreeItem->markCollapsed($collapse);
}

if ($expand) {
    $rootTreeItem->markExpanded($expand);
}

$uplExpandedList = [];
$rootTreeItem->getExpandedList($uplExpandedList);

$currentuser->setUserProperty('system', 'upl_expandstate', serialize($uplExpandedList));

$objects = [];
$rootTreeItem->traverse($objects);
unset($objects[0]);

if ($appendparameters == 'filebrowser') {
    $mtree   = new cGuiTree('b58f0ae3-8d4e-4bb3-a754-5f0628863364');
    $cattree = conFetchCategoryTree();
    $marray  = [];

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
        $marray[] = [
            'id'         => $catitem['idcat'],
            'name'       => $name,
            'level'      => $catitem['level'],
            'attributes' => [
                'icon' => $icon,
            ],
        ];
    }

    $mtree->setTreeName(i18n("Categories"));
    $mtree->setIcon('images/grid_folder.gif');
    $mtree->importTable($marray);

    $baselink = new cHTMLLink();
    $baselink->setCLink($area, $frame, '');
    $baselink->setCustom('appendparameters', $appendparameters);

    $mtree->setBaseLink($baselink);
    // @todo Where to get the $collapsed variable?
    $mtree->setCollapsed($collapsed ?? '');
    $mtree->processParameters();

    $collapsed = [];
    $mtree->getCollapsedList($collapsed);

    $tpl->set('s', 'CATBROWSER', $mtree->render());
    $tpl->set('s', 'APPENDPARAMETERS', '&appendparameters=' . $appendparameters);
} else {
    $tpl->set('s', 'CATBROWSER', '');
    $tpl->set('s', 'APPENDPARAMETERS', '&appendparameters=' . $appendparameters);
}

chdir(cRegistry::getBackendPath());

$idFsPathPrefix = 'fs_';
$pathString = '/';

$deleteTitle = i18n("Delete directory");
$deleteLinkTpl = '
    <a href="javascript:void(0)" data-action="delete_upl_dir" title="' . $deleteTitle . '">
        <img src="' . $cfg['path']['images'] . 'delete.gif" title="' . $deleteTitle . '" alt="' . $deleteTitle . '">
    </a>
';

// Show link
$showLink = sprintf(
    '<a id="root" href="javascript:void(0)" class="show_item" data-action="show_upl_dir">%s</a>',
    '<img class="dir_root_img" src="images/ordner_oben.gif" alt="">' . $file
);

$tpl->set('d', 'ID_PATH', $idFsPathPrefix . 'root');
$tpl->set('d', 'DATA_PATH', $pathString);
$tpl->set('d', 'INDENT', 3);
$tpl->set('d', 'DIRNAME', $showLink);
$tpl->set('d', 'EDITBUTTON', '');
$tpl->set('d', 'DELETEBUTTON', '');
$tpl->set('d', 'COLLAPSE', '');
$tpl->next();

if (is_array($objects)) {
    foreach ($objects as $a_file) {
        $file = $a_file->getName();
        $depth = $a_file->getCustom('level') - 1;
        $pathString = str_replace($cfgClient[$client]['upl']['path'], '', $a_file->getId());
        $a_file->setCollapsedIcon('images/grid_expand.gif');
        $a_file->setExpandedIcon('images/grid_collapse.gif');
        $dlevels[$depth] = $a_file->getCustom('lastitem');
        $imgCollapse = getUplExpandCollapseButton($a_file);
        $fileUrl = rawurlencode($path . $file . '/');
        $pathUrl = rawurlencode($path);

        // Indent for every level
        $indent = 18 + (($depth - 1) * 18);

        // Show link
        $showLink = sprintf(
            '<a href="javascript:void(0)" class="dir_folder_link show_item" data-action="show_upl_dir">%s</a>',
            '<img class="dir_folder_img" src="images/grid_folder.gif" alt="">' . $file
        );

        $hasFiles = uplHasFiles($pathString);
        $hasSubdirs = uplHasSubdirs($pathString);

        if ((!$hasSubdirs) && (!$hasFiles) && $perm->have_perm_area_action($tmp_area, "upl_rmdir")) {
            $deleteLink = $deleteLinkTpl;
        } else {
            if ($hasFiles) {
                $message = i18n("Directory contains files");
            } else {
                $message = i18n("Permission denied");
            }
            $deleteLink = '<img src="' . $cfg['path']['images'] . 'delete_inact.gif" alt="' . $message . '" title="' . $message . '">';
        }

        $gline = '';
        for ($i = 1; $i < $depth; $i++) {
            if ($dlevels[$i] == false && $i != 0) {
                $gline .= '<img class="dir_vline_img" alt="" src="images/grid_linedown.gif">';
            } else {
                $gline .= '<img class="dir_vline_img" alt="" src="images/spacer.gif">';
            }
        }

        $parent = str_replace($cfgClient[$client]['upl']['path'], '', $a_file->getCustom('parent'));

        $idAttrPath = getUplIdAttrPath($pathString);
        $tpl->set('d', 'ID_PATH', $idFsPathPrefix . $idAttrPath);
        $tpl->set('d', 'DATA_PATH', $pathString);
        $tpl->set('d', 'INDENT', 0);
        $tpl->set('d', 'DIRNAME', $showLink);
        $tpl->set('d', 'EDITBUTTON', '');
        $tpl->set('d', 'DELETEBUTTON', $deleteLink);
        $tpl->set('d', 'COLLAPSE', $gline . $imgCollapse);
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
$pathString = cApiDbfs::PROTOCOL_DBFS;
$rootTreeItem = new TreeItem();
$rootTreeItem->setCustom('level', 0);

uplRecursiveDBDirectoryList('', $rootTreeItem, 2, $client);

// Mark all items in the expandedList as expanded
foreach ($uplDbfsExpandedList as $key => $value) {
    $rootTreeItem->markExpanded($value);
}

// Collapse and expand the tree
if ($collapse) {
    $rootTreeItem->markCollapsed($collapse);
}

if ($expand) {
    $rootTreeItem->markExpanded($expand);
}

$uplDbfsExpandedList = [];
$rootTreeItem->getExpandedList($uplDbfsExpandedList);

$currentuser->setUserProperty('system', 'upl_dbfs_expandstate', serialize($uplDbfsExpandedList));

$objects = [];
$rootTreeItem->traverse($objects);

unset($objects[0]);

// Show link
$showLink = sprintf(
    '<a href="javascript:void(0)" class="show_item" data-action="show_upl_dir">%s</a>',
    '<img class="dir_root_img" src="images/ordner_oben.gif" alt="">' . $file
);

$tpl->set('d', 'ID_PATH', $idDbfsPathPrefix . 'root');
$tpl->set('d', 'DATA_PATH', $pathString);
$tpl->set('d', 'INDENT', 3);
$tpl->set('d', 'DIRNAME', $showLink);
$tpl->set('d', 'EDITBUTTON', '');
$tpl->set('d', 'DELETEBUTTON', '');
$tpl->set('d', 'COLLAPSE', '');
$tpl->next();

$dbfsCollection = new cApiDbfsCollection();

$dlevels = [];

if (is_array($objects)) {
    foreach ($objects as $a_file) {
        $file = $a_file->getName();
        $depth = $a_file->getCustom('level') - 1;
        $pathString = $a_file->getId();
        $a_file->setCollapsedIcon('images/grid_expand.gif');
        $a_file->setExpandedIcon('images/grid_collapse.gif');
        $dlevels[$depth] = $a_file->getCustom('lastitem');
        $collapse = getUplExpandCollapseButton($a_file);
        $fileUrl = rawurlencode($path . $file . '/');
        $pathUrl = rawurlencode($path);

        if ($file == 'tmp') {
            echo 'tmp2<br>';
        }

        // Indent for every level
        $indent = 18 + (($depth - 1) * 18);

        // Show link
        $showLink = sprintf(
            '<a href="javascript:void(0)" class="dir_folder_link show_item" data-action="show_upl_dir">%s</a>',
            '<img class="dir_folder_img" src="images/grid_folder.gif" alt="">' . $file
        );

        $hasFiles = $dbfsCollection->hasFiles($pathString);

        if (!$hasFiles && $perm->have_perm_area_action($tmp_area, 'upl_rmdir')) {
            $deleteLink = $deleteLinkTpl;
        } else {
            if ($hasFiles) {
                $message = i18n("Directory contains files");
            } else {
                $message = i18n("Permission denied");
            }
            $deleteLink = '<img alt="" src="' . $cfg['path']['images'] . 'delete_inact.gif" alt="' . $message . '" title="' . $message . '">';
        }

        $gline = '';
        for ($i = 1; $i < $depth; $i++) {
            if ($dlevels[$i] == false && $i != 0) {
                $gline .= '<img class="dir_vline_img" src="images/grid_linedown.gif" alt="">';
            } else {
                $gline .= '<img class="dir_vline_img" src="images/spacer.gif" alt="">';
            }
        }

        $parent = str_replace($cfgClient[$client]['upl']['path'], '', $a_file->getCustom('parent'));

        $idAttrPath = getUplIdAttrPath($pathString);
        $tpl->set('d', 'ID_PATH', $idDbfsPathPrefix . $idAttrPath);
        $tpl->set('d', 'DATA_PATH', $pathString);
        $tpl->set('d', 'INDENT', 0);
        $tpl->set('d', 'DIRNAME', $showLink);
        $tpl->set('d', 'EDITBUTTON', '');
        $tpl->set('d', 'DELETEBUTTON', $deleteLink);
        $tpl->set('d', 'COLLAPSE', $gline . $collapse);
        $tpl->next();
    }
}

if (empty($path) || $path === '/') {
    $currentPath = $idFsPathPrefix . 'root';
} elseif ($action == 'upl_delete' && $path === $_REQUEST['path']) {
    $currentPath = $idFsPathPrefix . 'root';
} else {
    $pathPrefix = (cApiDbfs::isDbfs($path)) ? $idDbfsPathPrefix : $idFsPathPrefix;
    $idAttrPath = getUplIdAttrPath($path);
    $currentPath = $pathPrefix . $idAttrPath;
}
$tpl->set('s', 'ID_PATH', $currentPath);
$tpl->set('s', 'DELETE_MESSAGE', i18n("Do you really want to delete the following directory:") . '<b>{path}</b>');

chdir(cRegistry::getBackendPath());

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['upl_dirs_overview']);
