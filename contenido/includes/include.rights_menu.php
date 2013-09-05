<?php
/**
 * This file contains the menu frame backend page for user management.
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
$sToday = date('Y-m-d');

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
    $link->setMultiLink("user", "", "user_overview", "");
    $link->setCustom("userid", $cApiUser->get("user_id"));

    if ($bDisplayUser == true) {
        $iItemCount++;

        if ($iItemCount > ($elemperpage * ($mPage - 1)) && $iItemCount < (($elemperpage * $mPage) + 1)) {
            if ($perm->have_perm_area_action('user', "user_delete")) {
                $message = sprintf(
                    i18n("Do you really want to delete the user %s?"),
                    htmlspecialchars(addslashes($cApiUser->get("username")))
                );

                $delTitle = i18n("Delete user");
                $deletebutton = '<a
                                    title="' . $delTitle . '"
                                    href="javascript:void(0)"
                                    onclick="showConfirmation(&quot;' . $message . '&quot;, function() { deleteBackenduser(&quot;' . $userid . '&quot;); });return false;"
                                 >
                                     <img
                                        src="' . $cfg['path']['images'] . 'delete.gif"
                                        border="0"
                                        title="' . $delTitle . '"
                                        alt="' . $delTitle . '"
                                     >
                                 </a>';
            } else {
                $deletebutton = '';
            }

            $iMenu++;

            if (($sToday < $cApiUser->get("valid_from") && ($cApiUser->get("valid_from") != '0000-00-00' && $cApiUser->get("valid_from") != '')) || ($sToday > $cApiUser->get("valid_to") && ($cApiUser->get("valid_to") != '0000-00-00') && $cApiUser->get("valid_from") != '')) {
                $mlist->setTitle($iMenu, '<span class="inactiveUser">' . $cApiUser->get("username") . "<br>" . $cApiUser->get("realname") . '</span>');
            } else {
                $mlist->setTitle($iMenu, $cApiUser->get("username") . "<br>" . $cApiUser->get("realname"));
            }

            $mlist->setLink($iMenu, $link);
            $mlist->setActions($iMenu, "delete", $deletebutton);

            if ($_GET['userid'] == $cApiUser->get("user_id")) {
                $mlist->setMarked($iMenu);
            }
        }
    }
}

$deleteScript = '<script type="text/javascript">

        /* Session-ID */
        var sid = "' . $sess->id . '";

        /* Function for deleting
           modules */

        function deleteBackenduser(userid) {

            form = parent.parent.left.left_top.document.filter;

            url  = \'main.php?area=user_overview\';
            url += \'&action=user_delete\';
            url += \'&frame=4\';
            url += \'&userid=\' + userid;
            url += \'&contenido=\' + sid;
            url += get_registered_parameters();
            url += \'&sortby=\' +form.sortby.value;
            url += \'&sortorder=\' +form.sortorder.value;
            url += \'&filter=\' +form.filter.value;
            url += \'&elemperpage=\' +form.elemperpage.value;
            url += \'&page=\' +\'' . $mPage . '\';
            parent.parent.right.right_bottom.location.href = url;
            parent.parent.right.right_top.location.href = \'main.php?area=user&frame=3&contenido=\'+sid;

        }

    </script>';

$markActiveScript = '<script type="text/javascript">
                         if (document.getElementById(\'marked\')) {
                             row.markedRow = document.getElementById(\'marked\');
                         }
                    </script>';
// <script type="text/javascript" src="scripts/rowMark.js"></script>
$oPage->addScript('parameterCollector.js');
$oPage->addScript($deleteScript);
$oDiv = new cHTMLDiv();
$oDiv->setContent($markActiveScript);
$oPage->setContent(array(
    $mlist,
    $oDiv
));

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
$sRefreshPager = '
    <script type="text/javascript">
        var sNavigation = \'' . $sPagerContent . '\';
        var left_top = parent.left_top;
        if (left_top.document) {
            var oPager = left_top.document.getElementById(\'44b41691-0dd4-443c-a594-66a8164e25fd\');

            if (oPager) {
                oInsert = oPager.firstChild;
                oInsert.innerHTML = sNavigation;
                left_top.toggle_pager(\'44b41691-0dd4-443c-a594-66a8164e25fd\');
            }
        }
    </script>';
$oPage->addScript($sRefreshPager);

$oPage->render();
