<?php

/**
 * This file contains the menu frame backend page for user management.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Timo Hummel
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$oPage = new cGuiPage("rights_menu");

$cApiUserCollection = new cApiUserCollection();
$cApiUserCollection->query();
$iSumUsers = $cApiUserCollection->count();

if (isset($_REQUEST["sortby"]) && $_REQUEST["sortby"] != "") {
    $cApiUserCollection->setOrder($_REQUEST["sortby"] . " " . $_REQUEST["sortorder"]);
} else {
    $cApiUserCollection->setOrder("username asc");
}

if (isset($_REQUEST["filter"]) && $_REQUEST["filter"] != "") {
    $cApiUserCollection->setWhereGroup("default", "username", "%" . $_REQUEST["filter"] . "%", "LIKE");
    $cApiUserCollection->setWhereGroup("default", "realname", "%" . $_REQUEST["filter"] . "%", "LIKE");
    $cApiUserCollection->setWhereGroup("default", "email", "%" . $_REQUEST["filter"] . "%", "LIKE");
    $cApiUserCollection->setWhereGroup("default", "telephone", "%" . $_REQUEST["filter"] . "%", "LIKE");
    $cApiUserCollection->setWhereGroup("default", "address_street", "%" . $_REQUEST["filter"] . "%", "LIKE");
    $cApiUserCollection->setWhereGroup("default", "address_zip", "%" . $_REQUEST["filter"] . "%", "LIKE");
    $cApiUserCollection->setWhereGroup("default", "address_city", "%" . $_REQUEST["filter"] . "%", "LIKE");
    $cApiUserCollection->setWhereGroup("default", "address_country", "%" . $_REQUEST["filter"] . "%", "LIKE");

    $cApiUserCollection->setInnerGroupCondition("default", "OR");
}
$cApiUserCollection->query();

$aCurrentUserPermissions = explode(',', $auth->auth['perm']);
$aCurrentUserAccessibleClients = $classclient->getAccessibleClients();

$iMenu = 0;
$iItemCount = 0;
$mPage = $_REQUEST["page"];

if ($mPage == 0) {
    $mPage = 1;
}

$elemperpage = $_REQUEST["elemperpage"];

if ($elemperpage == 0) {
    $elemperpage = 25;
}

$mlist = new cGuiMenu();
$sToday = date('Y-m-d H:i:s');

if (($elemperpage * $mPage) >= $iSumUsers + $elemperpage && $mPage != 1) {
    $_REQUEST["page"]--;
    $mPage--;
}

while ($cApiUser = $cApiUserCollection->next()) {
    $userid = $cApiUser->get("user_id");

    $aUserPermissions = explode(',', $cApiUser->get('perms'));

    $bDisplayUser = false;

    if (in_array("sysadmin", $aCurrentUserPermissions)) {
        $bDisplayUser = true;
    }

    foreach ($aCurrentUserAccessibleClients as $key => $value) {
        if (in_array("client[$key]", $aUserPermissions)) {
            $bDisplayUser = true;
        }
    }

    foreach ($aUserPermissions as $sLocalPermission) {
        if (in_array($sLocalPermission, $aCurrentUserPermissions)) {
            $bDisplayUser = true;
        }
    }

    $link = new cHTMLLink();
    $link->setClass('show_item')
        ->setLink('javascript:;')
        ->setAttribute('data-action', 'show_user');

    if ($bDisplayUser == true) {
        $iItemCount++;

        if ($iItemCount > ($elemperpage * ($mPage - 1)) && $iItemCount < (($elemperpage * $mPage) + 1)) {
            $iMenu++;

            // Delete button
            if ($perm->have_perm_area_action('user', "user_delete")) {
                $delTitle = i18n("Delete user");
                $deleteLink = '
                    <a href="javascript:;" data-action="delete_user" title="' . $delTitle . '" >
                        <img src="' . $cfg['path']['images'] . 'delete.gif" border="0" title="' . $delTitle . '" alt="' . $delTitle . '">
                    </a>
                ';
            } else {
                $deleteLink = '';
            }

            if (($sToday < $cApiUser->get("valid_from") && ($cApiUser->get("valid_from") != '0000-00-00 00:00:00' && $cApiUser->get("valid_from") != '')) || ($sToday > $cApiUser->get("valid_to") && ($cApiUser->get("valid_to") != '0000-00-00 00:00:00') && $cApiUser->get("valid_from") != '')) {
                $mlist->setTitle($iMenu, '<span class="inactiveUser"><span class="name">' . conHtmlSpecialChars($cApiUser->get("username")) . "</span><br>" . conHtmlSpecialChars($cApiUser->get("realname")) . '</span>');
            } else {
                $mlist->setTitle($iMenu, '<span class="name">' . conHtmlSpecialChars($cApiUser->get("username")) . "</span><br>" . conHtmlSpecialChars($cApiUser->get("realname")));
            }

            $mlist->setId($iMenu, $userid);
            $mlist->setLink($iMenu, $link);
            $mlist->setActions($iMenu, "delete", $deleteLink);

            if ($_GET['userid'] == $cApiUser->get("user_id")) {
                $mlist->setMarked($iMenu);
            }
        }
    }
}

$deleteMsg = i18n("Do you really want to delete the user %s?");
$oPage->set("s", "DELETE_MESSAGE", $deleteMsg);
$oPage->set("s", "MPAGE", $mPage);

// <script type="text/javascript" src="scripts/rowMark.js?v=4ff97ee40f1ac052f634e7e8c2f3e37e"></script>
$oPage->addScript('parameterCollector.js?v=4ff97ee40f1ac052f634e7e8c2f3e37e');
$oPage->set("s", "FORM", $mlist->render(false));

// generate current content for Object Pager
$oPagerLink = new cHTMLLink();
$oPagerLink->setLink("main.php");
$oPagerLink->setTargetFrame('left_bottom');
$oPagerLink->setCustom("elemperpage", $elemperpage);
$oPagerLink->setCustom("filter", $_REQUEST["filter"]);
$oPagerLink->setCustom("sortby", $_REQUEST["sortby"]);
$oPagerLink->setCustom("sortorder", $_REQUEST["sortorder"]);
$oPagerLink->setCustom("frame", $frame);
$oPagerLink->setCustom("area", $area);
$oPagerLink->enableAutomaticParameterAppend();
$oPagerLink->setCustom("contenido", $sess->id);

$pagerID = "pager";
$oPager = new cGuiObjectPager("44b41691-0dd4-443c-a594-66a8164e25fd", $iItemCount, $elemperpage, $page, $oPagerLink, "page", $pagerID);

// add slashes, to insert in javascript
$sPagerContent = $oPager->render(1);
$sPagerContent = str_replace('\\', '\\\\', $sPagerContent);
$sPagerContent = str_replace('\'', '\\\'', $sPagerContent);

// send new object pager to left_top
$oPage->set("s", "PAGER_CONTENT", $sPagerContent);

$oPage->render();
