<?php
/******************************************
* File      :   functions.rights.php
* Project   :   Contenido
* Descr    :   Defines the 'rights' related
*            functions
*
* Author   :   Martin Horwath
* Created   :   25.11.2004
* Modified   :   12.12.2004
*
* © dayside.net
*****************************************/

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
   
   // long way start
   /*
      $sql = "SELECT
            group_id
         FROM
            ".$cfg["tab"]["groupmembers"]."
         WHERE
            user_id = '".$userIDContainer[0]."'";

   $db->query($sql);

   while ($db->next_record()) {
      $userIDContainer[] = $db->f("group_id"); // add group_ids
   }
   */
   // long way end

   foreach ($userIDContainer as $key) {
      $statement_where2[] = "user_id = '".$key."' ";
   }

   $where_users =   "(".implode(" OR ", $statement_where2 ) .")"; // only duplicate on user and where user is member of

   // get all idarea values for $area
   // short way
   $AreaContainer = $area_tree[$perm->showareas($area)];

   // long way
   /*
   $AreaContainer[0] = $perm->getIDForArea($area);
   $sql = "SELECT
            idarea
         FROM
            ".$cfg["tab"]["area"]."
         WHERE
            parent_id = '".$area."'";

   $db->query($sql);

   while ($db->next_record()) {
      $AreaContainer[] = $db->f("idarea");
   }
   */

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
      $statement_where[] = "( idarea = ".$key["idarea"]." AND idaction = ".$key["idaction"]." )";
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

   /*
   // short version start
   $sql = "SELECT
            *
         FROM
            ".$cfg["tab"]["rights"]."
         WHERE
            idarea IN (".implode (',', $AreaContainer).") AND
            idaction != 0 AND
            {$where_users} AND
            idcat = {$iditem}";
   // short version end
   */

   if ($idlang) {
      $sql.= " AND idlang='$idlang'";
   }

   $db->query($sql);

   while ($db->next_record()) {
      $sql = "INSERT INTO ".$cfg["tab"]["rights"]." (idright,user_id,idarea,idaction,idcat,idclient,idlang,`type`) VALUES ('".$db2->nextid($cfg["tab"]["rights"])."','".$db->f("user_id")."','".$db->f("idarea")."','".$db->f("idaction")."','".$newiditem."','".$db->f("idclient")."','".$db->f("idlang")."','".$db->f("type")."');";
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
      $statement_where2[] = "user_id = '".$key."' ";
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
           idclient='$client' AND
           idarea IN (".implode (',', $AreaContainer).") AND
           idcat != 0 AND
           idaction!='0' AND
           {$where_users}";

   if ($idlang) {
      $sql.= " AND idlang='$idlang'";
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
                    VALUES ('".$db2->nextid($cfg["tab"]["rights"])."', '".$userid."','".$idarea."','".$idaction."','$iditem','$client','".$idlang."','".$type."')";
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

   // get all idarea values for $area
   $AreaContainer = $area_tree[$perm->showareas($area)];

   $db = new DB_Contenido;

   $sql = "DELETE FROM ".$cfg["tab"]["rights"]." WHERE idcat='$iditem' AND idclient='$client' AND idarea IN (".implode (',', $AreaContainer).")";
   if ($idlang) {
      $sql.= " AND idlang='$idlang'";
   }
   $db->query($sql);

   // permissions reloaded...
   $perm->load_permissions(true);

}

?>
