<?php
/**
 * This file contains the backend page for the directory overview in upload
 * section.
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude("includes", "functions.con.php");
cInclude("includes", "functions.str.php");

if (!(int) $client > 0) {
    // if there is no client selected, display empty page
    $oPage = new cGuiPage("upl_dirs_overview");
    $oPage->render();
    return;
}

function getUplExpandCollapseButton($item) {
    global $sess, $PHP_SELF, $frame, $area, $appendparameters;
    $selflink = "main.php";

    if (count($item->subitems) > 0) {
        if ($item->collapsed == true) {
            $expandlink = $sess->url($selflink . "?area=$area&frame=$frame&appendparameters=$appendparameters&expand=" . $item->id);
            return ('<a href="' . $expandlink . '" alt="' . i18n('Open category') . '" title="' . i18n('Open category') . '"><img src="' . $item->collapsed_icon . '" border="0" align="middle" width="18"></a>');
        } else {
            $collapselink = $sess->url($selflink . "?area=$area&appendparameters=$appendparameters&frame=$frame&collapse=" . $item->id);
            return ('<a href="' . $collapselink . '" alt="' . i18n('Close category') . '" title="' . i18n('Close category') . '"><img src="' . $item->expanded_icon . '" border="0" align="middle" width="18"></a>');
        }
    } else {
        if ($item->custom["lastitem"]) {
            return '<img class="vAlignMiddle" src="images/but_lastnode.gif" width="18" height="18">';
        } else {
            return '<img class="vAlignMiddle" src="images/grid_collapse.gif" width="18" height="18">';
        }
    }
}

// ###############
// Create Folder
// ###############
// ixxed by Timo Trautmann double database entries also called by action
// upl_mkdir
// Use remembered path from upl_last_path (from session)
if (!isset($path) && $sess->isRegistered("upl_last_path")) {
    $path = $upl_last_path;
}

$appendparameters = $_REQUEST["appendparameters"];

if (!isset($action))
    $action = "";

if ($tmp_area == "") {
    $tmp_area = $area; // $tmp_area used at two places for unknown reasons...
}

$uplexpandedList = unserialize($currentuser->getUserProperty("system", "upl_expandstate"));
$upldbfsexpandedList = unserialize($currentuser->getUserProperty("system", "upl_dbfs_expandstate"));

if (!is_array($uplexpandedList)) {
    $uplexpandedList = array();
}

if (!is_array($upldbfsexpandedList)) {
    $upldbfsexpandedList = array();
}

if ($action == "upl_renamedir") {
    if ($perm->have_perm_area_action("upl", "upl_renamedir")) {
        uplRenameDirectory($oldname, $newname, $parent);
        $path = $cfgClient[$client]['upl']['path'] . $parent . $newname . "/";
        if (in_array($cfgClient[$client]['upl']['path'] . $parent . $oldname . "/", $uplexpandedList)) {
            $uplexpandedList[] = $cfgClient[$client]['upl']['path'] . $parent . $newname . "/";
        }
    }
}

// #################
// File System Tree
// #################
$dbfs = new cApiDbfsCollection();

if ($action == "upl_delete") {
    if (cApiDbfs::isDbfs($path)) {
        $dbfs->remove($path . "/.");
    } else {
        /* Check for files */
        if (uplHasFiles($path)) {
            $failedFiles = array();
            if (is_dir($cfgClient[$client]["upl"]["path"] . $path)) {

                $directory = @opendir($cfgClient[$client]["upl"]["path"] . $path);
                if (false !== $directory) {
                    while (false !== ($dir_entry = readdir($directory))) {
                        if ($dir_entry != "." && $dir_entry != "..") {
                            $res = @ unlink($cfgClient[$client]["upl"]["path"] . $path . $dir_entry);

                            if ($res == false) {
                                $failedFiles[] = $dir_entry;
                            }
                        }
                    }
                }
                closedir($directory);
            }
        }

        if (count($failedFiles) > 0) {
            $notification->displayNotification("warning", i18n("Failed to delete the following files:") . "<br><br>" . implode("<br>", $failedFiles));
        } else {
            $res = @ rmdir($cfgClient[$client]['upl']['path'] . $path);
            if ($res == false) {
                $notification->displayNotification("warning", sprintf(i18n("Failed to remove directory %s"), $path));
            }
        }
    }
}

$tpl->reset();

// Show notification for error in dir name from upl_mkdir.action
if ($errno === '0703') {
    $tpl->set('s', 'WARNING', $notification->returnNotification('error', i18n('Directories with special characters and spaces are not allowed.')));
}

$file = 'Upload';
$pathstring = '';

$rootTreeItem = new TreeItem();
$rootTreeItem->custom["level"] = 0;
$rootTreeItem->name = i18n("Upload directory");
$aInvalidDirectories = uplRecursiveDirectoryList($cfgClient[$client]["upl"]["path"], $rootTreeItem, 2);
if (count($aInvalidDirectories) > 0) {
    $sWarningInfo = i18n('The following directories contains invalid characters and were ignored: ');
    $sSeperator = '<br>';
    $sFiles = implode(', ', $aInvalidDirectories);
    $sRenameString = i18n('Please click here in order to rename automatically.');
    $sRenameHref = $sess->url("main.php?area=$area&frame=$frame&force_rename=true");
    $sRemameLink = '<a href="' . $sRenameHref . '">' . $sRenameString . '</a>';
    $sNotificationString = $sWarningInfo . $sSeperator . $sFiles . $sSeperator . $sSeperator . $sRemameLink;

    $sErrorString = $notification->returnNotification("warning", $sNotificationString, 1);
    $tpl->set('s', 'WARNING', $sErrorString);
} else {
    $tpl->set('s', 'WARNING', '');
}

/* Mark all items in the expandedList as expanded */
foreach ($uplexpandedList as $key => $value) {
    $rootTreeItem->markExpanded($value);
}

/* Collapse and expand the tree */
if (is_string($collapse)) {
    $rootTreeItem->markCollapsed($collapse);
}

if (is_string($expand)) {
    $rootTreeItem->markExpanded($expand);
}

$uplexpandedList = array();
$rootTreeItem->getExpandedList($uplexpandedList);

$currentuser->setUserProperty("system", "upl_expandstate", serialize($uplexpandedList));

$objects = array();
$rootTreeItem->traverse($objects);
unset($objects[0]);

if ($appendparameters == "filebrowser") {
    $mtree = new cGuiTree("b58f0ae3-8d4e-4bb3-a754-5f0628863364");
    $cattree = conFetchCategoryTree();
    $marray = array();

    foreach ($cattree as $key => $catitem) {
        $no_start = true;
        $no_online = true;
        $no_start = !strHasStartArticle($catitem["idcat"], $lang);

        $no_online = !$catitem["visible"];

        if ($catitem["visible"] == 1) {
            if ($catitem["public"] == 0) {
                if ($no_start || $no_online) {
                    // Error found
                    $tmp_img = "folder_on_error_locked.gif";
                } else {
                    // No error found
                    $tmp_img = "folder_on_locked.gif";
                }
            } else {
                // Category is public
                if ($no_start || $no_online) {
                    // Error found
                    $tmp_img = "folder_on_error.gif";
                } else {
                    // No error found
                    $tmp_img = "folder_on.gif";
                }
            }
        } else {
            // Category is offline
            if ($catitem['public'] == 0) {
                // Category is locked
                if ($no_start || $no_online) {
                    // Error found
                    $tmp_img = "folder_off_error_locked.gif";
                } else {
                    // No error found
                    $tmp_img = "folder_off_locked.gif";
                }
            } else {
                // Category is public
                if ($no_start || $no_online) {
                    // Error found
                    $tmp_img = "folder_off_error.gif";
                } else {
                    // No error found
                    $tmp_img = "folder_off.gif";
                }
            }
        }

        $icon = "./images/" . $tmp_img;

        $idcat = $catitem["idcat"];

        $name = '&nbsp;<a href="' . $sess->url("main.php?area=$area&frame=5&idcat=$idcat&appendparameters=$appendparameters") . '" target="right_bottom">' . $catitem["name"] . '</a>';
        $marray[] = array(
            "id" => $catitem["idcat"],
            "name" => $name,
            "level" => $catitem["level"],
            "attributes" => array(
                "icon" => $icon
            )
        );
    }

    $mtree->setTreeName(i18n("Categories"));
    $mtree->setIcon("images/grid_folder.gif");
    $mtree->importTable($marray);

    $baselink = new cHTMLLink();
    $baselink->setCLink($area, $frame, "");
    $baselink->setCustom("appendparameters", $appendparameters);

    $mtree->setBaseLink($baselink);
    $mtree->setBackgroundMode(TREEVIEW_BACKGROUND_SHADED);
    $mtree->setMouseoverMode(cGuiTree::TREEVIEW_MOUSEOVER_NONE);
    $mtree->setCollapsed($collapsed);
    $mtree->processParameters();

    $collapsed = array();
    $mtree->getCollapsedList($collapsed);

    $tpl->set('s', 'CATBROWSER', $mtree->render());
    $tpl->set('s', 'APPENDPARAMETERS', 'url += \'&appendparameters=' . $appendparameters . '\'');
} else {
    $tpl->set('s', 'CATBROWSER', '');
    $tpl->set('s', 'APPENDPARAMETERS', 'url += \'&appendparameters=' . $appendparameters . '\'');
}

chdir(cRegistry::getBackendPath());

$tpl->set('s', 'SID', $sess->id);

// create javascript multilink
$tmp_mstr = '<a id="root" href="javascript:conMultiLink(\'%s\', \'%s\',\'%s\', \'%s\')">%s</a>';
$mstr = sprintf($tmp_mstr, 'right_top', $sess->url("main.php?area=$area&frame=3&path=$pathstring&appendparameters=$appendparameters"), 'right_bottom', $sess->url("main.php?area=$area&frame=4&path=$pathstring&appendparameters=$appendparameters"), '<img class="vAlignMiddle" src="images/ordner_oben.gif" align="middle" alt="" border="0"><img src="images/spacer.gif" width="5" border="0">' . $file);

$tpl->set('d', 'PATH', $pathstring);
$tpl->set('d', 'INDENT', 3);
$tpl->set('d', 'DIRNAME', $mstr);
$tpl->set('d', 'EDITBUTTON', '');
$tpl->set('d', 'DELETEBUTTON', '');
$tpl->set('d', 'COLLAPSE', '');
$tpl->next();

if (is_array($objects)) {
    foreach ($objects as $a_file) {
        $file = $a_file->name;
        $depth = $a_file->custom["level"] - 1;
        $pathstring = str_replace($cfgClient[$client]['upl']['path'], "", $a_file->id);
        $a_file->collapsed_icon = "images/grid_expand.gif";
        $a_file->expanded_icon = "images/grid_collapse.gif";
        $dlevels[$depth] = $a_file->custom["lastitem"];
        $imgcollapse = getUplExpandCollapseButton($a_file);
        $fileurl = rawurlencode($path . $file . '/');
        $pathurl = rawurlencode($path);

        // Indent for every level
        $cnt = $depth;
        $indent = 18;

        for ($i = 0; $i < $cnt; $i++) {
            $indent += 18;
        }

        // create javascript multilink # -> better create meaningful comments
        $tmp_mstr = '<a href="javascript:conMultiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>';
        $mstr = sprintf($tmp_mstr, 'right_bottom', $sess->url("main.php?area=$area&frame=4&path=$pathstring&appendparameters=$appendparameters"), 'right_top', $sess->url("main.php?area=$area&frame=3&path=$pathstring&appendparameters=$appendparameters"), '<img class="vAlignMiddle" src="images/grid_folder.gif" border="0" alt=""><img src="images/spacer.gif" align="middle" width="5" border="0">' . $file);

        $hasFiles = uplHasFiles($pathstring);
        $hasSubdirs = uplHasSubdirs($pathstring);

        if ((!$hasSubdirs) && (!$hasFiles) && $perm->have_perm_area_action($tmp_area, "upl_rmdir")) {
            $deletebutton = '<a title="' . i18n("Delete directory") . '" href="javascript:void(0)" onclick="event.cancelBubble=true;showConfirmation(&quot;' . i18n("Do you really want to delete the following directory:") . '<b>' . $file . '</b>&quot;, function() { deleteDirectory(&quot;' . $pathstring . '&quot;); });return false;"><img src="' . $cfg['path']['images'] . 'delete.gif" border="0" title="' . i18n("Delete directory") . '" alt="' . i18n("Delete directory") . '"></a>';
        } else {
            if ($hasFiles) {
                $message = i18n("Directory contains files");
            } else {
                $message = i18n("Permission denied");
            }

            $deletebutton = "<img src=\"" . $cfg["path"]["images"] . "delete_inact.gif\" border=\"0\" alt=\"" . $message . "\" title=\"" . $message . "\">";
        }

        $tpl->set('d', 'PATH', $pathstring);
        $tpl->set('d', 'INDENT', 0);

        $gline = "";

        for ($i = 1; $i < $depth; $i++) {
            if ($dlevels[$i] == false && $i != 0) {
                $gline .= '<img class="vAlignMiddle" src="images/grid_linedown.gif" width="18">';
            } else {
                $gline .= '<img class="vAlignMiddle" src="images/spacer.gif" width="18" height="18">';
            }
        }

        $parent = str_replace($cfgClient[$client]['upl']['path'], "", $a_file->custom["parent"]);

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

// ##################################
// Database-based filesystem (DBFS)
// ##################################
$file = i18n("Database file system");
$pathstring = 'dbfs:';
$rootTreeItem = new TreeItem();
$rootTreeItem->custom["level"] = 0;

uplRecursiveDBDirectoryList("", $rootTreeItem, 2, $client);

/* Mark all items in the expandedList as expanded */
foreach ($upldbfsexpandedList as $key => $value) {
    $rootTreeItem->markExpanded($value);
}

/* Collapse and expand the tree */
if (is_string($collapse)) {
    $rootTreeItem->markCollapsed($collapse);
}

if (is_string($expand)) {
    $rootTreeItem->markExpanded($expand);
}

$upldbfsexpandedList = array();
$rootTreeItem->getExpandedList($upldbfsexpandedList);

$currentuser->setUserProperty("system", "upl_dbfs_expandstate", serialize($upldbfsexpandedList));

$objects = array();
$rootTreeItem->traverse($objects);

unset($objects[0]);

$tmp_mstr = '<a href="javascript:conMultiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>';
$mstr = sprintf($tmp_mstr, 'right_top', $sess->url("main.php?area=$area&frame=3&path=$pathstring&appendparameters=$appendparameters"), 'right_bottom', $sess->url("main.php?area=$area&frame=4&path=$pathstring&appendparameters=$appendparameters"), '<img class="vAlignMiddle" src="images/ordner_oben.gif" alt="" border="0"><img src="images/spacer.gif" width="5" border="0">' . $file);

$tpl->set('d', 'PATH', $pathstring);
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
        $file = $a_file->name;
        $depth = $a_file->custom["level"] - 1;
        $pathstring = $a_file->id;
        $a_file->collapsed_icon = "images/grid_expand.gif";
        $a_file->expanded_icon = "images/grid_collapse.gif";
        $dlevels[$depth] = $a_file->custom["lastitem"];
        $collapse = getUplExpandCollapseButton($a_file);
        $fileurl = rawurlencode($path . $file . '/');
        $pathurl = rawurlencode($path);

        if ($file == 'tmp') {
            echo 'tmp2<br>';
        }

        // Indent for every level
        $cnt = $depth;
        $indent = 18;

        for ($i = 0; $i < $cnt; $i++) {
            // 18 px for every level
            $indent += 18;
        }

        // create javascript multilink
        $tmp_mstr = '<a href="javascript:conMultiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>';
        $mstr = sprintf($tmp_mstr, 'right_bottom', $sess->url("main.php?area=$area&frame=4&path=$pathstring&appendparameters=$appendparameters"), 'right_top', $sess->url("main.php?area=$area&frame=3&path=$pathstring&appendparameters=$appendparameters"), '<img class="vAlignMiddle" src="images/grid_folder.gif" border="0" alt=""><img src="images/spacer.gif" align="middle" width="5" border="0">' . $file);

        $hasFiles = $dbfsc->hasFiles($pathstring);

        if (!$hasFiles && $perm->have_perm_area_action($tmp_area, "upl_rmdir")) {
            $deletebutton = '<a title="' . i18n("Delete directory") . '" href="javascript:void(0)" onclick="event.cancelBubble=true;showConfirmation(&quot;' . i18n("Do you really want to delete the following directory:") . '<b>' . $file . '</b>' . '&quot;, function() { deleteDirectory(&quot;' . $pathstring . '&quot;); });return false;"><img class="vAlignMiddle" src="' . $cfg['path']['images'] . 'delete.gif" border="0" title="' . i18n("Delete directory") . '" alt="' . i18n("Delete directory") . '"></a>';
        } else {
            if ($hasFiles) {
                $message = i18n("Directory contains files");
            } else {
                $message = i18n("Permission denied");
            }

            $deletebutton = "<img class='vAlignMiddle' src=\"" . $cfg["path"]["images"] . "delete_inact.gif\" border=\"0\" alt=\"" . $message . "\" title=\"" . $message . "\">";
        }

        $tpl->set('d', 'PATH', $pathstring);
        $tpl->set('d', 'INDENT', 0);

        $gline = "";

        for ($i = 1; $i < $depth; $i++) {
            if ($dlevels[$i] == false && $i != 0) {
                $gline .= '<img class="vAlignMiddle" src="images/grid_linedown.gif" align="middle">';
            } else {
                $gline .= '<img class="vAlignMiddle" src="images/spacer.gif" width="18" height="18" align="middle">';
            }
        }

        $parent = str_replace($cfgClient[$client]['upl']['path'], "", $a_file->custom["parent"]);
        $tpl->set('d', 'DIRNAME', $mstr);
        $tpl->set('d', 'EDITBUTTON', '');
        $tpl->set('d', 'DELETEBUTTON', $deletebutton);
        $tpl->set('d', 'COLLAPSE', $gline . $collapse);
        $tpl->next();
    }
}

$tpl->set('s', 'ID_PATH', $path);
chdir(cRegistry::getBackendPath());
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['upl_dirs_overview']);
