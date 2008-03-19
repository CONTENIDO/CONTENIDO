<?php

/******************************************
* File      :   include.tpl_overview.php
* Project   :   Contenido 
* Descr     :   Shows all templates in the
*               left frame
*
* Author    :   Jan Lengowski
* Created   :   27.03.2003
* Modified  :   11.05.2003
*
* © four for business AG
******************************************/

$sql = "SELECT
            *
        FROM
            ".$cfg["tab"]["tpl"]."
        WHERE
            idclient = '".$client."'
        ORDER BY
            name";

$db->query($sql);
$tpl->reset();

$tpl->set('s', 'SID', $sess->id);

while ( $db->next_record() ) {

    if ( $perm->have_perm_item($area, $db->f("idtpl")) ||
         $perm->have_perm_area_action("tpl" , "tpl_delete") ||
         $perm->have_perm_area_action("tpl" , "tpl_duplicate") ||
         $perm->have_perm_area_action("tpl_edit" , "tpl_edit") ||
         $perm->have_perm_area_action("tpl_edit" , "tpl_new") ||
         $perm->have_perm_area_action("tpl_visual" , "tpl_visedit")
     ) {
        $name  = $db->f('name');
        $descr = $db->f('description');
        $idtpl = $db->f("idtpl");

        $bgcolor = ( is_int($tpl->dyn_cnt / 2) ) ? $cfg["color"]["table_light"] : $cfg["color"]["table_dark"];
        $tpl->set('d', 'BGCOLOR', $bgcolor);

        # create javascript multilink
        $tmp_mstr = '<a title="%s" href="javascript:conMultiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>';

		if ($db->f("defaulttemplate") == 1)
		{
            $mstr = sprintf($tmp_mstr, htmlspecialchars($descr), 'right_top',
                                       $sess->url("main.php?area=tpl&frame=3&idtpl=$idtpl"),
                                       'right_bottom',
                                       $sess->url("main.php?area=tpl_edit&frame=4&idtpl=$idtpl"),
                                       "<b>".$name."</b>");
		} else {
			$mstr = sprintf($tmp_mstr, htmlspecialchars($descr), 'right_top',
                                       $sess->url("main.php?area=tpl&frame=3&idtpl=$idtpl"),
                                       'right_bottom',
                                       $sess->url("main.php?area=tpl_edit&frame=4&idtpl=$idtpl"),
                                       $name);
			
		}
		
		//$mstr2 = sprintf($tmp_mstr, htmlspecialchars($descr), 'right_top',
        //                               $sess->url("main.php?area=tpl&frame=3&idtpl=$idtpl"),
        //                               'right_bottom',
        //                               $sess->url("main.php?area=tpl_edit&frame=4&idtpl=$idtpl"),
        //                               '<img src="images/template.gif" width="16" height="16">'); 


        if ($perm->have_perm_area_action_item("tpl_edit","tpl_edit",$db->f("idtpl"))) {
        //	$tpl->set('d', 'IMGLINK', $mstr2);
            $tpl->set('d', 'NAME',  $mstr);
        } else {
        //	$tpl->set('d', 'IMGLINK', '<img src="images/template.gif" width="16" height="16">');
            $tpl->set('d', 'NAME', $name);
        }

             /* Check if template is in use */
            $inUse = tplIsTemplateInUse($idtpl);

            if (!$inUse && ($perm->have_perm_area_action_item("tpl","tpl_delete",$db->f("idtpl")))) {
            	$delTitle = i18n("Delete template");
        		$delDescr = sprintf(i18n("Do you really want to delete the following template:<br><br>%s<br>"),htmlspecialchars($name));
            
                $tpl->set('d', 'DELETE', '<a title="'.$delTitle.'" href="javascript://" onclick="box.confirm(\''.$delTitle.'\', \''.$delDescr.'\', \'deleteTemplate('.$idtpl.')\')"><img src="'.$cfg['path']['images'].'delete.gif" border="0" title="'.$delTitle.'" alt="'.$delTitle.'"></a>');
                
            } else {
                //$inUseTitle = i18n('Template in use, cannot delete');
                //$tpl->set('d', 'DELETE','<img src="'.$cfg['path']['images'].'delete_inact.gif" alt="'.$inUseTitle.'" title="'.$inUseTitle.'">');
                $tpl->set('d', 'DELETE', '<img src="images/spacer.gif" width="16">');
            }

           if ($perm->have_perm_area_action_item("tpl","tpl_dup", $db->f("idtpl"))) {
                $copybutton = '<a target="right_bottom" href="'.$sess->url("main.php?area=tpl_edit&action=tpl_duplicate&idtpl=$idtpl&frame=4").'" title="'.i18n("Duplicate template").'"><img src="'.$cfg["path"]["images"].'but_copy.gif'.'" border="0" title="'.i18n("Duplicate template").'" alt="'.i18n("Duplicate template").'"></a>';
                        
           } else {
               $copybutton = '<img src="images/spacer.gif" width="14" height="1">';
           }

           $tpl->set('d', 'COPY', $copybutton);
           $tpl->set('d', 'ID', 'tpl'.$tpl->dyn_cnt);

        $tpl->next();
    }
}

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['tpl_overview']);

?>
