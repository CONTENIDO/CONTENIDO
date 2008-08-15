<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * List layouts in database
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.1
 * @author     Olaf Niemann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-03-27
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "class.todo.php");
cInclude("classes", "contenido/class.layout.php");

$layouts = new cApiLayoutCollection;
$layouts->select("idclient = '$client'","","name ASC");

$tpl->reset();

$tpl->set('s', 'SID', $sess->id);

$darkrow = false;
while ($layout = $layouts->next()) {

    if($perm->have_perm_area_action_item("lay_edit","lay_edit",$layout->get("idlay"))){      //idlay of area lay is 8

        $name  = $layout->get('name');
        $descr = $layout->get('description');
        $idlay = $layout->get('idlay');

        if (strlen($descr)  > 64) {
            $descr = substr($descr, 0, 64);
            $descr .= ' ..';
        }

        if ($perm->have_perm_area_action_item("lay_edit","lay_edit",$layout->get("idlay"))) {
        	
        	$tmp_mstr = '<a href="javascript:conMultiLink(\'%s\', \'%s\', \'%s\', \'%s\')" title="%s" alt="%s">%s</a>';
        	$area = "lay";
        	$mstr = sprintf($tmp_mstr, 'right_top',
                                       $sess->url("main.php?area=$area&frame=3&idlay=$idlay"),
                                       'right_bottom',
                                       $sess->url("main.php?area=lay_edit&frame=4&idlay=$idlay"),
                                       $descr, $descr, $name);

            $tpl->set('d', 'NAME',  $mstr);
        } else {
        	//$tpl->set('d', 'IMGLINK', '<img src="images/layout.gif" width="16" height="16">');
            $tpl->set('d', 'NAME',  $name);
        }
        $inUse = $classlayout->layoutInUse($layout->get("idlay"));

        if ($darkrow)
        {
            $bgColor = $cfg["color"]["table_dark"];
        } else {
            $bgColor = $cfg["color"]["table_light"];
        }

        $darkrow = !$darkrow;
        $tpl->set('d', 'BGCOLOR', $bgColor);

        if ((!$perm->have_perm_area_action_item("lay","lay_delete",$layout->get("idlay"))) && (!$perm->have_perm_area_action("lay","lay_delete")))
        {
            $delDescription = i18n("No permission");
        }

        if ($inUse)
        {
        	$delDescription = i18n("Layout is in use, cannot delete");
        	$inUseDescription = i18n("Layout is in use");
            $tpl->set('d', 'INUSE','<img src="'.$cfg['path']['images'].'exclamation.gif" border="0" title="'.$inUseDescription.'" alt="'.$inUseDescription.'">');
        } else {
            $tpl->set('d', 'INUSE','');    
        }
        
        if ($perm->have_perm_area_action_item("lay","lay_delete",$layout->get("idlay")) &&
            !$inUse)
            {
            	$delTitle = i18n("Delete layout");
            	$delDescr = sprintf(i18n("Do you really want to delete the following layout:<br><br>%s<br>"),htmlspecialchars($name));
            	
            	
                $tpl->set('d', 'DELETE', '<a title="'.$delTitle.'" href="javascript://" onclick="box.confirm(\''.$delTitle.'\', \''.$delDescr.'\', \'deleteLayout('.$idlay.')\')"><img src="'.$cfg['path']['images'].'delete.gif" border="0" title="'.$delTitle.'" alt="'.$delTitle.'"></a>');
        } else {
            $tpl->set('d', 'DELETE','<img src="'.$cfg['path']['images'].'delete_inact.gif" border="0" title="'.$delDescription.'" alt="'.$delDescription.'">');
        }
        
        $todo = new TODOLink("idlay",$layout->get("idlay"), i18n("Layout").": ".$name,"");
        
        $tpl->set('d', 'TODO', $todo->render());
        
        if (stripslashes($_REQUEST['idlay']) == $layout->get("idlay")) {
            $tpl->set('d', 'ID', 'marked');
        } else {
            $tpl->set('d', 'ID', '');
        }

        $tpl->next();

    }
}

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['lay_overview']);
?>