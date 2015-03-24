<?php
/**
 * This file contains the backend page for the editor of frontend users.
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

$page = new cGuiPage("frontend.user_edit");

$feusers = new cApiFrontendUserCollection();

if (is_array($cfg['plugins']['frontendusers'])) {
    foreach ($cfg['plugins']['frontendusers'] as $plugin) {
        plugin_include("frontendusers", $plugin."/".$plugin.".php");
    }
}

$feuser = new cApiFrontendUser();
$feuser->loadByPrimaryKey($idfrontenduser);

$oFEGroupMemberCollection = new cApiFrontendGroupMemberCollection();
$oFEGroupMemberCollection->setWhere('idfrontenduser', $idfrontenduser);
$oFEGroupMemberCollection->addResultField('idfrontendgroup');
$oFEGroupMemberCollection->query();

# Fetch all groups the user belongs to (no goup, one group, more than one group).
# The array $aFEGroup can be used in frontenduser plugins to display selfdefined user properties group dependent.
$aFEGroup = array();
while ($oFEGroup = $oFEGroupMemberCollection->next()) {
    $aFEGroup[] = $oFEGroup->get("idfrontendgroup");
}

if ($action == "frontend_create" && $perm->have_perm_area_action("frontend", "frontend_create")) {
    $feuser = $feusers->create(" ".i18n("-- new user --"));
    $idfrontenduser = $feuser->get("idfrontenduser");
    // put idfrontenduser of newly created user into superglobals for plugins
    $_GET['idfrontenduser'] = $idfrontenduser;
    $_REQUEST['idfrontenduser'] = $_GET['idfrontenduser'];
    //show success message
    $page->displayInfo(i18n("Created new user successfully!"));
}

if ($idfrontenduser && $action != '') {
    $sReloadScript = <<<JS
<script type="text/javascript">
(function(Con, $) {
    var frame = Con.getFrame('left_bottom');
    if (frame) {
        var href = Con.UtilUrl.replaceParams(frame.location.href, {frontenduser: {$idfrontenduser}});
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
    $sReloadScript = "";
}

if ($action == "frontend_delete" && $perm->have_perm_area_action("frontend", "frontend_delete")) {
    $feusers->delete($idfrontenduser);

    $iterator = $_cecRegistry->getIterator("Contenido.Permissions.FrontendUser.AfterDeletion");

    while ($chainEntry = $iterator->next()) {
        $chainEntry->execute($idfrontenduser);
    }

    $idfrontenduser = 0;
    $feuser = new cApiFrontendUser();
    if (!empty($sReloadScript)) {
        $page->addScript($sReloadScript);
    }
    $page->displayInfo(i18n("Delteted user successfully!"));
}

if ($feuser->virgin == false && $feuser->get("idclient") == $client) {
    $username = conHtmlentities(stripslashes(trim($username)));

    if ($action == "frontend_save_user" && strlen($username) == 0) {
        $page->displayError(i18n("Username can't be empty"));
    } else if ($action == "frontend_save_user" && strlen($username) > 0) {
        if (!empty($sReloadScript)) {
            $page->addScript($sReloadScript);
        }
        $messages = array();

        if ($feuser->get("username") != $username) {
            $feusers->select("username = '".$username."' and idclient='$client'");
            if ($feusers->next()) {
                $messages[] = i18n("Could not set new username: Username already exists");
            } else {
                $feuser->set("username", $username);
            }
        }

        if ($newpd != $newpd2) {
            $messages[] = i18n("Could not set new password: Passwords don't match");
        } else {
            if ($newpd != "") {
                $feuser->set("password", $newpd);
            }
        }

        $feuser->set("active", $active);

        // Check out if there are any plugins
        if (is_array($cfg['plugins']['frontendusers'])) {
            foreach ($cfg['plugins']['frontendusers'] as $plugin) {
                if (function_exists("frontendusers_".$plugin."_wantedVariables") &&
                    function_exists("frontendusers_".$plugin."_store"))
                {
                    // check if user belongs to a specific group
                    // if true store values defined in frontenduser plugin
                    if (function_exists("frontendusers_".$plugin."_checkUserGroup")) {
                        $bCheckUserGroup = call_user_func("frontendusers_".$plugin."_checkUserGroup");
                    } else {
                        $bCheckUserGroup = true;
                    }

                    if ($bCheckUserGroup) {
                        $wantVariables = call_user_func("frontendusers_".$plugin."_wantedVariables");

                        if (is_array($wantVariables)) {
                            $varArray = array();

                            foreach ($wantVariables as $value) {
                                if (is_array($GLOBALS[$value])) {
                                    foreach ($GLOBALS[$value] as $globKey => $globValue) {
                                        $GLOBALS[$value][$globKey] = stripslashes($globValue);
                                    }
                                } else {
                                    $varArray[$value] = stripslashes($GLOBALS[$value]);
                                }
                            }
                        }
                        $store = call_user_func("frontendusers_".$plugin."_store", $varArray);
                    }
                }
            }
        }

        $feuser->store();
        $page->displayInfo(i18n("Saved changes successfully!"));
    }

    if (count($messages) > 0) {
        $page->displayWarning(implode("<br>", $messages)) . "<br>";
    }


    $form = new cGuiTableForm("properties");
    $form->setVar("frame", $frame);
    $form->setVar("area", $area);
    $form->setVar("action", "frontend_save_user");
    $form->setVar("idfrontenduser", $idfrontenduser);

    $form->addHeader(i18n("Edit user"));

    $username = new cHTMLTextbox("username", $feuser->get("username"), 40);
    $newpw    = new cHTMLPasswordBox("newpd", "", 40);
    $newpw2   = new cHTMLPasswordBox("newpd2", "", 40);
    $active   = new cHTMLCheckbox("active", "1");
    $active->setChecked($feuser->get("active"));

    $form->add(i18n("User name"), $username->render());
    $form->add(i18n("New password"), $newpw->render());
    $form->add(i18n("New password (again)"), $newpw2->render());
    $form->add(i18n("Active"), $active->toHTML(false));

    $pluginOrder = cArray::trim(explode(',', getSystemProperty('plugin', 'frontendusers-pluginorder')));

    // Check out if there are any plugins
    if (is_array($pluginOrder)) {
        foreach ($pluginOrder as $plugin) {
            if (function_exists("frontendusers_".$plugin."_getTitle") &&
                function_exists("frontendusers_".$plugin."_display"))
            {
                // check if user belongs to a specific group
                // if true display frontenduser plugin
                if (function_exists("frontendusers_".$plugin."_checkUserGroup")) {
                    $bCheckUserGroup = call_user_func("frontendusers_".$plugin."_checkUserGroup");
                } else {
                    $bCheckUserGroup = true;
                }

                if ($bCheckUserGroup) {
                    $plugTitle = call_user_func("frontendusers_".$plugin."_getTitle");
                    $display = call_user_func("frontendusers_".$plugin."_display", $feuser);

                    if (is_array($plugTitle) && is_array($display)) {
                        foreach ($plugTitle as $key => $value) {
                            $form->add($value, $display[$key]);
                        }
                    } else {
                        if (is_array($plugTitle) || is_array($display)) {
                            $form->add(i18n("WARNING"), sprintf(i18n("The plugin %s delivered an array for the displayed titles, but did not return an array for the contents."), $plugin));
                        } else {
                            $form->add($plugTitle, $display);
                        }
                    }
                }
            }
        }

        $arrGroups = $feuser->getGroupsForUser();

        if (count($arrGroups) > 0) {
            $aMemberGroups = array();

            foreach ($arrGroups as $iGroup) {
                $oMemberGroup = new cApiFrontendGroup($iGroup);
                $aMemberGroups[] = $oMemberGroup->get("groupname");
            }

            asort($aMemberGroups);

            $sTemp = implode('<br>', $aMemberGroups);
        } else {
            $sTemp = i18n("none");
        }

        $form->add(i18n("Group membership"), $sTemp);

        $oUser = new cApiUser($feuser->get("author"));
        $form->add(i18n("Author"), $oUser->get('username') . " (". displayDatetime($feuser->get("created")).")");
        $oUser2 = new cApiUser($feuser->get("modifiedby"));
        $form->add(i18n("Last modified by"), $oUser2->get('username') . " (". displayDatetime($feuser->get("modified")).")");
    }
    $page->setContent($form);
    if (!empty($sReloadScript)) {
        $page->addScript($sReloadScript);
    }
}

if (!isset($form)) {
	$page->abortRendering();
}

$page->render();
?>