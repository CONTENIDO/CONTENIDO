<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Rights menu
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.2
 * @author     Olaf Niemann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-04-23
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2009-11-06, Murat Purc, replaced deprecated functions (PHP 5.3 ready)
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


$oPage = new cPage;

$cApiUserCollection = new cApiUserCollection;
$cApiUserCollection->query();
$iSumUsers = $cApiUserCollection->count();

if (isset($_REQUEST["sortby"]) && $_REQUEST["sortby"] != "")
{
	$cApiUserCollection->setOrder($_REQUEST["sortby"]. " ". $_REQUEST["sortorder"]);	
} else {
	$cApiUserCollection->setOrder("username asc");
}

if (isset($_REQUEST["filter"]) && $_REQUEST["filter"] != "")
{
	$cApiUserCollection->setWhereGroup("default", "username", "%".$_REQUEST["filter"]."%", "LIKE");	
	$cApiUserCollection->setWhereGroup("default", "realname", "%".$_REQUEST["filter"]."%", "LIKE");
	$cApiUserCollection->setWhereGroup("default", "email", "%".$_REQUEST["filter"]."%", "LIKE");
	$cApiUserCollection->setWhereGroup("default", "telephone", "%".$_REQUEST["filter"]."%", "LIKE");
	$cApiUserCollection->setWhereGroup("default", "address_street", "%".$_REQUEST["filter"]."%", "LIKE");
	$cApiUserCollection->setWhereGroup("default", "address_zip", "%".$_REQUEST["filter"]."%", "LIKE");
	$cApiUserCollection->setWhereGroup("default", "address_city", "%".$_REQUEST["filter"]."%", "LIKE");
	$cApiUserCollection->setWhereGroup("default", "address_country", "%".$_REQUEST["filter"]."%", "LIKE");
	
	$cApiUserCollection->setInnerGroupCondition("default", "OR");
}
$cApiUserCollection->query();

$aCurrentUserPermissions = explode(',', $auth->auth['perm']);
$aCurrentUserAccessibleClients = $classclient->getAccessibleClients();

$iMenu = 0;
$iItemCount = 0;
$mPage = $_REQUEST["page"];

if ($mPage == 0)
{
	$mPage = 1;	
}

$elemperpage = $_REQUEST["elemperpage"];

if ($elemperpage == 0)
{
	$elemperpage = 25;
}

$mlist = new UI_Menu;
$sToday = date('Y-m-d');


if (($elemperpage*$mPage) >= $iSumUsers+$elemperpage && $mPage  != 1) {
    $_REQUEST["page"]--;
    $mPage--;
}

while ($cApiUser = $cApiUserCollection->next())
{
	$userid = $cApiUser->get("user_id");
	
	$aUserPermissions = explode(',', $cApiUser->get('perms'));
	
	$bDisplayUser = false;

    if (in_array("sysadmin", $aCurrentUserPermissions))
    {
        $bDisplayUser = true;
    }
    
    foreach ($aCurrentUserAccessibleClients as $key => $value)
    {
        if (in_array("client[$key]", $aUserPermissions))
        {
            $bDisplayUser = true;
        }
    }
    
    foreach ($aUserPermissions as $sLocalPermission)
    {
        if (in_array($sLocalPermission, $aCurrentUserPermissions))
        {
            $bDisplayUser = true;
        }
    }    
    
    $link = new cHTMLLink;
    $link->setMultiLink("user", "", "user_overview", "");
    $link->setCustom("userid", $cApiUser->get("user_id"));
    
    if ($bDisplayUser == true)
    {
    	$iItemCount++;

    	if ($iItemCount > ($elemperpage * ($mPage - 1)) && $iItemCount < (($elemperpage * $mPage) + 1))
    	{
	        if ($perm->have_perm_area_action('user',"user_delete") ) { 
	        		$message = sprintf(i18n("Do you really want to delete the user %s?"), $cApiUser->get("username"));
	        		
					$delTitle = i18n("Delete user");
					$deletebutton = '<a title="'.$delTitle.'" href="javascript://" onclick="box.confirm(\''.$delTitle.'\', \''.$message.'\', \'deleteBackenduser(\\\''.$userid.'\\\')\')"><img src="'.$cfg['path']['images'].'delete.gif" border="0" title="'.$delTitle.'" alt="'.$delTitle.'"></a>';
				        		
	            } else {
	                $deletebutton = "";
	            }

	    	$iMenu++;
            
            if (($sToday < $cApiUser->get("valid_from") && ($cApiUser->get("valid_from") != '0000-00-00' && $cApiUser->get("valid_from") != '')) ||
                ($sToday > $cApiUser->get("valid_to") && ($cApiUser->get("valid_to") != '0000-00-00') && $cApiUser->get("valid_from") != '')) {
                $mlist->setTitle($iMenu, '<span style="color:#b3b3b8">'.$cApiUser->get("username")."<br>".$cApiUser->get("realname").'</span>');
            }  else {
                $mlist->setTitle($iMenu, $cApiUser->get("username")."<br>".$cApiUser->get("realname"));
            }            

	    	$mlist->setLink($iMenu, $link);		
	    	$mlist->setActions($iMenu, "delete", $deletebutton); 
            
            if ($_GET['userid'] == $cApiUser->get("user_id")) {
                $mlist->setExtra($iMenu, 'id="marked" ');
            }
    	}
    }
	
}

$deleteScript = '<script type="text/javascript">

        /* Session-ID */
        var sid = "'.$sess->id.'";

        /* Create messageBox
           instance */
        box = new messageBox("", "", "", 0, 0);

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
			url += \'&page=\' +\''.$mPage.'\';
			parent.parent.right.right_bottom.location.href = url;
			parent.parent.right.right_top.location.href = \'main.php?area=user&frame=3&contenido=\'+sid;

        }

    </script>';
    
$markActiveScript = '<script type="text/javascript">
                         if (document.getElementById(\'marked\')) {
                             row.markedRow = document.getElementById(\'marked\');
                         }
                    </script>';
    //<script type="text/javascript" src="scripts/rowMark.js"></script>
$oPage->setMargin(0);
$oPage->addScript('rowMark.js', '<script language="JavaScript" src="scripts/rowMark.js"></script>');
$oPage->addScript('parameterCollector.js', '<script language="JavaScript" src="scripts/parameterCollector.js"></script>');
$oPage->addScript('messagebox', '<script type="text/javascript" src="scripts/messageBox.js.php?contenido='.$sess->id.'"></script>');
$oPage->addScript('delete', $deleteScript);
$oPage->setContent($mlist->render(false).$markActiveScript);

//generate current content for Object Pager
$oPagerLink = new cHTMLLink;
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

$pagerID="pager";
$oPager = new cObjectPager("44b41691-0dd4-443c-a594-66a8164e25fd", $iItemCount, $elemperpage, $page, $oPagerLink, "page", $pagerID);


//add slashes, to insert in javascript
$sPagerContent = $oPager->render(1);
$sPagerContent = str_replace('\\', '\\\\', $sPagerContent);
$sPagerContent = str_replace('\'', '\\\'', $sPagerContent);

//send new object pager to left_top
$sRefreshPager = '
    <script type="text/javascript">
        var sNavigation = \''.$sPagerContent.'\';
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
$oPage->addScript('refreshpager', $sRefreshPager); 

$oPage->render();

?>