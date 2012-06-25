<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO Groups Menu
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.1.2
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created 2003-05-30
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

$tpl->reset();
$tpl->set('s', 'SID', $sess->id);

if (($action == "group_delete") && ($perm->have_perm_area_action($area, $action))) {

   $sql = "DELETE FROM "
             .$cfg["tab"]["groups"].
          " WHERE
             group_id = \"" .Contenido_Security::escapeDB($groupid, $db)."\"";
   $db->query($sql);

   $sql = "DELETE FROM ".
               $cfg["tab"]["groupmembers"].
               " WHERE group_id = \"" .Contenido_Security::escapeDB($groupid, $db)."\"";

   $db->query($sql);


   $sql = "DELETE FROM ".
               $cfg["tab"]["rights"].
               " WHERE user_id = \"" .Contenido_Security::escapeDB($groupid, $db)."\"";

   $db->query($sql);
}

$sql = "SELECT
            groupname, group_id, perms
        FROM
            ".$cfg["tab"]["groups"]."
        ORDER BY
            groupname ASC";


if ($restriction == 1)
{
     $sql = "SELECT
            A.groupname AS groupname, A.group_id as group_id, A.perms as perms
        FROM
            ".$cfg["tab"]["groups"]." AS A,
            ".$cfg["tab"]["rights"]." AS B,
            ".$cfg["tab"]["actions"]." AS C
        WHERE
            C.name = 'front_allow' AND
            B.user_id = A.group_id AND
            C.idaction = B.idaction AND
            A.perms LIKE ''
        GROUP BY
            group_id
        ORDER BY
            groupname ASC";
}

if ($restriction == 3)
{
     $sql = "SELECT
            A.groupname AS groupname, A.group_id as group_id, A.perms as perms
        FROM
            ".$cfg["tab"]["groups"]." AS A,
            ".$cfg["tab"]["rights"]." AS B,
            ".$cfg["tab"]["actions"]." AS C
        WHERE
            C.name NOT LIKE 'front_allow' AND
            B.user_id = A.group_id AND
            C.idaction = B.idaction AND
            A.perms NOT LIKE ''
        GROUP BY
            group_id
        ORDER BY
            groupname ASC";
}
$db->query($sql);

// Empty Row
$bgcolor = '#FFFFFF';
$tpl->set('s', 'PADDING_LEFT', '10');
$thisperm = explode(',', $auth->auth['perm']);

$accessibleClients = $classclient->getAccessibleClients();

while ($db->next_record())
{

    $groupperm = explode(',', $db->f('perms'));

    $allow = false;

    // Sysadmin check
    if (in_array("sysadmin", $thisperm))
    {
        $allow = true;
    }

    // Admin check
    foreach ($accessibleClients as $key => $value)
    {
        if (in_array("client[".$key."]", $groupperm))
        {
            $allow = true;
        }
    }

    // Group check
    foreach ($groupperm as $localperm)
    {

        if (in_array($localperm, $thisperm))
        {
            $allow = true;
        }

    }

    if ($allow == true)
    {

        $groupid = $db->f("group_id");
        $groupname = $db->f("groupname");

        $groupname = substr($groupname, 4);

        $tmp_mstr = '<a href="javascript:conMultiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>';
        $area = "groups";
        $mstr = sprintf($tmp_mstr, 'right_top',
                                       $sess->url("main.php?area=$area&frame=3&groupid=$groupid"),
                                       'right_bottom',
                                       $sess->url("main.php?area=groups_overview&frame=4&groupid=$groupid"),
                                       $groupname);

        $mstr2 = sprintf($tmp_mstr, 'right_top',
                                       $sess->url("main.php?area=$area&frame=3&groupid=$groupid"),
                                       'right_bottom',
                                       $sess->url("main.php?area=groups_overview&frame=4&groupid=$groupid"),
                                       '<img src="images/spacer.gif" border="0" width="15">');

        if ($perm->have_perm_area_action('groups',"groups_delete") ) {
                $message = sprintf(i18n("Do you really want to delete the group %s?"),htmlspecialchars($groupname));
                $deletebutton = "<a onClick=\"event.cancelBubble=true;check=confirm('".$message."'); if (check==true) { location.href='".$sess->url("main.php?area=groups&action=group_delete&frame=$frame&groupid=$groupid&del=")."#deletethis'};\" href=\"#\"><img src=\"".$cfg['path']['images']."delete.gif\" border=\"0\" width=\"13\" height=\"13\" alt=\"".i18n("Delete group")."\" title=\"".i18n("Delete group")."\"></a>";
            } else {
                $deletebutton = "";
            }

        if ($_GET['groupid'] == $groupid) {
            $tpl->set('d', 'ID_MARKED', 'marked');
        } else {
            $tpl->set('d', 'ID_MARKED', '');
        }

        $tpl->set('d', 'TEXT', $mstr);
        $tpl->set('d', 'ICON', $mstr2);

        if ($perm->have_perm_area_action('groups',"groups_delete") ) {
         $delTitle = i18n("Delete group");
        $delDescr = sprintf(i18n("Do you really want to delete the following group:<br><br>%s<br>"),htmlspecialchars($groupname));


        $tpl->set('d', 'DELETE', '<a title="'.$delTitle.'" href="javascript://" onclick="box.confirm(\''.$delTitle.'\', \''.$delDescr.'\', \'deleteGroup(\\\''.$groupid.'\\\')\')"><img src="'.$cfg['path']['images'].'delete.gif" border="0" title="'.$delTitle.'" alt="'.$delTitle.'"></a>');
        } else {
            $tpl->set('d', 'DELETE', '&nbsp;');
        }

        $tpl->next();
    }
}

# Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['grouprights_menu']);

?>