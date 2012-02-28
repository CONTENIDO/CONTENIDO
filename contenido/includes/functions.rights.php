<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Defines the "rights" related functions
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.0
 * @author     Martin Horwath
 * @copyright  dayside.net
 * @link       http://www.dayside.net
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created  2004-11-25
 *   modified 2008-06-26, Frederic Schneider, add security fix
 *   modified 2011-02-05, Murat Purc, Added function buildUserOrGroupPermsFromRequest()
 *
 *   $Id$:
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
  * @param array $aCfg - CONTENIDO configruation array (no more needed)
  * @param object $oDb - CONTENIDO database object (no more needed)
  *
  * @return boolean - status (if language id corresponds to list of clients true otherwise false)
  */
function checkLangInClients($aClients, $iLang, $aCfg, $oDb)
{
    $oClientLanguageCollection = new cApiClientLanguageCollection();
    return $oClientLanguageCollection->hasLanguageInClients($iLang, $aClients);
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
function copyRightsForElement($area, $iditem, $newiditem, $idlang = false)
{
    global $cfg, $perm, $auth, $area_tree;

    $db = new DB_Contenido();
    $whereUsers = array();
    $whereAreaActions = array();

    // get all user_id values for con_rights
    $userIDContainer = $perm->getGroupsForUser($auth->auth['uid']); // add groups if available
    $userIDContainer[] = $auth->auth['uid']; // add user_id of current user
    foreach ($userIDContainer as $key) {
        $whereUsers[] = "user_id = '" . $db->escape($key) . "'";
    }
    $whereUsers = '(' . implode(' OR ', $whereUsers) . ')'; // only duplicate on user and where user is member of

    // get all idarea values for $area short way
    $areaContainer = $area_tree[$perm->showareas($area)];

    // long version start
    // get all actions for corresponding area
    $oActionColl = new cApiActionCollection();
    $oActionColl->select('idarea IN (' . implode (',', $areaContainer) . ')');
    while ($oItem = $oActionColl->next()) {
        $whereAreaActions[] = '(idarea = ' . (int) $oItem->get('idarea') . ' AND idaction = ' . (int) $oItem->get('idaction') . ')';
    }
    $whereAreaActions = '(' . implode(' OR ', $whereAreaActions) . ')'; // only correct area action pairs possible

    // final where statement to get all effected elements in con_right
    $sWhere = "{$whereAreaActions} AND {$whereUsers} AND idcat = {$iditem}";

    // long version end
    if ($idlang) {
        $sWhere .= ' AND idlang=' . (int) $idlang;
    }

    $oDestRightCol = new cApiRightCollection();
    $oSourceRighsColl = new cApiRightCollection();

    $oSourceRighsColl->select($sWhere);
    while ($oItem = $oSourceRighsColl->next()) {
        $rs = $oItem->toObject();
        $oDestRightCol->create($rs->user_id, $rs->idarea, $rs->idaction, $newiditem, $rs->idclient, $rs->idlang, $rs->type);
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
function createRightsForElement($area, $iditem, $idlang = false)
{
    global $cfg, $perm, $auth, $area_tree, $client;

    if (!is_object($perm)) {
        return false;
    }

    if (!is_object($auth)) {
        return false;
    }

    $db = new DB_Contenido();
    $db2 = new DB_Contenido();

    // get all user_id values for con_rights
    $userIDContainer = $perm->getGroupsForUser($auth->auth['uid']); // add groups if available
    $userIDContainer[] = $auth->auth['uid']; // add user_id of current user
    foreach ($userIDContainer as $key) {
        $whereUsers[] = "user_id = '" . $db->escape($key) . "'";
    }
    $whereUsers = '(' . implode(' OR ', $whereUsers) . ')'; // only duplicate on user and where user is member of

    // get all idarea values for $area
    // short way
    $areaContainer = $area_tree[$perm->showareas($area)];

    $sql = "SELECT
                *
            FROM
                ".$cfg["tab"]["rights"]."
            WHERE
                idclient='".Contenido_Security::toInteger($client)."' AND
                idarea IN (".implode (',', $areaContainer).") AND
                idcat != 0 AND
                idaction!='0' AND
                {$whereUsers}";

    if ($idlang) {
        $sql.= " AND idlang='".Contenido_Security::toInteger($idlang)."'";
    }

    $db->query($sql);

    $RightsContainer = array();

    while($db->next_record()){
        $RightsContainer[$db->f('user_id')][$db->f('idlang')][$db->f('type')][$db->f('idaction')] = $db->f('idarea');
    }

    // i found no better way to set the rights
    // double entries should not be possible anymore...

    foreach ($RightsContainer as $userid=>$LangContainer) {
        foreach ($LangContainer as $idlang=>$TypeContainer) {
            foreach ($TypeContainer as $type=>$ActionContainer) {
                foreach ($ActionContainer as $idaction=>$idarea) {
                    $sql = "INSERT INTO ".$cfg["tab"]["rights"]."
                           (user_id,idarea,idaction,idcat,idclient,idlang,`type`)
                           VALUES ('".Contenido_Security::toInteger($userid)."', '".Contenido_Security::toInteger($idarea)."',
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
function deleteRightsForElement($area, $iditem, $idlang = false)
{
    global $cfg, $perm, $area_tree, $client;

    $db = new DB_Contenido();

    // get all idarea values for $area
    $areaContainer = $area_tree[$perm->showareas(Contenido_Security::escapeDB($area, $db))];

    $sql = "DELETE FROM ".$cfg["tab"]["rights"]." WHERE idcat='".Contenido_Security::toInteger($iditem)."' AND idclient='".Contenido_Security::toInteger($client)."' AND idarea IN (".implode (',', $areaContainer).")";
    if ($idlang) {
        $sql.= " AND idlang='".Contenido_Security::toInteger($idlang)."'";
    }
    $db->query($sql);

    // permissions reloaded...
    $perm->load_permissions(true);
}


/**
 * Builds user/group permissions (sysadmin, admin, client and language) by
 * processing request variables ($msysadmin, $madmin, $mclient, $mlang) and
 * returns the build permissions array.
 *
 * @todo  Do we really need to add other perms, if the user/group gets the
 *        'sysadmin' permission?
 * @param  bool  $bAddUserToClient  Flag to add current user to current client,
 *                                  if no client is specified.
 * @return array
 */
function buildUserOrGroupPermsFromRequest($bAddUserToClient = false)
{
    global $cfg, $msysadmin, $madmin, $mclient, $mlang, $auth, $client;

    $aPerms = array();

    // check and prevalidation

    $bSysadmin = (isset($msysadmin) && $msysadmin);

    $aAdmin = (isset($madmin) && is_array($madmin)) ? $madmin : array();
    foreach ($aAdmin as $p => $value) {
        if (!is_numeric($value)) {
            unset($aAdmin[$p]);
        }
    }

    $aClient = (isset($mclient) && is_array($mclient)) ? $mclient : array();
    foreach ($aClient as $p => $value) {
        if (!is_numeric($value)) {
            unset($aClient[$p]);
        }
    }

    $aLang = (isset($mlang) && is_array($mlang)) ? $mlang : array();
    foreach ($aLang as $p => $value) {
        if (!is_numeric($value)) {
            unset($aLang[$p]);
        }
    }

    // build permissions array

    if ($bSysadmin) {
        $aPerms[] = 'sysadmin';
    }

    foreach ($aAdmin as $value) {
        $aPerms[] = sprintf('admin[%s]', $value);
    }

    foreach ($aClient as $value) {
        $aPerms[] = sprintf('client[%s]', $value);
    }

    if (count($aClient) == 0 && $bAddUserToClient) {
        // Add user to the current client, if the current user isn't sysadmin and
        // no client has been specified. This avoids new accounts which are not
        // accessible by the current user (client admin) anymore.
        $aUserPerm = explode(',', $auth->auth['perm']);
        if (!in_array('sysadmin', $aUserPerm)) {
            $aPerms[] = sprintf('client[%s]', $client);
        }
    }

    if (count($aLang) > 0 && count($aClient) > 0) {
        // adding language perms makes sense if we have also at least one selected client
        $db = new DB_Contenido();
        foreach ($aLang as $value) {
            if (checkLangInClients($aClient, $value, $cfg, $db)) {
                $aPerms[] = sprintf('lang[%s]', $value);
            }
        }
    }

    return $aPerms;
}


?>