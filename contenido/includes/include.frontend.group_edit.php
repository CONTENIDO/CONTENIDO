<?php
/**
 * This file contains the backend page for the editor of frontend group details.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Unknown
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$fegroups = new cApiFrontendGroupCollection();
$page = new cGuiPage("grouprights_memberselect", "", 0);

if (is_array($cfg['plugins']['frontendgroups'])) {
    foreach ($cfg['plugins']['frontendgroups'] as $plugin) {
        plugin_include("frontendgroups", $plugin."/".$plugin.".php");
    }
}
$successMessage = '';
$fegroup      = new cApiFrontendGroup();
$groupmembers = new cApiFrontendGroupMemberCollection();
$fegroup->loadByPrimaryKey($idfrontendgroup);
$sRefreshRightTopLinkJs = "";

if ($action == "frontendgroup_create" && $perm->have_perm_area_action($area, $action)) {
   $fegroup = $fegroups->create(" ".i18n("-- New group --"));
   $idfrontendgroup = $fegroup->get("idfrontendgroup");
   $sRefreshRightTopLink = $sess->url('main.php?frame=3&area='.$area.'&idfrontendgroup='.$idfrontendgroup);
   $sRefreshRightTopLink = "Con.multiLink('right_top', '".$sRefreshRightTopLink."')";
   $sRefreshRightTopLinkJs = '<script type="text/javascript">' . $sRefreshRightTopLink . '</script>';
   $successMessage = i18n("Created new frontend-group successfully");
} else if ($action == "frontendgroups_user_delete" && $perm->have_perm_area_action($area, $action)) {
    $aDeleteMembers = array();
    if (!is_array($_POST['user_in_group'])) {
        if ($_POST['user_in_group'] > 0) {
            array_push($aDeleteMembers, $_POST['user_in_group']);
        }
    } else {
        $aDeleteMembers = $_POST['user_in_group'];
    }
    foreach ($aDeleteMembers as $idfrontenduser) {
        $groupmembers->remove($idfrontendgroup, $idfrontenduser);
    }

    $successMessage = i18n("Removed user from group successfully!");
    # also save other variables
    $action = "frontendgroup_save_group";
} else if ($action == "frontendgroup_user_add" && $perm->have_perm_area_action($area, $action)) {
    if (count($newmember) > 0) {
        foreach ($newmember as $add) {
            $groupmembers->create($idfrontendgroup, $add);
        }
    }
    $successMessage = i18n("Added user to group successfully!");
    # also save other variables
    $action = "frontendgroup_save_group";
} else if ($action == "frontendgroup_delete" && $perm->have_perm_area_action($area, $action)) {
   $fegroups->delete($idfrontendgroup);
   $idfrontendgroup= 0;
   $fegroup = new cApiFrontendGroup();

  cRegistry::addInfoMessage(i18n("Deleted group successfully!"));
}

if ($action != '') {
    $sReloadScript = <<<JS
<script type="text/javascript">
(function(Con, $) {
    var frame = Con.getFrame('left_bottom');
    if (frame) {
        var href = Con.UtilUrl.replaceParams(frame.location.href, {idfrontendgroup: {$idfrontendgroup}, action: null});
        frame.location.href = href;
        var frame2 = Con.getFrame('left_top');
        if (frame2 && 'function' === $.type(frame2.refresh)) {
            frame2.refresh();
        }
    }
})(Con, Con.$);
</script>
JS;
} else {
    $sReloadScript = '';
}

if ($fegroup->virgin == false && $fegroup->get("idclient") == $client) {
    if ($action == "frontendgroup_save_group" && $perm->have_perm_area_action($area, $action)) {
        $messages = array();

        if ($fegroup->get("groupname") != stripslashes($groupname)) {
            $fegroups->select("groupname = '$groupname' and idclient='$client'");
            if ($fegroups->next()) {
                $messages[] = i18n("Could not set new group name: Group already exists");
            } else {
                $fegroup->set("groupname", stripslashes($groupname));

                if (!isset($successMessage)) {
                    $successMessage = i18n("Saved changes successfully!");
                }
            }
        }

        //Reset all default groups
        if ($defaultgroup == 1) {
            $sSql = 'UPDATE '.$cfg["tab"]["frontendgroups"].' SET defaultgroup = 0 WHERE idclient='.$client.';';
            $db->query($sSql);
        }
        $fegroup->set("defaultgroup", $defaultgroup);

        // Check out if there are any plugins
        if (is_array($cfg['plugins']['frontendgroups'])) {
            foreach ($cfg['plugins']['frontendgroups'] as $plugin) {
                if (function_exists("frontendgroups_".$plugin."_wantedVariables") &&
                    function_exists("frontendgroups_".$plugin."_store"))
                {
                    $wantVariables = call_user_func("frontendgroups_".$plugin."_wantedVariables");

                    if (is_array($wantVariables)) {
                        $varArray = array();

                        foreach ($wantVariables as $value) {
                            $varArray[$value] = stripslashes($GLOBALS[$value]);
                        }
                    }
                    $store = call_user_func("frontendgroups_".$plugin."_store", $varArray);
                }
            }
        }

        $fegroup->store();
    }

    if (count($messages) > 0) {
        $notis = $notification->returnNotification("warning", implode("<br>", $messages)) . "<br>";
    } else {
        if (strlen($successMessage) > 0) {
            cRegistry::addInfoMessage($successMessage);
        } elseif (strlen($action) > 0) {
            cRegistry::addInfoMessage(i18n("Saved changes successfully!"));
        }
    }


    $feusers = new cApiFrontendUserCollection();
    $feusers->select("idclient='$client'");

    $addedusers = $groupmembers->getUsersInGroup($idfrontendgroup,false);
    $addeduserobjects = $groupmembers->getUsersInGroup($idfrontendgroup,true);

    $cells = array();
    foreach ($addeduserobjects as $addeduserobject) {
        if ((int)$addeduserobject->get("idfrontenduser") != 0 && $addeduserobject->get("username") != '') {
            $cells[$addeduserobject->get("idfrontenduser")] = $addeduserobject->get("username");
        }
    }

    asort($cells);

    $sInGroupOptions = '';
    foreach ($cells as $idfrontenduser => $name) {
        $sInGroupOptions .= '<option value="'.$idfrontenduser.'">'.$name.'</option>'."\n";
    }
    $page->set('s', 'IN_GROUP_OPTIONS', $sInGroupOptions);

    $items = array();
    while ($feuser = $feusers->next()) {
        $idfrontenduser = $feuser->get("idfrontenduser");
        $sUsername = $feuser->get("username");

        if (!in_array($idfrontenduser,$addedusers)) {
            if ((int) $idfrontenduser != 0 && $sUsername != '') {
                $items[$idfrontenduser] = $sUsername;
            }
        }
    }

    asort($items);

    $sNonGroupOptions = '';
    foreach ($items as $idfrontenduser => $name) {
        $sNonGroupOptions .= '<option value="'.$idfrontenduser.'">'.$name.'</option>'."\n";
    }
    $page->set('s', 'NON_GROUP_OPTIONS', $sNonGroupOptions);

    $groupname = new cHTMLTextbox("groupname", $fegroup->get("groupname"),40);

    $defaultgroup = new cHTMLCheckbox("defaultgroup", "1");
    $defaultgroup->setChecked($fegroup->get("defaultgroup"));

    $page->set('d', 'LABEL', i18n("Group name"));
    $page->set('d', 'INPUT', $groupname->render());
    $page->next();

    $page->set('d', 'LABEL', i18n("Default group"));
    $page->set('d', 'INPUT', $defaultgroup->toHTML(false));
    $page->next();

    $pluginOrder = cArray::trim(explode(',', getSystemProperty('plugin', 'frontendgroups-pluginorder')));

    // Check out if there are any plugins
    if (is_array($pluginOrder)) {
        foreach ($pluginOrder as $plugin) {
            if (function_exists("frontendgroups_".$plugin."_getTitle") &&
                function_exists("frontendgroups_".$plugin."_display"))
            {

                $plugTitle = call_user_func("frontendgroups_".$plugin."_getTitle");
                $display = call_user_func("frontendgroups_".$plugin."_display", $fegroup);

                if (is_array($plugTitle) && is_array($display)) {
                    foreach ($plugTitle as $key => $value) {
                        $page->set('d', 'LABEL', $value);
                        $page->set('d', 'INPUT', $display[$key]);
                        $page->next();
                    }
                } else {
                    if (is_array($plugTitle) || is_array($display)) {
                        $page->set('d', 'LABEL', "WARNING");
                        $page->set('d', 'INPUT', "The plugin $plugin delivered an array for the displayed titles, but did not return an array for the contents.");
                        $page->next();
                    } else {
                        $page->set('d', 'LABEL', $plugTitle);
                        $page->set('d', 'INPUT', $display);
                        $page->next();
                    }
                }
            }
        }
    }

    $page->set('s', 'CATNAME', i18n("Edit group"));
    $page->set('s', 'CATFIELD', "&nbsp;");
    $page->set('s', 'FORM_ACTION', $sess->url('main.php'));
    $page->set('s', 'AREA', $area);
    $page->set('s', 'GROUPID', $idfrontendgroup);
    $page->set('s', 'FRAME', $frame);
    $page->set('s', 'IDLANG', $lang);
    $page->set('s', 'STANDARD_ACTION', 'frontendgroup_save_group');
    $page->set('s', 'ADD_ACTION', 'frontendgroup_user_add');
    $page->set('s', 'DELETE_ACTION', 'frontendgroups_user_delete');
    $page->set('s', 'DISPLAY_OK', 'block');
    $page->set('s', 'IN_GROUP_VALUE', $_POST['filter_in']);
    $page->set('s', 'NON_GROUP_VALUE', $_POST['filter_non']);
    $page->set('s', 'RECORD_ID_NAME', 'idfrontendgroup');
    $page->set('s', 'RELOADSCRIPT', $sReloadScript.$sRefreshRightTopLinkJs);

    $page->render();
} else {
    $page = new cGuiPage("frontend.group_edit");
    if (!empty($sReloadScript)) {
        $page->addScript($sReloadScript);
    }

    $page->render();
}
?>