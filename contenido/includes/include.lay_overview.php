<?php

/******************************************
* File      :   include.lay_overview.php
* Project   :   Contenido
* Descr     :   Listet die layouts auf
*
* Author    :   Olaf Niemann
* Created   :   27.03.2003
* Modified  :   27.03.2003
*
* © four for business AG
******************************************/

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
        	//$mstr2 = sprintf($tmp_mstr, 'right_top',
            //                           $sess->url("main.php?area=$area&frame=3&idlay=$idlay"),
            //                           'right_bottom',
            //                           $sess->url("main.php?area=lay_edit&frame=4&idlay=$idlay"),
            //                           $descr, $descr, '<img src="images/layout.gif" width="16" height="16">');                                       
        	
        	//$tpl->set('d', 'IMGLINK', $mstr2);
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
        
        $tpl->set('d', 'ID', 'lay'.$tpl->dyn_cnt);

        $tpl->next();

    }
}
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['lay_overview']);





?>
