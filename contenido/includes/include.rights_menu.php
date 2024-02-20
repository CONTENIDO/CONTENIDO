<?php

/**
 * This file contains the menu frame backend page for user management.
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

global $classclient;

$auth = cRegistry::getAuth();
$sess = cRegistry::getSession();
$perm = cRegistry::getPerm();
$area = cRegistry::getArea();
$frame = cRegistry::getFrame();
$cfg = cRegistry::getConfig();

$page = isset($_REQUEST['page']) ? abs(cSecurity::toInteger($_REQUEST['page'])) : 1;
$elemPerPage = cSecurity::toInteger($_REQUEST['elemperpage'] ?? '0');
$sortby = cSecurity::toString($_REQUEST['sortby'] ?? '');
$sortorder = cSecurity::toString($_REQUEST['sortorder'] ?? 'asc');
$filter = cSecurity::toString($_REQUEST['filter'] ?? '');
$userid = cSecurity::toString($_GET['userid'] ?? '');

$oPage = new cGuiPage("rights_menu");

$cApiUserCollection = new cApiUserCollection();
$cApiUserCollection->query();
$iSumUsers = $cApiUserCollection->count();

if (!empty($sortby)) {
    $cApiUserCollection->setOrder($sortby . " " . $sortorder);
} else {
    $cApiUserCollection->setOrder("username asc");
}

if (!empty($filter)) {
    $cApiUserCollection->setWhereGroup("default", "username", "%" . $filter . "%", "LIKE");
    $cApiUserCollection->setWhereGroup("default", "realname", "%" . $filter . "%", "LIKE");
    $cApiUserCollection->setWhereGroup("default", "email", "%" . $filter . "%", "LIKE");
    $cApiUserCollection->setWhereGroup("default", "telephone", "%" . $filter . "%", "LIKE");
    $cApiUserCollection->setWhereGroup("default", "address_street", "%" . $filter . "%", "LIKE");
    $cApiUserCollection->setWhereGroup("default", "address_zip", "%" . $filter . "%", "LIKE");
    $cApiUserCollection->setWhereGroup("default", "address_city", "%" . $filter . "%", "LIKE");
    $cApiUserCollection->setWhereGroup("default", "address_country", "%" . $filter . "%", "LIKE");

    $cApiUserCollection->setInnerGroupCondition("default", "OR");
}
$cApiUserCollection->query();

$aCurrentUserPermissions = explode(',', $auth->auth['perm']);
$aCurrentUserAccessibleClients = $classclient->getAccessibleClients();

$iMenu = 0;
$iItemCount = 0;
$mPage = $page;

if ($mPage == 0) {
    $mPage = 1;
}

if ($elemPerPage == 0) {
    $elemPerPage = 25;
}

$mlist = new cGuiMenu();
$sToday = date('Y-m-d H:i:s');

if (($elemPerPage * $mPage) >= $iSumUsers + $elemPerPage && $mPage != 1) {
    $page--;
    $mPage--;
}

/**
 * @var cApiUser $currentuser
 */
$rightsAreasHelper = new cRightsAreasHelper($currentuser, $auth, []);

$isAuthUserSysadmin = $rightsAreasHelper->isAuthSysadmin();

while ($cApiUser = $cApiUserCollection->next()) {
    $userid = $cApiUser->get('user_id');

    $aUserPermissions = explode(',', $cApiUser->get('perms') ?? '');
    $rightsAreasHelper->setContextPermissions($aUserPermissions);


    $bDisplayUser = false;

    if ($isAuthUserSysadmin) {
        $bDisplayUser = true;
    }

    if (!$bDisplayUser) {
        foreach ($aCurrentUserAccessibleClients as $key => $value) {
            if (cPermission::checkClientPermission(cSecurity::toInteger($key), $aUserPermissions)) {
                $bDisplayUser = true;
            }
        }
    }

    if (!$bDisplayUser) {
        foreach ($aUserPermissions as $sLocalPermission) {
            if (in_array($sLocalPermission, $aCurrentUserPermissions)) {
                $bDisplayUser = true;
            }
        }
    }

    if ($bDisplayUser) {
        $iItemCount++;

        $link = new cHTMLLink();
        $link->setClass('show_item')
            ->setLink('javascript:void(0)')
            ->setAttribute('data-action', 'show_user');

        if ($iItemCount > ($elemPerPage * ($mPage - 1)) && $iItemCount < (($elemPerPage * $mPage) + 1)) {
            $iMenu++;

            // Delete button
            if ($perm->have_perm_area_action('user', "user_delete")) {
                $delTitle = i18n("Delete user");
                $deleteLink = '<a class="con_img_button" href="javascript:void(0)" data-action="delete_user" title="' . $delTitle . '">'
                    . cHTMLImage::img($cfg['path']['images'] . 'delete.gif', $delTitle)
                    . '</a>';
            } else {
                $deleteLink = '';
            }

            $userInfo = '<span class="name">' . conHtmlSpecialChars($cApiUser->get("username")) . "</span><br>" . conHtmlSpecialChars($cApiUser->get("realname") ?? '');
            $isValidFromEmpty = cDate::isEmptyDate($cApiUser->get("valid_from"));
            $isValidToEmpty = cDate::isEmptyDate($cApiUser->get("valid_to"));
            if (($sToday < $cApiUser->get("valid_from") && !$isValidFromEmpty) || ($sToday > $cApiUser->get("valid_to") && !$isValidToEmpty && !$isValidFromEmpty)) {
                $userInfo = '<span class="is_inactive">' . $userInfo . '</span>';
            }
            $mlist->setTitle($iMenu, $userInfo);
            $mlist->setId($iMenu, $userid);
            $mlist->setLink($iMenu, $link);
            $mlist->setActions($iMenu, "delete", $deleteLink);

            if ($userid == $cApiUser->get("user_id")) {
                $mlist->setMarked($iMenu);
            }
        }
    }
}

$deleteMsg = i18n("Do you really want to delete the user %s?");
$oPage->set("s", "DELETE_MESSAGE", $deleteMsg);
$oPage->set("s", "MPAGE", $mPage);

// <script type="text/javascript" src="{_ASSET(scripts/rowMark.js)_}"></script>
$oPage->addScript('parameterCollector.js');
$oPage->set("s", "FORM", $mlist->render(false));

// generate current content for Object Pager
$oPagerLink = new cHTMLLink();
$oPagerLink->setLink("main.php");
$oPagerLink->setTargetFrame('left_bottom');
$oPagerLink->setCustom("elemperpage", $elemPerPage);
$oPagerLink->setCustom("filter", $filter);
$oPagerLink->setCustom("sortby", $sortby);
$oPagerLink->setCustom("sortorder", $sortorder);
$oPagerLink->setCustom("frame", $frame);
$oPagerLink->setCustom("area", $area);
$oPagerLink->enableAutomaticParameterAppend();
$oPagerLink->setCustom("contenido", $sess->id);

$pagerID = "pager";
$oPager = new cGuiObjectPager("44b41691-0dd4-443c-a594-66a8164e25fd", $iItemCount, $elemPerPage, $page, $oPagerLink, "page", $pagerID);

// add slashes, to insert in javascript
$sPagerContent = $oPager->render(1);
$sPagerContent = str_replace('\\', '\\\\', $sPagerContent);
$sPagerContent = str_replace('\'', '\\\'', $sPagerContent);

// send new object pager to left_top
$oPage->set("s", "PAGER_CONTENT", $sPagerContent);

$oPage->render();
