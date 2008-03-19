<?php
/*****************************************
* File      :   $RCSfile: include.frontend.user_menu.php,v $
* Project   :   Contenido
* Descr     :   Frontend group list
* Modified  :   $Date: 2004/01/14 17:30:48 $
*
* © four for business AG, www.4fb.de
*
* $Id: include.frontend.user_menu.php,v 1.2 2004/01/14 17:30:48 timo.hummel Exp $
******************************************/
cInclude("classes", "widgets/class.widgets.page.php");
cInclude("classes", "class.ui.php");
cInclude("classes", "class.frontend.users.php");
cInclude("classes", "class.frontend.groups.php");

$page = new cPage;
$menu = new UI_Menu;

$fegroups = new FrontendGroupCollection;
$fegroups->select("idclient = '$client'","", "groupname ASC");

// Create the link to show/edit the frontend user
//$link = new Link;
//$link->setMultiLink("frontendgroups","","frontendgroups","frontendgroup_create");
//$menu->setImage("-2", $cfg["path"]["images"] . "folder_new.gif");	
//$menu->setLink("-2", $link);
//$menu->setTitle("-2", i18n("Create group"));
//$menu->setLink("-1", "");
//$menu->setTitle("-1", "");
//$menu->setImage("-1", "");

while ($fegroup = $fegroups->next())
{
	$groupname = $fegroup->get("groupname");
	$idfegroup = $fegroup->get("idfrontendgroup");
	
    $link = new Link;
    $link->setMultiLink("frontendgroups","","frontendgroups","");
    $link->setCustom("idfrontendgroup",$idfegroup);

    $delTitle = i18n("Delete frontend group");
    $delDescr = sprintf(i18n("Do you really want to delete the following frontend group:<br><b>%s</b>"),htmlspecialchars($groupname));
  	$delete = '<a title="'.$delTitle.'" href="javascript://" onclick="box.confirm(\''.$delTitle.'\', \''.$delDescr.'\', \'deleteFrontendGroup(\\\''.$idfegroup.'\\\')\')"><img src="'.$cfg['path']['images'].'delete.gif" border="0" title="'.$delTitle.'" alt="'.$delTitle.'"></a>';	
		
  	$menu->setTitle($idfegroup, $groupname);	
    $menu->setLink($idfegroup, $link);
    $menu->setImage($idfegroup, "", 0);	
	$menu->setActions($idfegroup, 'delete', $delete);
    
    if ($_GET['idfrontendgroup'] == $idfegroup) {
        $menu->setExtra($idfegroup, 'id="marked" ');
    } 
} 


$sInitRowMark = "<script type=\"text/javascript\">
                 if (document.getElementById('marked')) {
                     row.markedRow = document.getElementById('marked');
                 }
            </script>";

$delScript = '
    <script type="text/javascript">

        
        /* Session-ID */
        var sid = "'.$sess->id.'";

        /* Create messageBox
           instance */
        box = new messageBox("", "", "", 0, 0);

        /* Function for deleting
           modules */

        function deleteFrontendGroup(idfrontendgroup) {
            url  = "main.php?area=frontendgroups";
            url += "&action=frontendgroup_delete";
            url += "&frame=4";
            url += "&idfrontendgroup=" + idfrontendgroup;
            url += "&contenido=" + sid;
            parent.parent.right.right_bottom.location.href = url;

        }
		</script>';

$msgboxInclude = '<script type="text/javascript" src="scripts/messageBox.js.php?contenido='.$sess->id.'"></script>';        

$page->addScript('include', $msgboxInclude);
$page->addScript('del',$delScript);
$page->setMargin(0);
$page->setMargin(0);
$page->setContent($menu->render(false).$sInitRowMark);
$page->render();

?>