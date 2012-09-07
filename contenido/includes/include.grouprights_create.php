<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido Create Group Function
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.7.1
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
 *   $Id: include.grouprights_create.php 1094 2009-11-06 01:22:13Z xmurrix $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude('includes', 'functions.rights.php');

if(!$perm->have_perm_area_action($area, $action))
{
    $notification->displayNotification("error", i18n("Permission denied"));
} else {

    if ($action == "group_create")
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

			if ($groupname == "")
			{
				$groupname = "grp_".i18n("New Group");
			}
			
			if (substr($groupname,0,4) != "grp_")
			{
				$groupname = "grp_".$groupname;
			}
    	     $newgroupid = md5($groupname);
    	     $sql = 'INSERT INTO
                        '.$cfg["tab"]["groups"].'
                      SET
            		    groupname="'.Contenido_Security::escapeDB($groupname, $db).'",
                        perms="'.implode(",",$stringy_perms).'",
						description="'.Contenido_Security::escapeDB($description, $db).'",
    		            group_id="'.Contenido_Security::escapeDB($newgroupid, $db).'"';
                   
        $db->query($sql); 
      

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
                 <input type="hidden" name="action" value="group_create">
                 <input type="hidden" name="frame" value="'.$frame.'">
                 <input type="hidden" name="idlang" value="'.$lang.'">';

    $tpl->set('s', 'FORM', $form);
    $tpl->set('s', 'BORDERCOLOR', $cfg["color"]["table_border"]);
    $tpl->set('s', 'BGCOLOR', $cfg["color"]["table_dark"]);
    $tpl->set('s', 'SUBMITTEXT', i18n("Save changes"));
    if ($error)
    {
        echo $error;
    }

    $tpl->set('d', 'CATNAME', i18n("Property"));
    $tpl->set('d', 'BGCOLOR',  $cfg["color"]["table_header"]);
    $tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
    $tpl->set('d', 'CATFIELD', i18n("Value"));
    $tpl->next();

    $tpl->set('d', 'CATNAME', i18n("Group name"));
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
    $tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]); 
    $tpl->set('d', 'CATFIELD', formGenerateField ("text", "groupname", "", 40, 32));
    $tpl->next();
    
    $tpl->set('d', 'CATNAME', i18n("Description"));
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
    $tpl->set('d', "BORDERCOLOR", $cfg["color"]["table_border"]);
    $tpl->set('d', 'CATFIELD', formGenerateField ("text", "description", $db->f("description"), 40, 255));
    $tpl->next();
  
    $groupperm = explode(',', $auth->auth['perm']);

    if(in_array("sysadmin",$groupperm)){
        $tpl->set('d', 'CLASS', 'text_medium');
        $tpl->set('d', 'CATNAME', i18n("System administrator"));
        $tpl->set('d', "BORDERCOLOR", $cfg["color"]["table_border"]);
        $tpl->set('d', "BGCOLOR", $cfg["color"]["table_light"]);
        $tpl->set('d', "CATFIELD", formGenerateCheckbox("msysadmin","1", in_array("sysadmin", $group_perms)));
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

        if ($gen == 1)
        {
            $tpl->set('d', 'CLASS', 'text_medium');
            $tpl->set('d', 'CATNAME', i18n("Administrator"));
            $tpl->set('d', 'BORDERCOLOR',  $cfg["color"]["table_border"]);
            $tpl->set('d', "BGCOLOR", $cfg["color"]["table_dark"]);
            $tpl->set('d', "CATFIELD", $client_list);
            $tpl->next(); 
        }


    $sql = "SELECT * FROM " .$cfg["tab"]["clients"];
    $db2->query($sql);
    $client_list = "";
    
    while ($db2->next_record())
    {
            if(in_array("client[".$db2->f("idclient")."]",$groupperm) || in_array("sysadmin",$groupperm) || in_array("admin[".$db2->f("idclient")."]",$groupperm)) {
                $client_list .= formGenerateCheckbox("mclient[".$db2->f("idclient")."]",$db2->f("idclient"),in_array("client[".$db2->f("idclient")."]",$group_perms), $db2->f("name")." (". $db2->f("idclient") . ")")."<br>";
            }

    }
    $tpl->set('d', 'CLASS', 'text_medium');
    $tpl->set('d', 'CATNAME', i18n("Access clients"));
    $tpl->set('d', 'BORDERCOLOR',  $cfg["color"]["table_border"]);
    $tpl->set('d', "BGCOLOR", $cfg["color"]["table_light"]);
    $tpl->set('d', "CATFIELD", $client_list);
    $tpl->next();
    
    $sql = "SELECT
                a.idlang as idlang,
                a.name as name,
                b.name as clientname FROM
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

            if($perm->have_perm_client("lang[".$db2->f("idlang")."]") || $perm->have_perm_client("admin[".$db2->f("idclient")."]" ))
            {
                $client_list .= formGenerateCheckbox("mlang[".$db2->f("idlang")."]",$db2->f("idlang"),in_array("lang[".$db2->f("idlang")."]",$group_perms), $db2->f("name")." (". $db2->f("clientname") .")")."<br>";
            }

    }
    $tpl->set('d', 'CLASS', 'text_medium');
    $tpl->set('d', 'CATNAME', i18n("Access languages"));
    $tpl->set('d', 'BORDERCOLOR',  $cfg["color"]["table_border"]);
    $tpl->set('d', "BGCOLOR", $cfg["color"]["table_dark"]);
    $tpl->set('d', "CATFIELD", $client_list);
    $tpl->next();

    # Generate template
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['grouprights_create']);
}
?>