<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido Rights
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
 *   modified 2008-06-27, Dominik Ziegler, add security fix
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

if ( $_REQUEST['cfg'] ) { 
	die('Illegal call');
}

if(!is_object($db2))
$db2 = new DB_Contenido;

global $belang;

$right_list=array();
        # Load language file
        if ($xml->load($cfg['path']['xml'] . $cfg['lang'][$belang]) == false)
        {
        	if ($xml->load($cfg['path']['xml'] . 'lang_en_US.xml') == false)
        	{
        		die("Unable to load any XML language file");
        	}
        }
        
        
if(!isset($rights_client)){
      $rights_client=$client;
      $rights_lang=$lang;
}

if($action==10){
            saverights();

}

echo"<FORM name=\"rightsform\" method=post action=\"".$sess->url("main.php")."\" >";
echo"<input type=\"hidden\" name=\"action\" value=\"\">";
echo"<input type=\"hidden\" name=\"frame\" value=\"4\">";
echo"<input type=\"hidden\" name=\"area\" value=\"user_overview\">";
echo"<input type=\"hidden\" name=\"idlang\" value=\"".$lang."\">";
echo"<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
echo"<tr>";
echo"<td>";

echo "<SELECT name=\"rights_user\" SIZE=1 onChange=\"rightsform.submit()\">";
$sql="SELECT * FROM ".$cfg["tab"]["phplib_auth_user_md5"];
$db->query($sql);
while($db->next_record())
{
       if(!isset($rights_user)){
                 $rights_user=$db->f("id");
       }
       if ($rights_user == $db->f("id")) {
                       $rights_perms=$db->f("perms");
                       printf("<option value=\"%s\" selected>%s</option>",
                         $db->f("id"),
                         $db->f("username")
                       );
               } else {
                       printf("<option value=\"%s\">%s</option>",
                         $db->f("id"),
                         $db->f("username")
                       );
               }
}
echo "</SELECT>";
echo "</td><td>";

echo"<input type=\"hidden\" name=\"rights_perms\" value=\"$rights_perms\">";

echo "<SELECT name=\"rights_client\" SIZE=1 onChange=\"rightsform.submit()\">";
$sql="SELECT * FROM ".$cfg["tab"]["clients"];
$db->query($sql);
while($db->next_record())
{
       if ($rights_client == $db->f("idclient")) {
                       printf("<option value=\"%s\" selected>%s</option>",
                         $db->f("idclient"),
                         $db->f("name")
                       );
               } else {
                       printf("<option value=\"%s\">%s</option>",
                         $db->f("idclient"),
                         $db->f("name")
                       );
               }
}
echo "</SELECT>";

echo "</td><td>";

echo "<SELECT name=\"rights_lang\" SIZE=1 onChange=\"rightsform.submit()\">";
$sql="SELECT * FROM ".$cfg["tab"]["lang"]." as A, ".$cfg["tab"]["clients_lang"]." as B WHERE B.idclient='".Contenido_Security::toInteger($rights_client)."' AND A.idlang=B.idlang";
$db->query($sql);
while($db->next_record())
{
       if ($rights_lang == $db->f("idlang")) {
                       printf("<option value=\"%s\" selected>%s</option>",
                         $db->f("idlang"),
                         $db->f("name")
                       );
               } else {
                       printf("<option value=\"%s\">%s</option>",
                         $db->f("idlang"),
                         $db->f("name")
                       );
               }
}
echo "</SELECT>";

echo "</td>";

echo"</tr><tr>";
echo"<td colspan=\"2\" align=\"left\">";


$sql="SELECT A.idarea, A.parent_id, B.location,A.name FROM ".$cfg["tab"]["area"]." as A LEFT JOIN ".$cfg["tab"]["nav_sub"]." as B ON  A.idarea = B.idarea ORDER BY B.idnavs";
$db->query($sql);
while($db->next_record())
{
       $sql="SELECT * FROM ".$cfg["tab"]["actions"]." WHERE idarea='".$db->f("idarea")."'";
       $db2->query($sql);
       while($db2->next_record())
       {
             if($db->f("parent_id")==0){
                     $right_list[$db->f("id")][$db->f("id")]["action"][]=$db2->f("name");
                     if(!isset($right_list[$db->f("id")][$db->f("id")]["perm"])){
                         $right_list[$db->f("id")][$db->f("id")]["perm"]=$db->f("name");
                         $right_list[$db->f("id")][$db->f("id")]["location"]=$db->f("location");
                     }
             }else{
                     if(!isset($right_list[$db->f("parent_id")][$db->f("id")]["perm"])){
                         $right_list[$db->f("parent_id")][$db->f("id")]["perm"]=$db->f("name");
                         $right_list[$db->f("parent_id")][$db->f("id")]["action"][]=$db2->f("name");
                     }
             }
       }
}

if(!isset($rights_list)||$action==""||!isset($action)){
      $sql="SELECT * FROM ".$cfg["tab"]["rights"]." WHERE user_id='".Contenido_Security::escapeDB($rights_user, $db)."' AND idcat='0' AND idclient='".Contenido_Security::toInteger($client)."' AND idlang='".Contenido_Security::toInteger($lang)."' AND type=0";
      $db->query($sql);
      $rights_list=array();
      while($db->next_record()){
            $rights_list[]=$db->f("idarea")."[".$db->f("idaction")."]";
      }
}

echo "<SELECT name=\"rights_list[]\" SIZE=8 multiple>";

foreach($right_list as $firstid => $value)
{
       if(in_array($value[$firstid]["perm"]."[0]",$rights_list)){
               printf("<option value=\"%s\" selected=\"selected\">%s</option>",
                                        $value[$firstid]["perm"]."[0]",
                                        $xml->valueOf($value[$firstid]["location"])
                                        );
       }else{
               printf("<option value=\"%s\">%s</option>",
                                        $value[$firstid]["perm"]."[0]",
                                        $xml->valueOf($value[$firstid]["location"])
                                        );
       }

       foreach($value as $secondid => $value2)
       {
               foreach($value2["action"] as $key3 => $value3)
               {
                        if(in_array($value2["perm"]."[$value3]",$rights_list)){
                                printf("<option value=\"%s\"  selected=\"selected\">%s</option>",
                                        $value2["perm"]."[$value3]",
                                        "---".$value3
                                        );
                        }else{
                                printf("<option value=\"%s\">%s</option>",
                                        $value2["perm"]."[$value3]",
                                        "---".$value3
                                        );
                        }
               }
       }
}
echo "</SELECT>";

echo"</td><td>";

if(!strstr($rights_perms,"admin[$rights_client]"))
   $checked="";
else
   $checked="checked=\"checked\"";

echo"<input type=\"checkbox\" name=\"rights_admin\" value=\"1\" $checked>Admin (client)<br>";

if(!strstr($rights_perms,"sysadmin"))
   $checked="";
else
   $checked="checked=\"checked\"";

echo"<input type=\"checkbox\" name=\"rights_sysadmin\" value=\"1\" $checked>Systemadmin (all)<br>";

echo"<a href=\"javascript:submitrightsform(10)\" class=\"action\">Speichern</a>";
echo"</td></tr></table>";

echo"</form>"
?>