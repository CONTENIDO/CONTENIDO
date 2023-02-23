<?php

/**
 * This file contains the backend page for the editor of frontend group details.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $notification;

$cfg = cRegistry::getConfig();
$sess = cRegistry::getSession();
$perm = cRegistry::getPerm();
$client = cRegistry::getClientId();
$area = cRegistry::getArea();
$frame = cRegistry::getFrame();
$lang = cRegistry::getLanguageId();
$action = cRegistry::getAction();
$db = cRegistry::getDb();

$fegroups = new cApiFrontendGroupCollection();
$page = new cGuiPage("grouprights_memberselect", "", 0);

cIncludePlugins('frontendgroups');

$requestUserInGroup = $_POST['user_in_group'] ?? '';
$requestFilterIn = $_POST['filter_in'] ?? '';
$requestFilterNon = $_POST['filter_non'] ?? '';
$requestIdFrontendGroup = cSecurity::toInteger($_REQUEST['idfrontendgroup'] ?? '0');
$requestNewMember = $_REQUEST['newmember'] ?? [];
$requestGroupName = $_REQUEST['groupname'] ?? '';
$requestDefaultGroup =  cSecurity::toInteger($_REQUEST['defaultgroup'] ?? '0');

$successMessage = '';
$fegroup      = new cApiFrontendGroup();
$groupmembers = new cApiFrontendGroupMemberCollection();
$fegroup->loadByPrimaryKey($requestIdFrontendGroup);
$sRefreshRightTopLinkJs = "";

if ($action == "frontendgroup_create" && $perm->have_perm_area_action($area, $action)) {
   $fegroup = $fegroups->create(" ".i18n("-- New group --"));
   $requestIdFrontendGroup = $fegroup->get("idfrontendgroup");
   $sRefreshRightTopLink = $sess->url('main.php?frame=3&area='.$area.'&idfrontendgroup='.$requestIdFrontendGroup);
   $sRefreshRightTopLink = "Con.multiLink('right_top', '".$sRefreshRightTopLink."')";
   $sRefreshRightTopLinkJs = '<script type="text/javascript">' . $sRefreshRightTopLink . '</script>';
   $successMessage = i18n("Created new frontend-group successfully");
} elseif ($action == "frontendgroups_user_delete" && $perm->have_perm_area_action($area, $action)) {
    $aDeleteMembers = [];
    if (!is_array($requestUserInGroup)) {
        if ($requestUserInGroup > 0) {
            $aDeleteMembers[] = $requestUserInGroup;
        }
    } else {
        $aDeleteMembers = $requestUserInGroup;
    }
    foreach ($aDeleteMembers as $idfrontenduser) {
        $groupmembers->remove($requestIdFrontendGroup, $idfrontenduser);
    }

    $successMessage = i18n("Removed user from group successfully!");
    // also save other variables
    $action = "frontendgroup_save_group";
} elseif ($action == "frontendgroup_user_add" && $perm->have_perm_area_action($area, $action)) {
    if (count($requestNewMember) > 0) {
        foreach ($requestNewMember as $add) {
            $groupmembers->create($requestIdFrontendGroup, $add);
        }
    }
    $successMessage = i18n("Added user to group successfully!");
    // also save other variables
    $action = "frontendgroup_save_group";
} elseif ($action == "frontendgroup_delete" && $perm->have_perm_area_action($area, $action)) {
   $fegroups->delete($requestIdFrontendGroup);
   $requestIdFrontendGroup= 0;
   $fegroup = new cApiFrontendGroup();

  cRegistry::addOkMessage(i18n("Deleted group successfully!"));
}

if ($action != '') {
    $reloadLeftBottom = <<<JS
<script type="text/javascript">
(function(Con, $) {
    var frame = Con.getFrame('left_bottom');
    if (frame) {
        var href = Con.UtilUrl.replaceParams(frame.location.href, {idfrontendgroup: {$requestIdFrontendGroup}, action: null});
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
    $reloadLeftBottom = '';
}

if (true === $fegroup->isLoaded() && $fegroup->get("idclient") == $client) {
    $messages = [];

    if ($action == "frontendgroup_save_group" && $perm->have_perm_area_action($area, $action)) {
        if ($fegroup->get("groupname") != stripslashes($requestGroupName)) {
            $fegroups->select("groupname = '$requestGroupName' and idclient='$client'");
            if ($fegroups->next()) {
                $messages[] = i18n("Could not set new group name: Group already exists");
            } else {
                $fegroup->set("groupname", stripslashes($requestGroupName));

                if (!isset($successMessage)) {
                    $successMessage = i18n("Saved changes successfully!");
                }
            }
        }

        //Reset all other default groups
        if ($requestDefaultGroup == 1) {
            $sSql = 'UPDATE `%s` SET defaultgroup = 0 WHERE idfrontendgroup != %d AND idclient = %d;';
            $db->query($sSql, $cfg["tab"]["frontendgroups"], $requestIdFrontendGroup, $client);
        }
        $fegroup->set("defaultgroup", $requestDefaultGroup);

        // Check out if there are any plugins
        if (cHasPlugins('frontendgroups')) {
            cCallPluginStore('frontendgroups');
        }

        $fegroup->store();
    }

    if (count($messages) > 0) {
        $notis = $notification->returnNotification("warning", implode("<br>", $messages)) . "<br>";
    } else {
        if (cString::getStringLength($successMessage) > 0) {
            cRegistry::addOkMessage($successMessage);
        } elseif (!empty($action)) {
            cRegistry::addOkMessage(i18n("Saved changes successfully!"));
        }
    }

    $feusers = new cApiFrontendUserCollection();
    $feusers->select("idclient='$client'");

    $addedusers = $groupmembers->getUsersInGroup($requestIdFrontendGroup, false);
    $addeduserobjects = $groupmembers->getUsersInGroup($requestIdFrontendGroup, true);

    $cells = [];
    foreach ($addeduserobjects as $addeduserobject) {
        if ((int) $addeduserobject->get("idfrontenduser") != 0 && $addeduserobject->get("username") != '') {
            $cells[$addeduserobject->get("idfrontenduser")] = $addeduserobject->get("username");
        }
    }
    asort($cells);

    $sInGroupOptions = '';
    foreach ($cells as $idfrontenduser => $name) {
        $sInGroupOptions .= '<option value="'.$idfrontenduser.'">'.conHtmlSpecialChars($name).'</option>'."\n";
    }
    $page->set('s', 'IN_GROUP_OPTIONS', $sInGroupOptions);

    $items = [];
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
        $sNonGroupOptions .= '<option value="'.$idfrontenduser.'">'.conHtmlSpecialChars($name).'</option>'."\n";
    }
    $page->set('s', 'NON_GROUP_OPTIONS', $sNonGroupOptions);

    $groupname = new cHTMLTextbox("groupname", $fegroup->get("groupname"),40);

    $defaultgroup = new cHTMLCheckbox("defaultgroup", "1");
    $defaultgroup->setChecked($fegroup->get("defaultgroup"));

    $page->set('d', 'LABEL', i18n("Group name"));
    $page->set('d', 'INPUT', $groupname->render());
    $page->next();

    $page->set('d', 'LABEL', i18n("Default group"));
    $page->set('d', 'INPUT', $defaultgroup->toHtml(false));
    $page->next();

    $pluginOrder = cArray::trim(explode(',', getSystemProperty('plugin', 'frontendgroups-pluginorder')));

    // Check out if there are any plugins
    if (is_array($pluginOrder)) {
        foreach ($pluginOrder as $plugin) {
            if (function_exists('frontendgroups_' . $plugin . '_getTitle') &&
                function_exists('frontendgroups_' . $plugin . '_display'))
            {
                $plugTitle = call_user_func('frontendgroups_' . $plugin . '_getTitle');
                $display = call_user_func('frontendgroups_' . $plugin . '_display', $fegroup);

                if (is_array($plugTitle) && is_array($display)) {
                    foreach ($plugTitle as $key => $value) {
                        $page->set('d', 'LABEL', $value);
                        $page->set('d', 'INPUT', $display[$key]);
                        $page->next();
                    }
                } else {
                    if (is_array($plugTitle) || is_array($display)) {
                        $page->set('d', 'LABEL', 'WARNING');
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
    $page->set('s', 'GROUPID', $requestIdFrontendGroup);
    $page->set('s', 'FRAME', $frame);
    $page->set('s', 'IDLANG', $lang);
    $page->set('s', 'STANDARD_ACTION', 'frontendgroup_save_group');
    $page->set('s', 'ADD_ACTION', 'frontendgroup_user_add');
    $page->set('s', 'DELETE_ACTION', 'frontendgroups_user_delete');
    $page->set('s', 'DISPLAY_OK', 'block');
    $page->set('s', 'IN_GROUP_VALUE', $requestFilterIn);
    $page->set('s', 'NON_GROUP_VALUE', $requestFilterNon);
    $page->set('s', 'RECORD_ID_NAME', 'idfrontendgroup');
    $page->set('s', 'RELOADSCRIPT', $reloadLeftBottom.$sRefreshRightTopLinkJs);

    $page->render();
} else {
    $page = new cGuiPage("frontend.group_edit");
    if (!empty($reloadLeftBottom)) {
        $page->addScript($reloadLeftBottom);
    }

    $page->render();
}
