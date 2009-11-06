<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido Groups Overview PAge
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.1.1
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
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

cInclude('includes', 'functions.rights.php');

$db2 = new DB_Contenido;

if(!$perm->have_perm_area_action($area,$action))
{
  $notification->displayNotification("error", i18n("Permission denied"));
} else {

if ( !isset($groupid) )
{

} else {

    if (($action == "group_edit") && ($perm->have_perm_area_action($area, $action)))
    {
            $stringy_perms = array();
            if ($msysadmin)
            {
                array_push($stringy_perms, "sysadmin");
            }

            if (is_array($madmin)) {
                foreach ($madmin as $value) {
                    array_push($stringy_perms, "admin[$value]");
                }
            }

            if (is_array($mclient)) {
                foreach ($mclient as $value) {
                    array_push($stringy_perms, "client[$value]");
                }
            }

            //Fixed CON-200
            if (!is_array($mclient)) {
                $mclient = array();
            }
            
            if (is_array($mlang)) {
                foreach ($mlang as $value) {
                    //Fixed CON-200
                    if (checkLangInClients($mclient, $value, $cfg, $db)) {
                        array_push($stringy_perms, "lang[$value]");
                    }
                }
            }

                           $sql = 'UPDATE
                         '.$cfg["tab"]["groups"].'
                        SET
                          description="'.Contenido_Security::escapeDB($description, $db).'",
                          perms="'.Contenido_Security::escapeDB(implode(",",$stringy_perms), $db).'" 
                        WHERE
                          group_id = "'.Contenido_Security::escapeDB($groupid, $db).'"';

                $db->query($sql);

                $notification->displayNotification("info", i18n("Changes saved"));

        
 }    

    $tpl->reset();    
    
    $sql = "SELECT
                groupname, description, perms
            FROM
                ".$cfg["tab"]["groups"]."
            WHERE
                group_id = '".Contenido_Security::escapeDB($groupid, $db)."'";

    $db->query($sql);
    $db->next_record();

    $group_perms = array();
    $group_perms = explode(",", $db->f("perms"));

    $db2 = new DB_Contenido;


    $form = '<form name="group_properties" method="post" action="'.$sess->url("main.php?").'">
                 '.$sess->hidden_session().'
                 <input type="hidden" name="area" value="'.$area.'">
                 <input type="hidden" name="action" value="group_edit">
                 <input type="hidden" name="frame" value="'.$frame.'">
				 <input type="hidden" name="groupid" value="'.$groupid.'">
                 <input type="hidden" name="idlang" value="'.$lang.'">';
   
    $tpl->set('s', 'FORM', $form);
    $tpl->set('s', 'GET_GROUPID', $groupid);
    
    $tpl->set('s', 'BORDERCOLOR', $cfg["color"]["table_border"]);
    $tpl->set('s', 'BGCOLOR', $cfg["color"]["table_dark"]);
    $tpl->set('s', 'SUBMITTEXT', i18n("Save changes"));
    $tpl->set('s', 'CANCELTEXT', i18n("Discard changes"));
    $tpl->set('s', 'CANCELLINK', $sess->url("main.php?area=$area&frame=4&groupid=$groupid"));
    
    if ($error)
    {
        echo $error;
    }

    $tpl->set('d', 'CATNAME', i18n("Property"));
    $tpl->set('d', 'CLASS', 'text_medium');
    $tpl->set('d', 'BGCOLOR',  $cfg["color"]["table_header"]);
    $tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
    $tpl->set('d', 'CATFIELD', i18n("Value"));
	$tpl->set('d', 'BRDB', 0);
	$tpl->set('d', 'BRDT', 1);
    $tpl->next();

    $tpl->set('d', 'CATNAME', i18n("Groupname"));
    $tpl->set('d', 'CLASS', 'text_medium');
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
    $tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]); 
    $tpl->set('d', 'CATFIELD', substr($db->f("groupname"),4));
	$tpl->set('d', 'BRDB', 1);
	$tpl->set('d', 'BRDT', 0);
    $tpl->next();
    
    $tpl->set('d', 'CATNAME', i18n("Description"));
    $tpl->set('d', 'CLASS', 'text_medium');
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
    $tpl->set('d', "BORDERCOLOR", $cfg["color"]["table_border"]);
    $tpl->set('d', 'CATFIELD', formGenerateField ("text", "description", $db->f("description"), 40, 255));
	$tpl->set('d', 'BRDB', 1);
	$tpl->set('d', 'BRDT', 0);
    $tpl->next();
  
    $groupperm = explode(',', $auth->auth['perm']);

    if(in_array("sysadmin",$groupperm)){
        $tpl->set('d', 'CLASS', 'text_medium');
        $tpl->set('d', 'CATNAME', i18n("System administrator"));
        $tpl->set('d', "BORDERCOLOR", $cfg["color"]["table_border"]);
        $tpl->set('d', "BGCOLOR", $cfg["color"]["table_light"]);
        $tpl->set('d', "CATFIELD", formGenerateCheckbox("msysadmin","1", in_array("sysadmin", $group_perms)));
				$tpl->set('d', 'BRDB', 1);
				$tpl->set('d', 'BRDT', 0);
        $tpl->next();
    }

        $sql="SELECT * FROM ".$cfg["tab"]["clients"];
        $db2->query($sql);
        $client_list = "";
        $gen = 0;
        while($db2->next_record())
        {
            if(in_array("admin[".$db2->f("idclient")."]",$groupperm) || in_array("sysadmin",$groupperm)){
                $client_list .= formGenerateCheckbox("madmin[".$db2->f("idclient")."]",$db2->f("idclient"),in_array("admin[".$db2->f("idclient")."]",$group_perms), $db2->f("name")." (".$db2->f("idclient").")")."<br>";
                $gen = 1;
            }
       }

        if ($gen == 1 && !in_array("sysadmin",$group_perms))
        {
            $tpl->set('d', 'CLASS', 'text_medium');
            $tpl->set('d', 'CATNAME', i18n("Administrator"));
            $tpl->set('d', 'BORDERCOLOR',  $cfg["color"]["table_border"]);
            $tpl->set('d', "BGCOLOR", $cfg["color"]["table_dark"]);
            $tpl->set('d', "CATFIELD", $client_list);
			$tpl->set('d', 'BRDB', 1);
			$tpl->set('d', 'BRDT', 0);
            $tpl->next(); 
        }

    $sql = "SELECT * FROM " .$cfg["tab"]["clients"];
    $db2->query($sql);
    $client_list = "";  

    while ($db2->next_record())
    {
            if((in_array("client[".$db2->f("idclient")."]",$groupperm) || in_array("sysadmin",$groupperm) || in_array("admin[".$db2->f("idclient")."]",$groupperm)) && !in_array("admin[".$db2->f("idclient")."]",$group_perms)) {
                $client_list .= formGenerateCheckbox("mclient[".$db2->f("idclient")."]",$db2->f("idclient"),in_array("client[".$db2->f("idclient")."]",$group_perms), $db2->f("name")." (". $db2->f("idclient") . ")")."<br>";
            }
    }
    
    if ($client_list != "" && !in_array("sysadmin",$group_perms))
    {
        $tpl->set('d', 'CLASS', 'text_medium');
        $tpl->set('d', 'CATNAME', i18n("Access clients"));
        $tpl->set('d', 'BORDERCOLOR',  $cfg["color"]["table_border"]);
        $tpl->set('d', "BGCOLOR", $cfg["color"]["table_light"]);
        $tpl->set('d', "CATFIELD", $client_list);
				$tpl->set('d', 'BRDB', 1);
				$tpl->set('d', 'BRDT', 0);
        $tpl->next();
    }
    
    $sql = "SELECT
                a.idlang as idlang,
                a.name as name,
                b.name as clientname,
				b.idclient as idclient FROM
                " .$cfg["tab"]["lang"]." as a,
                " .$cfg["tab"]["clients_lang"]." as c,
                " .$cfg["tab"]["clients"]." as b
                WHERE
                    a.idlang = c.idlang AND
                    c.idclient = b.idclient";

    $db2->query($sql);
    $client_list = "";
    
    while ($db2->next_record())
    {
            if(($perm->have_perm_client("lang[".$db2->f("idlang")."]") || $perm->have_perm_client("admin[".$db2->f("idclient")."]")) && !in_array("admin[".$db2->f("idclient")."]",$group_perms))
            {
                $client_list .= formGenerateCheckbox("mlang[".$db2->f("idlang")."]",$db2->f("idlang"),in_array("lang[".$db2->f("idlang")."]",$group_perms), $db2->f("name")." (". $db2->f("clientname") .")")."<br>";
            }

    }
    
    if ($client_list != "" && !in_array("sysadmin",$group_perms))
    {
        
        $tpl->set('d', 'CLASS', 'text_medium');
        $tpl->set('d', 'CATNAME', i18n("Access languages"));
        $tpl->set('d', 'BORDERCOLOR',  $cfg["color"]["table_border"]);
        $tpl->set('d', "BGCOLOR", $cfg["color"]["table_dark"]);
        $tpl->set('d', "CATFIELD", $client_list);
				$tpl->set('d', 'BRDB', 1);
				$tpl->set('d', 'BRDT', 0);
        $tpl->next();
    }

	/* Generate group property table */
    $tempGroup = new Group();
    
    $tempGroup->loadGroupByGroupID($groupid);
    
    if (is_string($del_groupprop_type) && is_string($del_groupprop_name))
    {
    	$tempGroup->deleteGroupProperty($del_groupprop_type, $del_groupprop_name);
    }
    
    if (is_string($groupprop_type) && is_string($groupprop_name) && is_string($groupprop_value)
    	&& !empty($groupprop_type) && !empty($groupprop_name))
    {
    	$tempGroup->setGroupProperty($groupprop_type, $groupprop_name, $groupprop_value);
    }
    $properties = $tempGroup->getGroupProperties();
    
    if (is_array($properties))
    {
    	foreach ($properties as $prop)
    	{
    		$type = $prop["type"];
    		$name = $prop["name"];
    		$deleteButton = '<a href="'.$sess->url("main.php?area=$area&frame=4&groupid=$groupid&del_groupprop_type=$type&del_groupprop_name=$name").'"><img src="images/delete.gif" border="0" alt="Eigenschaft löschen" title="Eigenschaft löschen"></a>';
    		$value = $tempGroup->getGroupProperty($type,$name);
    		$propLines .= "<tr class=\"text_medium\"><td>$type</td><td>$name</td><td>$value</td><td>$deleteButton</tr>";
    	}
    }	
	$table = '<table width="100%" cellspacing="0" cellpadding="2" style="border: 1px; border-color:'.$cfg["color"]["table_border"].'; border-style: solid;">
                 <tr style="background-color:'.$cfg["color"]["table_header"].'" class="text_medium"><td>'.i18n("Area/Type").'</td><td>'.i18n("Property").'</td><td>'.i18n("Value").'</td><td>&nbsp;</td></tr>'. $propLines. 
			 '<tr class="text_medium"><td><input class="text_medium"  type="text" size="16" maxlen="32" name="groupprop_type"></td>
              <td><input class="text_medium" type="text" size="16" maxlen="32" name="groupprop_name"></td>
			  <td><input class="text_medium" type="text" size="32" name="groupprop_value"></td><td>&nbsp;</td></tr></table>';
	
	$groupProps = $table;
	
    $tpl->set('d', 'CLASS', 'text_medium');
    $tpl->set('d', 'CATNAME', i18n("User-defined properties"));
    $tpl->set('d', 'BORDERCOLOR',  $cfg["color"]["table_border"]);
    $tpl->set('d', "BGCOLOR", $cfg["color"]["table_light"]);
    $tpl->set('d', "CATFIELD", $groupProps);
    $tpl->next(); 
    
    # Generate template
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['grouprights_overview']);
}
}
?>