<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Defines the "rights" related functions
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.0
 * @author     Martin Horwath
 * @copyright  dayside.net
 * @link       http://www.dayside.net
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2004-11-25
 *   modified 2008-06-26, Frederic Schneider, add security fix
 *
 *   $Id: functions.rights.php 802 2008-09-09 15:54:38Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

/**
  * Function checks if a language is associated with a given list of clients Fixed CON-200
  * 
  * @param array $aClients - array of clients to check
  * @param integer $iLang - language id which should be checked
  * @param array $aCfg - Contenido configruation array
  * @param object $oDb - Contenido database object
  *
  * @return boolean - status (if language id corresponds to list of clients true otherwise false)
  */
function checkLangInClients($aClients, $iLang, $aCfg, $oDb) {
    //Escape values for use in DB
    $iIdClient = Contenido_Security::toInteger($iLang);  
    foreach ($aClients as $iKey => $iValue) {
        $aClients[$iKey] = Contenido_Security::toInteger($aClients[$iKey]);  
    }
    
    //Query to check, if langid is in list of clients associated
    $sSql = "SELECT * FROM ".$aCfg['tab']['clients_lang']. " WHERE idlang=".$iLang." AND idclient IN ('".implode("','",$aClients)."');";
    
    $oDb->query($sSql);
    if ($oDb->next_record()) {
        return true;
    } else {
        return false;
    }
}

/**
 * Duplicate rights for any element
 *
 * @param string $area main area name
 * @param int $iditem ID of element to copy
 * @param int $newiditem ID of the new element
 * @param int $idlang ID of lang parameter
 *
 * @author Martin Horwath <horwath@dayside.net>
 * @copyright dayside.net <dayside.net>
 */
function copyRightsForElement ($area, $iditem, $newiditem, $idlang=false) {

   global $cfg, $perm, $auth, $area_tree;

   $db = new DB_Contenido;
   $db2 = new DB_Contenido;

   // get all user_id values for con_rights

   $userIDContainer = $perm->getGroupsForUser($auth->auth["uid"]); // add groups if available
   $userIDContainer[] = $auth->auth["uid"]; // add user_id of current user
   
   foreach ($userIDContainer as $key) {
      $statement_where2[] = "user_id = '".Contenido_Security::escapeDB($key, $db)."' ";
   }

   $where_users =   "(".implode(" OR ", $statement_where2 ) .")"; // only duplicate on user and where user is member of

   // get all idarea values for $area
   // short way
   $AreaContainer = $area_tree[$perm->showareas($area)];

   // long version start
   // get all actions for corresponding area
   $AreaActionContainer = array();
   $sql = "SELECT
            idarea, idaction
         FROM
            ".$cfg["tab"]["actions"]."
         WHERE
            idarea IN (".implode (',', $AreaContainer).")";
   $db->query($sql);

   while ($db->next_record()) {
      $AreaActionContainer[] = Array ("idarea"=>$db->f("idarea"), "idaction"=>$db->f("idaction"));
   }

   // build sql statement for con_rights
   foreach ($AreaActionContainer as $key) {
      $statement_where[] = "( idarea = ".Contenido_Security::toInteger($key["idarea"])." AND idaction = ".Contenido_Security::toInteger($key["idaction"])." )";
   }

   $where_area_actions = "(".implode(" OR ", $statement_where ) .")"; // only correct area action pairs possible

   // final sql statement to get all effected elements in con_right
   $sql = "SELECT
            *
         FROM
            ".$cfg["tab"]["rights"]."
         WHERE
            {$where_area_actions} AND
            {$where_users} AND
            idcat = {$iditem}";
   // long version end

   if ($idlang) {
      $sql.= " AND idlang='$idlang'";
   }

   $db->query($sql);

   while ($db->next_record()) {
      $sql = "INSERT INTO ".$cfg["tab"]["rights"]." (idright,user_id,idarea,idaction,idcat,idclient,idlang,`type`) VALUES ('".Contenido_Security::toInteger($db2->nextid($cfg["tab"]["rights"]))."', 
              '".Contenido_Security::escapeDB($db->f("user_id"), $db)."', '".Contenido_Security::toInteger($db->f("idarea"))."', '".Contenido_Security::toInteger($db->f("idaction"))."',
              '".Contenido_Security::toInteger($newiditem)."','".Contenido_Security::toInteger($db->f("idclient"))."', '".Contenido_Security::toInteger($db->f("idlang"))."',
              '".Contenido_Security::toInteger($db->f("type"))."');";
      $db2->query($sql);
   }

   // permissions reloaded...
   $perm->load_permissions(true);

}


/**
 * Create rights for any element
 *
 * @param string $area main area name
 * @param int $iditem ID of new element
 * @param int $idlang ID of lang parameter
 *
 * @author Martin Horwath <horwath@dayside.net>
 * @copyright dayside.net <dayside.net>
 */
function createRightsForElement ($area, $iditem, $idlang=false) {

   global $cfg, $perm, $auth, $area_tree, $client;

   if (!is_object($perm))
   {	
   		return false;
   }
   
   if (!is_object($auth))
   {	
   		return false;
   }   
   
   $db = new DB_Contenido;
   $db2 = new DB_Contenido;

   // get all user_id values for con_rights

   $userIDContainer = $perm->getGroupsForUser($auth->auth["uid"]); // add groups if available
   $userIDContainer[] = $auth->auth["uid"]; // add user_id of current user

   foreach ($userIDContainer as $key) {
      $statement_where2[] = "user_id = '".Contenido_Security::toInteger($key)."' ";
   }

   $where_users =   "(".implode(" OR ", $statement_where2 ) .")"; // only duplicate on user and where user is member of

   // get all idarea values for $area
   // short way
   $AreaContainer = $area_tree[$perm->showareas($area)];

   $sql="SELECT
           *
        FROM
           ".$cfg["tab"]["rights"]."
        WHERE
           idclient='".Contenido_Security::toInteger($client)."' AND
           idarea IN (".implode (',', $AreaContainer).") AND
           idcat != 0 AND
           idaction!='0' AND
           {$where_users}";

   if ($idlang) {
      $sql.= " AND idlang='".Contenido_Security::toInteger($idlang)."'";
   }

   $db->query($sql);

   $RightsContainer = array();

   while($db->next_record()){
       $RightsContainer[$db->f("user_id")][$db->f("idlang")][$db->f("type")][$db->f("idaction")] = $db->f("idarea");
   }

   // i found no better way to set the rights
   // double entries should not be possible anymore...

   foreach ($RightsContainer as $userid=>$LangContainer) {

      foreach ($LangContainer as $idlang=>$TypeContainer) {

         foreach ($TypeContainer as $type=>$ActionContainer) {

            foreach ($ActionContainer as $idaction=>$idarea) {

               $sql="INSERT INTO ".$cfg["tab"]["rights"]."
                    (idright, user_id,idarea,idaction,idcat,idclient,idlang,`type`)
                    VALUES ('".Contenido_Security::toInteger($db2->nextid($cfg["tab"]["rights"]))."', '".Contenido_Security::toInteger($userid)."', '".Contenido_Security::toInteger($idarea)."',
                    '".Contenido_Security::toInteger($idaction)."', '".Contenido_Security::toInteger($iditem)."', '".Contenido_Security::toInteger($client)."',
                    '".Contenido_Security::toInteger($idlang)."', '".Contenido_Security::toInteger($type)."')";
               $db2->query($sql);
            }
         }
      }
   }

   // permissions reloaded...
   $perm->load_permissions(true);

}


/**
 * Delete rights for any element
 *
 * @param string $area main area name
 * @param int $iditem ID of new element
 * @param int $idlang ID of lang parameter
 *
 * @author Martin Horwath <horwath@dayside.net>
 * @copyright dayside.net <dayside.net>
 */
function deleteRightsForElement ($area, $iditem, $idlang=false) {

   global $cfg, $perm, $area_tree, $client;

   $db = new DB_Contenido;
   
   // get all idarea values for $area
   $AreaContainer = $area_tree[$perm->showareas(Contenido_Security::escapeDB($area, $db))];

   $sql = "DELETE FROM ".$cfg["tab"]["rights"]." WHERE idcat='".Contenido_Security::toInteger($iditem)."' AND idclient='".Contenido_Security::toInteger($client)."' AND idarea IN (".implode (',', $AreaContainer).")";
   if ($idlang) {
      $sql.= " AND idlang='".Contenido_Security::toInteger($idlang)."'";
   }
   $db->query($sql);

   // permissions reloaded...
   $perm->load_permissions(true);

}

?>