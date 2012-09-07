<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Displays languages
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.0
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-05-08
 *   modified 2008-06-26, Dominik Ziegler, add security fix
 *
 *   $Id: include.client_menu.php 338 2008-06-27 09:02:23Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

$tpl->set('s', 'SID', $sess->id);

if (!isset($action)) $action = "";

if ($action == "client_delete")
{
    if ($perm->have_perm_area_action("client", "client_delete")) {

   $sql = "DELETE FROM "
             .$cfg["tab"]["clients"].	
          " WHERE
             idclient = '".Contenido_Security::toInteger($idclient)."'";
   $db->query($sql);
    } 
          
}

$sql = "SELECT
        *
        FROM
        ".$cfg["tab"]["clients"];

$db->query($sql);

while ($db->next_record()) {
	$idclient = $db->f("idclient");
	if ((strpos($auth->auth["perm"],"admin[$idclient]") !== false) ||
		(strpos($auth->auth["perm"],"sysadmin") !== false))
	{
    	$dark = !$dark;
    	if ($dark) {
	        $bgColor = $cfg["color"]["table_dark"];
	    } else {
        	$bgColor = $cfg["color"]["table_light"];
    	}

        $tmp_mstr = '<a href="javascript:conMultiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>';
		$idclient = $db->f("idclient");
        $mstr = sprintf($tmp_mstr, 'right_top',
                                       $sess->url("main.php?area=$area&frame=3&idclient=$idclient"),
                                       'right_bottom',
                                       $sess->url("main.php?area=client_edit&frame=4&idclient=$idclient"),
                                       $db->f("name") );
                                                                              
        if (!$classclient->hasLanguageAssigned($idclient) && $perm->have_perm_area_action('client',"client_delete") ) {
       		$delTitle = i18n("Delete client");
        	$delDescr = sprintf(i18n("Do you really want to delete the following client:<br><br>%s<br>"),htmlspecialchars($db->f("name")));
    
        	$tpl->set('d', 'DELETE', '<a title="'.$delTitle.'" href="javascript://" onclick="box.confirm(\''.$delTitle.'\', \''.$delDescr.'\', \'deleteClient(\\\''.$idclient.'\\\')\')"><img src="'.$cfg['path']['images'].'delete.gif" border="0" title="'.$delTitle.'" alt="'.$delTitle.'"></a>');
        		
            } else {
                $tpl->set('d', 'DELETE', '&nbsp;');
            }
    
    	$tpl->set('d', 'ICON', '<img src="images/spacer.gif" width="12">');
        $tpl->set('d', 'BGCOLOR', $bgColor);
        $tpl->set('d', 'TEXT', $mstr);
        
        if ($_GET['idclient'] == $idclient) {
            $tpl->set('d', 'ID', 'id="marked"');
        } else {
            $tpl->set('d', 'ID', '');
        }
        
        $tpl->next();
	}
}    
# Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['client_menu']);
?>
