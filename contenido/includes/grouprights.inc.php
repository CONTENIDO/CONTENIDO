<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido Group Rights
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.0
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-26, Dominik Ziegler, add security fix
 * 	 modified 2008-07-28, Bilal Arslan, moved inline html to template
 *
 *   $Id: grouprights.inc.php 719 2008-08-22 09:58:44Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

if(!is_object($db2))
$db2 = new DB_Contenido;

if(!is_object($oTpl))
$oTpl = new Template();
$oTpl->reset();

//set new right_list (=all possible rights)
if(!is_array($right_list)){
         //register these list fore following sites
         # same shit like every rights area ;)
         # commented out by H. Librenz, 2007-08-31
         //$sess->register("right_list");

         $plugxml=new XML_Doc();

         //select all rights , actions an theeir locations   without area login
        $sql="SELECT A.idarea, A.parent_id, B.location,A.name FROM ".$cfg["tab"]["area"]." as A LEFT JOIN ".$cfg["tab"]["nav_sub"]." as B ON  A.idarea = B.idarea WHERE A.name!='login' AND A.relevant='1' AND A.online='1' GROUP BY A.name ORDER BY A.idarea";
         $db->query($sql);

         while($db->next_record())
        {
                if($db->f("parent_id")=="0"){
                             $right_list[$db->f("name")][$db->f("name")]["perm"]=$db->f("name");
                             $right_list[$db->f("name")][$db->f("name")]["location"]=$db->f('location');
                }else{
                             $right_list[$db->f("parent_id")][$db->f("name")]["perm"]=$db->f("name");
                             $right_list[$db->f("parent_id")][$db->f("name")]["location"] = $db->f('location');
                }

                $sql="SELECT * FROM ".$cfg["tab"]["actions"]." WHERE idarea='".Contenido_Security::toInteger($db->f("idarea"))."' AND relevant='1'";
                $db2->query($sql);
                while($db2->next_record())
                {
                      if($db->f("parent_id")=="0"){
                              $right_list[$db->f("name")][$db->f("name")]["action"][]=$db2->f("name");
                      }else{
                              $right_list[$db->f("parent_id")][$db->f("name")]["action"][]=$db2->f("name");
                      }
                }
         }
}

##Header Area Begin
// Set the session id 
$oTpl->set("s", "SESS_ID", $sess->id);

##End of Header Area

if(!isset($actionarea)){
    $actionarea="area";
}
##Body Area Begin
$oTpl->set("s", "ACTION_URL", $sess->url("main.php"));
$oTpl->set("s", "TYPE_ID", "groupid");
$oTpl->set("s", "USER_ID", $groupid);
$oTpl->set("s", "AREA", $area);

$mgroup = new Group;
$mgroup->loadGroupByGroupID($groupid);

$userperms = $mgroup->getField("perms");

$oTpl->set("s", "TABLE_BORDER",$cfg["color"]["table_border"]);
$oTpl->set("s", "TABLE_BGCOLOR", $cfg["color"]["table_dark"]);
$oTpl->set("s", "RIGHTS_PERMS", $rights_perms);

//selectbox for clients
$oHtmlSelect = new 	cHTMLSelectElement ('rights_clientslang', "", "rights_clientslang");

	$clientclass = new Client;
   	$clientList = $clientclass->getAccessibleClients();
  	$firstsel = false;
  	$i = 0;
  	
   	foreach ($clientList as $key=>$value) {
   		$sql="SELECT * FROM ".$cfg["tab"]["lang"]." as A, ".$cfg["tab"]["clients_lang"]." as B WHERE B.idclient='".Contenido_Security::toInteger($key)."' AND A.idlang=B.idlang";
		$db->query($sql);

		while($db->next_record())
		{
    		if((strpos($userperms, "client[$key]") !== false) && 
    		   (strpos($userperms, "lang[".$db->f("idlang")."]") !== false)
    		   && ($perm->have_perm("lang[".$db->f("idlang")."]"))){
    		   	
    		   	if ($firstsel == false)
    		   	{
    		   		$firstsel = true;
    		   		$firstclientslang = $db->f("idclientslang");
    		   	}
    		   	
		       if ($rights_clientslang == $db->f("idclientslang")) {
                   # printf("<option value=\"%s\" selected>%s</option>",$db->f("idclientslang"),$value["name"] . " -> ".$db->f("name"));
                    $oHtmlSelectOption = new cHTMLOptionElement($value["name"] . " -> ".$db->f("name"), $db->f("idclientslang"), true);
                    $oHtmlSelect->addOptionElement($i, $oHtmlSelectOption);
					$i++; 
                       
                       
                   if(!isset($rights_client))
                   {
                   	$firstclientslang = $db->f("idclientslang");
                   }
               } else {
                    #printf("<option value=\"%s\">%s</option>",$db->f("idclientslang"),$value["name"] . " -> ".$db->f("name"));
			        $oHtmlSelectOption = new cHTMLOptionElement($value["name"] . " -> ".$db->f("name"), $db->f("idclientslang"), false);
                    $oHtmlSelect->addOptionElement($i, $oHtmlSelectOption);
					$i++;  
               }
    		}
		}
    }
// Render Select Box
$oTpl->set('s', 'INPUT_SELECT_CLIENT', $oHtmlSelect->render());

      if ($area != 'groups_content') {
        	$oTpl->set('s', 'INPUT_SELECT_RIGHTS', '');
			$oTpl->set('s', 'DISPLAY_RIGHTS', 'none');
      } else {
    
       #filter for displaying rights
        $oHtmlSelect = new 	cHTMLSelectElement ('filter_rights', '', "filter_rights");  
        $oHtmlSelectOption = new cHTMLOptionElement('--- '.i18n("All").' ---', '', false);
        $oHtmlSelect->addOptionElement(0, $oHtmlSelectOption);
        $oHtmlSelectOption = new cHTMLOptionElement(i18n("Article rights"), 'article', false);
        $oHtmlSelect->addOptionElement(1, $oHtmlSelectOption);
        $oHtmlSelectOption = new cHTMLOptionElement(i18n("Category rights"), 'category', false);
        $oHtmlSelect->addOptionElement(2, $oHtmlSelectOption);
        $oHtmlSelectOption = new cHTMLOptionElement(i18n("Template rights"), 'template', false);
        $oHtmlSelect->addOptionElement(3, $oHtmlSelectOption);
        $oHtmlSelectOption = new cHTMLOptionElement(i18n("Plugin/Other rights"), 'other', false);
        $oHtmlSelect->addOptionElement(4, $oHtmlSelectOption);
        $oHtmlSelect->setEvent('change', "document.rightsform.submit();");
        $oHtmlSelect->setDefault($_POST['filter_rights']);
		
        #set global array which defines rights to display
        $aArticleRights = array('con_syncarticle', 'con_lock', 'con_deleteart', 'con_makeonline', 'con_makestart', 'con_duplicate', 'con_editart', 'con_newart', 'con_edit');
        $aCategoryRights = array('con_synccat', 'con_makecatonline', 'con_makepublic');
        $aTempalteRights = array('con_changetemplate', 'con_tplcfg_edit');

        $aViewRights = array();
        $bExclusive = false;
        if (isset($_POST['filter_rights'])) {
            switch($_POST['filter_rights']) {
                case 'article':
                    $aViewRights = $aArticleRights;
                    break;
                case 'category':
                    $aViewRights = $aCategoryRights;
                    break;
                case 'template':
                    $aViewRights = $aTempalteRights;
                    break;
                case 'other':
                    $aViewRights = array_merge($aArticleRights, $aCategoryRights, $aTempalteRights);
                    $bExclusive = true;
                    break;
                default:
                    break;
            }
        }
        $oTpl->set('s', 'INPUT_SELECT_RIGHTS', $oHtmlSelect->render());
		$oTpl->set('s', 'DISPLAY_RIGHTS', 'block');
        
    }


if(!isset($rights_clientslang))
{
	$rights_clientslang = $firstclientslang;
}

$sql = "SELECT idclient, idlang FROM ".$cfg["tab"]["clients_lang"]." WHERE idclientslang = '$rights_clientslang'";
$db->query($sql);

if ($db->next_record())
{
	$rights_client = $db->f("idclient");
	$rights_lang = $db->f("idlang");
} else {
	$notification->displayNotification("error", i18n("Current group doesn't have any rights to any client/language."));
	die;
}

  // current set it on null 
   $oTpl->set('s', 'NOTIFICATION', '');

   $oTpl->set('s', 'OB_CONTENT', '');

function saverightsarea()
{
         global $db, $cfg,$groupid,$rights_client,$rights_lang,$rights_admin,$rights_sysadmin,$rights_perms,$rights_list;

         if(!isset($rights_perms)){
             //search for the permissions of this user
             $sql="SELECT perms FROM ".$cfg["tab"]["groups"]." WHERE group_id='".Contenido_Security::escapeDB($groupid, $db)."'";
             $db->query($sql);
             $db->next_record();
             $rights_perms=$db->f("perms");
         }


         //if there are no permissions,   delete permissions for lan and client
         if(!is_array($rights_list)){
            $rights_perms=preg_replace("/,+client\[$rights_client\]/","",$rights_perms);
            $rights_perms=preg_replace("/,+lang\[$rights_lang\]/","",$rights_perms);
         }else{
            if(!strstr($rights_perms,"client[$rights_client]"))
                 $rights_perms.=",client[$rights_client]";
            if(!strstr($rights_perms,"lang[$rights_lang]"))
                 $rights_perms.=",lang[$rights_lang]";
         }

         //if admin is checked
         if($rights_admin==1){
             //if admin is mot set
             if(!strstr($rights_perms,"admin[$rights_client]"))
                 $rights_perms.=",admin[$rights_client]";
         }else{
             //cut admin from the string
             $rights_perms=preg_replace("/,*admin\[$rights_client\]/","",$rights_perms);
         }

         //if sysadmin is checked
         if($rights_sysadmin==1){
             //if sysadmin is not set
             if(!strstr($rights_perms,"sysadmin"))
                 $rights_perms.=",sysadmin";
         }else{
             //cat sysadmin from string
             $rights_perms=preg_replace("/,*sysadmin/","",$rights_perms);
         }


         //cut ',' in front of the string
         $rights_perms=preg_replace("/^,/","",$rights_perms);

         //update table
         $sql="UPDATE ".$cfg["tab"]["groups"]." SET perms='".Contenido_Security::escapeDB($rights_perms, $db)."' WHERE group_id='".Contenido_Security::escapeDB($groupid, $db)."'";
                
         $db->query($sql);
         
         //save the other rights
         saverights();
}

function saverights() {

   global $rights_list, $rights_list_old, $db;
   global $cfg, $groupid, $rights_client, $rights_lang;
   global $perm, $sess, $notification;

   //if no checkbox is checked
   if (!is_array($rights_list)) {
      $rights_list = array ();
   }

   //search all checks which are not in the new Rights_list for deleting
   $arraydel = array_diff(array_keys($rights_list_old), array_keys($rights_list));
   //search all checks which are not in the Rights_list_old for saving
   $arraysave = array_diff(array_keys($rights_list), array_keys($rights_list_old));

   if (is_array($arraydel)) {
      foreach ($arraydel as $value) {
         $data = explode("|", $value);
         $data[0] = $perm->getIDForArea($data[0]);
         $data[1] = $perm->getIDForAction($data[1]);

         $sql = "DELETE FROM ".$cfg["tab"]["rights"]." WHERE user_id='".Contenido_Security::escapeDB($groupid, $db)."' AND idclient='".Contenido_Security::toInteger($rights_client)."' AND idlang='".Contenido_Security::toInteger($rights_lang)."' AND idarea='".Contenido_Security::toInteger($data[0])."' AND idcat='".Contenido_Security::toInteger($data[2])."' AND idaction='".Contenido_Security::toInteger($data[1])."' AND type=1";
         $db->query($sql);
      }
   }

   unset($data);

   //search for all mentioned checkboxes
   if (is_array($arraysave)) {
      foreach ($arraysave as $value) {
         //explodes the key     it consits    areait+actionid+itemid
         $data = explode("|", $value);

         // Since areas are stored in a numeric form in the rights table, we have
         // to convert them from strings into numbers

         $data[0] = $perm->getIDForArea($data[0]);
         $data[1] = $perm->getIDForAction($data[1]);

         if (!isset ($data[1])) {
            $data[1] = 0;
         }
         // Insert new right
         $sql = "INSERT INTO ".$cfg["tab"]["rights"]."
                  (idright, user_id,idarea,idaction,idcat,idclient,idlang, type)
                  VALUES ('".Contenido_Security::toInteger($db->nextid($cfg["tab"]["rights"]))."', '".Contenido_Security::escapeDB($groupid, $db)."', '".Contenido_Security::toInteger($data[0])."','".Contenido_Security::toInteger($data[1])."', '".Contenido_Security::toInteger($data[2])."', '".Contenido_Security::toInteger($rights_client)."', '".Contenido_Security::toInteger($rights_lang)."', 1)";
         $db->query($sql);
      }

   }
   $rights_list_old = $rights_list;

   $notification->displayNotification("info", i18n("Changes saved"));
}
?>