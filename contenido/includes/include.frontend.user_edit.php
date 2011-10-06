<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Frontend user editor
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend includes
 * @version    1.1.10
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *  created unknown
 *  modified 2008-06-27, Frederic Schneider, add security fix
 *  modified 2009-06-02, Andreas Lindner, fix check for duplicate user name when it contains a special character
 *  modified 2011-06-01, Ortwin Pinke, fixed CON-402 german umlaute not correct displayed for membergroups
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


$page = new cPage();

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
while($oFEGroup = $oFEGroupMemberCollection->next()) {
    $aFEGroup[] = $oFEGroup->get("idfrontendgroup");
}

if ($action == "frontend_create" && $perm->have_perm_area_action("frontend", "frontend_create")) {
    $feuser = $feusers->create(" ".i18n("-- new user --"));
    $idfrontenduser = $feuser->get("idfrontenduser");
}

if ($idfrontenduser && $action != '') {
    $sReloadScript = "<script type=\"text/javascript\">
                         var left_bottom = parent.parent.frames['left'].frames['left_bottom'];
                         if (left_bottom) {
                             var href = left_bottom.location.href;
                             href = href.replace(/&frontenduser.*/, '');
                             left_bottom.location.href = href+'&frontenduser='+".$idfrontenduser.";
                             top.content.left.left_top.refresh();
                         }
                     </script>";
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
    $page->addScript('reload', $sReloadScript);
}

if ($feuser->virgin == false && $feuser->get("idclient") == $client) {
    if ($action == "frontend_save_user") {
        $page->addScript('reload', $sReloadScript);
        $messages = array();

        if ($feuser->get("username") != stripslashes($username)) {
            $feusers->select("username = '".urlencode($username)."' and idclient='$client'");
            if ($feusers->next()) {
                $messages[] = i18n("Could not set new username: Username already exists");
            } else {
                $feuser->set("username", stripslashes($username));
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
                                $varArray[$value] = stripslashes($GLOBALS[$value]);
                            }
                        }
                        $store = call_user_func("frontendusers_".$plugin."_store", $varArray);
                    }
                }
            }
        }

        $feuser->store();
    }

    if (count($messages) > 0) {
        $notis = $notification->returnNotification("warning", implode("<br>", $messages)) . "<br>";
    }


    $form = new UI_Table_Form("properties");
    $form->setVar("frame", $frame);
    $form->setVar("area", $area);
    $form->setVar("action", "frontend_save_user");
    $form->setVar("idfrontenduser", $idfrontenduser);

    $form->addHeader(i18n("Edit user"));

    $username = new cHTMLTextbox("username", $feuser->get("username"),40);
    $newpw    = new cHTMLPasswordBox("newpd","",40);
    $newpw2   = new cHTMLPasswordBox("newpd2","",40);
    $active   = new cHTMLCheckbox("active","1");
    $active->setChecked($feuser->get("active"));

    $form->add(i18n("User name"), $username->render());
    $form->add(i18n("New password"), $newpw->render());
    $form->add(i18n("New password (again)"), $newpw2->render());
    $form->add(i18n("Active"), $active->toHTML(false));

    $pluginOrder = trim_array(explode(",",getSystemProperty("plugin", "frontendusers-pluginorder")));

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

            foreach($arrGroups as $iGroup) {
                $oMemberGroup = new cApiFrontendGroup($iGroup);
                $aMemberGroups[] = $oMemberGroup->get("groupname");
            }

            asort($aMemberGroups);

            $sTemp = implode('<br/>', $aMemberGroups);
        } else {
            $sTemp = i18n("none");
        }

        $form->add(i18n("Group membership"), $sTemp );

        $form->add(i18n("Author"), $classuser->getUserName($feuser->get("author")) . " (". $feuser->get("created").")" );
        $form->add(i18n("Last modified by"), $classuser->getUserName($feuser->get("modifiedby")). " (". $feuser->get("modified").")" );
    }
    $page->setContent($notis . $form->render(true));
    $page->addScript('reload', $sReloadScript);
} else {
    $page->setContent("");
}

$page->render();
?>