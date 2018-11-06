<?php

/**
 * This file contains the CONTENIDO rights functions.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Martin Horwath
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Function checks if a language is associated with a given list of clients
 *
 * @param array $aClients
 *         array of clients to check
 * @param int   $iLang
 *         language id which should be checked
 * @param array $aCfg
 *         CONTENIDO configruation array (no more needed)
 * @param cDb   $oDb
 *         CONTENIDO database object (no more needed)
 *
 * @return bool
 *         status
 *         If language id corresponds to list of clients true otherwise false.
 * 
 * @throws cDbException
 */
function checkLangInClients($aClients, $iLang, $aCfg, $oDb) {
    $oClientLanguageCollection = new cApiClientLanguageCollection();
    return $oClientLanguageCollection->hasLanguageInClients($iLang, $aClients);
}

/**
 * Duplicate rights for any element.
 *
 * @param string $area
 *         Main area name (e. g. 'lay', 'mod', 'str', 'tpl', etc.)
 * @param int    $iditem
 *         ID of element to copy
 * @param int    $newiditem
 *         ID of the new element
 * @param bool   $idlang
 *         ID of language, if passed only rights for this language
 *         will be created, otherwise for all existing languages
 *
 * @return bool
 *         True on success otherwise false
 * 
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 */
function copyRightsForElement($area, $iditem, $newiditem, $idlang = false) {
    global $perm, $auth, $area_tree;

    if (!is_object($perm)) {
        return false;
    }
    if (!is_object($auth)) {
        return false;
    }

    $oDestRightCol = new cApiRightCollection();
    $oSourceRighsColl = new cApiRightCollection();
    $whereUsers = array();
    $whereAreaActions = array();

    // get all user_id values for con_rights
    $userIDContainer = $perm->getGroupsForUser($auth->auth['uid']); // add
    // groups if
    // available
    $userIDContainer[] = $auth->auth['uid']; // add user_id of current user
    foreach ($userIDContainer as $key) {
        $whereUsers[] = "user_id = '" . $oDestRightCol->escape($key) . "'";
    }
    $whereUsers = '(' . implode(' OR ', $whereUsers) . ')'; // only duplicate on
    // user and where
    // user is member of
    // get all idarea values for $area
    $areaContainer = $area_tree[$perm->showareas($area)];

    // get all actions for corresponding area
    $oActionColl = new cApiActionCollection();
    $oActionColl->select('idarea IN (' . implode(',', $areaContainer) . ')');
    while (($oItem = $oActionColl->next()) !== false) {
        $whereAreaActions[] = '(idarea = ' . (int) $oItem->get('idarea') . ' AND idaction = ' . (int) $oItem->get('idaction') . ')';
    }
    $whereAreaActions = '(' . implode(' OR ', $whereAreaActions) . ')'; // only
    // correct
    // area
    // action
    // pairs
    // possible
    // final where clause to get all affected elements in con_right
    $sWhere = "{$whereAreaActions} AND {$whereUsers} AND idcat = {$iditem}";
    if ($idlang) {
        $sWhere .= ' AND idlang=' . (int) $idlang;
    }

    $oSourceRighsColl->select($sWhere);
    while (($oItem = $oSourceRighsColl->next()) !== false) {
        $rs = $oItem->toObject();
        $oDestRightCol->create($rs->user_id, $rs->idarea, $rs->idaction, $newiditem, $rs->idclient, $rs->idlang, $rs->type);
    }

    // permissions reloaded...
    $perm->load_permissions(true);

    return true;
}

/**
 * Create rights for any element
 *
 * @param string $area
 *         Main area name (e. g. 'lay', 'mod', 'str', 'tpl', etc.)
 * @param int    $iditem
 *         ID of new element
 * @param bool   $idlang
 *         ID of language, if passed only rights for this language
 *         will be created, otherwise for all existing languages
 *
 * @return bool
 *         True on success otherwise false
 * 
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 */
function createRightsForElement($area, $iditem, $idlang = false) {
    global $perm, $auth, $area_tree, $client;

    if (!is_object($perm)) {
        return false;
    }
    if (!is_object($auth)) {
        return false;
    }

    $oDestRightCol = new cApiRightCollection();
    $oSourceRighsColl = new cApiRightCollection();
    $whereUsers = array();
    $rightsCache = array();

    // get all user_id values for con_rights
    $userIDContainer = $perm->getGroupsForUser($auth->auth['uid']); // add
    // groups if
    // available
    $userIDContainer[] = $auth->auth['uid']; // add user_id of current user
    foreach ($userIDContainer as $key) {
        $whereUsers[] = "user_id = '" . $oDestRightCol->escape($key) . "'";
    }
    $whereUsers = '(' . implode(' OR ', $whereUsers) . ')'; // only duplicate on
    // user and where
    // user is member of
    // get all idarea values for $area short way
    $areaContainer = $area_tree[$perm->showareas($area)];

    // statement to get all existing actions/areas for corresponding area.
    // all existing rights for same area will be taken over to new item.
    $sWhere = 'idclient=' . (int) $client . ' AND idarea IN (' . implode(',', $areaContainer) . ')' . ' AND idcat != 0 AND idaction != 0 AND ' . $whereUsers;
    if ($idlang) {
        $sWhere .= ' AND idlang=' . (int) $idlang;
    }

    $oSourceRighsColl->select($sWhere);
    while (($oItem = $oSourceRighsColl->next()) !== false) {
        $rs = $oItem->toObject();

        // concatenate a key to use it to prevent double entries
        $key = $rs->user_id . '-' . $rs->idarea . '-' . $rs->idaction . '-' . $iditem . '-' . $rs->idclient . '-' . $rs->idlang . '-' . $rs->type;
        if (isset($rightsCache[$key])) {
            continue;
        }

        // create new right entry
        $oDestRightCol->create($rs->user_id, $rs->idarea, $rs->idaction, $iditem, $rs->idclient, $rs->idlang, $rs->type);

        $rightsCache[$key] = true;
    }

    // permissions reloaded...
    $perm->load_permissions(true);

    return true;
}

/**
 * Delete rights for any element
 *
 * @param string $area
 *         main area name
 * @param int    $iditem
 *         ID of new element
 * @param bool   $idlang
 *         ID of lang parameter
 *
 * @throws cDbException
 * @throws cInvalidArgumentException
 */
function deleteRightsForElement($area, $iditem, $idlang = false) {
    global $perm, $area_tree, $client;

    // get all idarea values for $area
    $areaContainer = $area_tree[$perm->showareas($area)];

    $sWhere = "idcat=" . (int) $iditem . " AND idclient=" . (int) $client . " AND idarea IN (" . implode(',', $areaContainer) . ")";
    if ($idlang) {
        $sWhere .= " AND idlang=" . (int) $idlang;
    }

    $oRightColl = new cApiRightCollection();
    $oRightColl->deleteByWhereClause($sWhere);

    // permissions reloaded...
    $perm->load_permissions(true);
}

/**
 * Builds user/group permissions (sysadmin, admin, client and language) by
 * processing request variables ($msysadmin, $madmin, $mclient, $mlang) and
 * returns the build permissions array.
 *
 * @todo Do we really need to add other perms, if the user/group gets the
 *       'sysadmin' permission?
 *
 * @param bool $bAddUserToClient
 *         Flag to add current user to current client, if no client is specified.
 *
 * @return array
 * 
 * @throws cDbException
 */
function buildUserOrGroupPermsFromRequest($bAddUserToClient = false) {
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
        // Add user to the current client, if the current user isn't sysadmin
        // and
        // no client has been specified. This avoids new accounts which are not
        // accessible by the current user (client admin) anymore.
        $aUserPerm = explode(',', $auth->auth['perm']);
        if (!in_array('sysadmin', $aUserPerm)) {
            $aPerms[] = sprintf('client[%s]', $client);
        }
    }

    if (count($aLang) > 0 && count($aClient) > 0) {
        // adding language perms makes sense if we have also at least one
        // selected client
        $db = cRegistry::getDb();
        foreach ($aLang as $value) {
            if (checkLangInClients($aClient, $value, $cfg, $db)) {
                $aPerms[] = sprintf('lang[%s]', $value);
            }
        }
    }

    return $aPerms;
}

/**
 *
 * @return bool
 * 
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 */
function saveRights() {
    global $perm, $notification, $db, $userid;
    global $rights_list, $rights_list_old, $rights_client, $rights_lang;
    global $aArticleRights, $aCategoryRights, $aTemplateRights;

    // If no checkbox is checked
    if (!is_array($rights_list)) {
        $rights_list = array();
    }

    // Search all checks which are not in the new rights_list for deleting
    $arraydel = array_diff(array_keys($rights_list_old), array_keys($rights_list));

    // Search all checks which are not in the rights_list_old for saving
    $arraysave = array_diff(array_keys($rights_list), array_keys($rights_list_old));
    $oAreaColl = new cApiAreaCollection();

    if (is_array($arraydel)) {
        foreach ($arraydel as $value) {

            $data = explode('|', $value);

            // Do not delete rights that does not display at this moment
            if (!empty($_REQUEST['filter_rights'])) {
                if (($_REQUEST['filter_rights'] != 'article' && in_array($data[1], $aArticleRights)) ||
                    ($_REQUEST['filter_rights'] != 'category' && in_array($data[1], $aCategoryRights)) ||
                    ($_REQUEST['filter_rights'] != 'template' && in_array($data[1], $aTemplateRights))) {
                    continue;
                }

                if ($_REQUEST['filter_rights'] != 'other' && !in_array($data[1], array_merge($aArticleRights, $aCategoryRights, $aTemplateRights))) {
                    continue;
                }
            }

            $data[0] = $oAreaColl->getAreaID($data[0]);
            $data[1] = $perm->getIDForAction($data[1]);

            $where = "user_id = '" . $db->escape($userid) . "' AND idclient = " . (int) $rights_client . " AND idlang = " . (int) $rights_lang . " AND idarea = " . (int) $data[0] . " AND idcat = " . (int) $data[2] . " AND idaction = " . (int) $data[1] . " AND type = 0";
            $oRightColl = new cApiRightCollection();
            $oRightColl->deleteByWhereClause($where);
        }
    }

    unset($data);

    // Search for all mentioned checkboxes
    if (is_array($arraysave)) {
        foreach ($arraysave as $value) {
            // Explodes the key it consits areaid+actionid+itemid
            $data = explode('|', $value);

            // Since areas are stored in a numeric form in the rights table, we
            // have
            // to convert them from strings into numbers
            $data[0] = $oAreaColl->getAreaID($data[0]);
            $data[1] = $perm->getIDForAction($data[1]);

            if (!isset($data[1])) {
                $data[1] = 0;
            }

            // Insert new right
            $oRightColl = new cApiRightCollection();
            $oRightColl->create($userid, $data[0], $data[1], $data[2], $rights_client, $rights_lang, 0);
        }
    }

    $rights_list_old = $rights_list;

    return true;

}

/**
 *
 * @return bool
 * 
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 */
function saveGroupRights() {
    global $perm, $notification, $db, $groupid;
    global $rights_list, $rights_list_old, $rights_client, $rights_lang;
    global $aArticleRights, $aCategoryRights, $aTemplateRights;

    // If no checkbox is checked
    if (!is_array($rights_list)) {
        $rights_list = array();
    }

    // Search all checks which are not in the new rights_list for deleting
    $arraydel = array_diff(array_keys($rights_list_old), array_keys($rights_list));

    // Search all checks which are not in the rights_list_old for saving
    $arraysave = array_diff(array_keys($rights_list), array_keys($rights_list_old));

    $oAreaColl = new cApiAreaCollection();

    if (is_array($arraydel)) {
        foreach ($arraydel as $value) {
            $data = explode('|', $value);

            // Do not delete grouprights that does not display at this moment
            if (!empty($_REQUEST['filter_rights'])) {
                if (($_REQUEST['filter_rights'] != 'article' && in_array($data[1], $aArticleRights)) ||
                    ($_REQUEST['filter_rights'] != 'category' && in_array($data[1], $aCategoryRights)) ||
                    ($_REQUEST['filter_rights'] != 'template' && in_array($data[1], $aTemplateRights))) {
                    continue;
                }

                if ($_REQUEST['filter_rights'] != 'other' && !in_array($data[1], array_merge($aArticleRights, $aCategoryRights, $aTemplateRights))) {
                    continue;
                }
            }

            $data[0] = $oAreaColl->getAreaID($data[0]);
            $data[1] = $perm->getIDForAction($data[1]);

            $where = "user_id = '" . $db->escape($groupid) . "' AND idclient = " . (int) $rights_client . " AND idlang = " . (int) $rights_lang . " AND idarea = " . (int) $data[0] . " AND idcat = " . (int) $data[2] . " AND idaction = " . (int) $data[1] . " AND type = 1";
            $oRightColl = new cApiRightCollection();
            $oRightColl->deleteByWhereClause($where);
        }
    }

    unset($data);

    // Search for all mentioned checkboxes
    if (is_array($arraysave)) {
        foreach ($arraysave as $value) {
            // Explodes the key it consits areaid+actionid+itemid
            $data = explode('|', $value);

            // Since areas are stored in a numeric form in the rights table, we
            // have
            // to convert them from strings into numbers
            $data[0] = $oAreaColl->getAreaID($data[0]);
            $data[1] = $perm->getIDForAction($data[1]);

            if (!isset($data[1])) {
                $data[1] = 0;
            }

            // Insert new right
            $oRightColl = new cApiRightCollection();
            $oRightColl->create($groupid, $data[0], $data[1], $data[2], $rights_client, $rights_lang, 1);
        }
    }

    $rights_list_old = $rights_list;
    return true;
}
