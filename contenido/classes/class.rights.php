<?php

/**
 * This file contains the the rights class.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Marcus Gnaß
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains methods to handle rights.
 *
 * @package    Core
 * @subpackage Backend
 */
class cRights
{
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
    public static function copyRightsForElement($area, $iditem, $newiditem, $idlang = false)
    {
        global $area_tree;

        $perm = cRegistry::getPerm();
        $auth = cRegistry::getAuth();

        if (!is_object($perm)) {
            return false;
        }
        if (!is_object($auth)) {
            return false;
        }

        $oDestRightCol    = new cApiRightCollection();
        $oSourceRightsColl = new cApiRightCollection();
        $whereUsers       = [];
        $whereAreaActions = [];

        // get all user_id values for con_rights
        // add groups if available
        $userIDContainer = $perm->getGroupsForUser($auth->auth['uid']);
        // add user_id of current user
        $userIDContainer[] = $auth->auth['uid'];
        foreach ($userIDContainer as $key) {
            $whereUsers[] = "user_id = '" . $oDestRightCol->escape($key) . "'";
        }
        // only duplicate on user and where user is member of
        $whereUsers = '(' . implode(' OR ', $whereUsers) . ')';
        // get all idarea values for $area
        $areaContainer = $area_tree[$perm->showareas($area)];

        // get all actions for corresponding area
        $oActionColl = new cApiActionCollection();
        $oActionColl->select('idarea IN (' . implode(',', $areaContainer) . ')');
        while (($oItem = $oActionColl->next()) !== false) {
            $whereAreaActions[] =
                '(idarea = ' . (int)$oItem->get('idarea') . ' AND idaction = ' . (int)$oItem->get('idaction') . ')';
        }
        // only correct area action pairs possible
        $whereAreaActions = '(' . implode(' OR ', $whereAreaActions) . ')';

        // final where clause to get all affected elements in con_right
        $sWhere = "{$whereAreaActions} AND {$whereUsers} AND idcat = {$iditem}";
        if ($idlang) {
            $sWhere .= ' AND idlang=' . (int)$idlang;
        }

        $oSourceRightsColl->select($sWhere);
        while (($oItem = $oSourceRightsColl->next()) !== false) {
            $rs = $oItem->toObject();
            $oDestRightCol->create(
                $rs->user_id,
                $rs->idarea,
                $rs->idaction,
                $newiditem,
                $rs->idclient,
                $rs->idlang,
                $rs->type
            );
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
    public static function createRightsForElement($area, $iditem, $idlang = false)
    {
        global $area_tree;

        $perm = cRegistry::getPerm();
        $auth = cRegistry::getAuth();
        $client = cRegistry::getClientId();

        if (!is_object($perm)) {
            return false;
        }
        if (!is_object($auth)) {
            return false;
        }

        $oDestRightCol    = new cApiRightCollection();
        $oSourceRightsColl = new cApiRightCollection();
        $whereUsers       = [];
        $rightsCache      = [];

        // get all user_id values for con_rights
        // add groups if available
        $userIDContainer = $perm->getGroupsForUser($auth->auth['uid']);
        // add user_id of current user
        $userIDContainer[] = $auth->auth['uid'];
        foreach ($userIDContainer as $key) {
            $whereUsers[] = "user_id = '" . $oDestRightCol->escape($key) . "'";
        }
        // only duplicate on user and where user is member of
        $whereUsers = '(' . implode(' OR ', $whereUsers) . ')';
        // get all idarea values for $area short way
        $areaContainer = $area_tree[$perm->showareas($area)];

        // statement to get all existing actions/areas for corresponding area.
        // all existing rights for same area will be taken over to new item.
        $sWhere = 'idclient=' . (int)$client . ' AND idarea IN (' . implode(',', $areaContainer) . ')'
            . ' AND idcat != 0 AND idaction != 0 AND ' . $whereUsers;
        if ($idlang) {
            $sWhere .= ' AND idlang=' . (int)$idlang;
        }

        $oSourceRightsColl->select($sWhere);
        while (($oItem = $oSourceRightsColl->next()) !== false) {
            $rs = $oItem->toObject();

            // concatenate a key to use it to prevent double entries
            $key = $rs->user_id . '-' . $rs->idarea . '-' . $rs->idaction . '-' . $iditem . '-' . $rs->idclient . '-'
                . $rs->idlang . '-' . $rs->type;
            if (isset($rightsCache[$key])) {
                continue;
            }

            // create new right entry
            $oDestRightCol->create(
                $rs->user_id,
                $rs->idarea,
                $rs->idaction,
                $iditem,
                $rs->idclient,
                $rs->idlang,
                $rs->type
            );

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
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public static function deleteRightsForElement($area, $iditem, $idlang = false)
    {
        global $area_tree;

        $perm = cRegistry::getPerm();
        $client = cRegistry::getClientId();

        // get all idarea values for $area
        $areaContainer = $area_tree[$perm->showareas($area)];
        $areaContainer = implode(',', $areaContainer);

        $sWhere = "idcat=" . (int)$iditem . " AND idclient=" . (int)$client . " AND idarea IN (" . $areaContainer . ")";
        if ($idlang) {
            $sWhere .= " AND idlang=" . (int)$idlang;
        }

        $oRightColl = new cApiRightCollection();
        $oRightColl->deleteByWhereClause($sWhere);

        // permissions reloaded...
        $perm->load_permissions(true);
    }

    /**
     * Builds user/group permissions (sysadmin, admin, client and language) by processing request variables
     * ($msysadmin, $madmin, $mclient, $mlang) and returns the build permissions array.
     *
     * @todo Do we really need to add other perms, if the user/group gets the 'sysadmin' permission?
     *
     * @param bool $bAddUserToClient
     *         Flag to add current user to current client, if no client is specified.
     *
     * @return array
     *
     * @throws cDbException
     */
    public static function buildUserOrGroupPermsFromRequest($bAddUserToClient = false)
    {
        global $msysadmin, $madmin, $mclient, $mlang;

        $auth = cRegistry::getAuth();
        $client = cRegistry::getClientId();

        // check and prevalidation

        $bSysadmin = isset($msysadmin) && $msysadmin;

        $aAdmin = (isset($madmin) && is_array($madmin)) ? $madmin : [];
        foreach ($aAdmin as $p => $value) {
            if (!is_numeric($value)) {
                unset($aAdmin[$p]);
            }
        }

        $aClient = (isset($mclient) && is_array($mclient)) ? $mclient : [];
        foreach ($aClient as $p => $value) {
            if (!is_numeric($value)) {
                unset($aClient[$p]);
            }
        }

        $aLang = (isset($mlang) && is_array($mlang)) ? $mlang : [];
        foreach ($aLang as $p => $value) {
            if (!is_numeric($value)) {
                unset($aLang[$p]);
            }
        }

        // build permissions array
        $aPerms = [];

        if ($bSysadmin) {
            $aPerms[] = 'sysadmin';
        }

        foreach ($aAdmin as $value) {
            $aPerms[] = sprintf('admin[%s]', $value);
        }

        foreach ($aClient as $value) {
            $aPerms[] = sprintf('client[%s]', $value);
        }

        // Add user to the current client, if the current user isn't sysadmin and no client has been specified.
        // This avoids new accounts which are not accessible by the current user (client admin) anymore.
        if (count($aClient) == 0 && $bAddUserToClient) {
            if (!cPermission::checkSysadminPermission($auth->getPerms())) {
                $aPerms[] = sprintf('client[%s]', $client);
            }
        }

        // adding language perms makes sense if we have also at least one selected client
        if (count($aLang) > 0 && count($aClient) > 0) {
            foreach ($aLang as $idlang) {
                $oClientLanguageCollection = new cApiClientLanguageCollection();
                $hasLanguageInClients      = $oClientLanguageCollection->hasLanguageInClients($idlang, $aClient);
                if ($hasLanguageInClients) {
                    $aPerms[] = sprintf('lang[%s]', $idlang);
                }
            }
        }

        return $aPerms;
    }

    /**
     * @return bool
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public static function saveRights()
    {
        global $db, $userid;
        global $rights_list, $rights_list_old, $rights_client, $rights_lang;
        global $aArticleRights, $aCategoryRights, $aTemplateRights;

        $perm = cRegistry::getPerm();

        // If no checkbox is checked
        if (!is_array($rights_list)) {
            $rights_list = [];
        }

        // Search all checks which are not in the new rights_list for deleting
        $arrayDel = array_diff(array_keys($rights_list_old), array_keys($rights_list));

        // Search all checks which are not in the rights_list_old for saving
        $arraySave = array_diff(array_keys($rights_list), array_keys($rights_list_old));
        $oAreaColl = new cApiAreaCollection();

        foreach ($arrayDel as $value) {
            $data = explode('|', $value);

            // Do not delete rights that does not display at this moment
            if (!empty($_REQUEST['filter_rights'])) {
                if (($_REQUEST['filter_rights'] != 'article' && in_array($data[1], $aArticleRights))
                    || ($_REQUEST['filter_rights'] != 'category' && in_array($data[1], $aCategoryRights))
                    || ($_REQUEST['filter_rights'] != 'template' && in_array($data[1], $aTemplateRights))
                ) {
                    continue;
                }

                if ($_REQUEST['filter_rights'] != 'other'
                    && !in_array($data[1], array_merge($aArticleRights, $aCategoryRights, $aTemplateRights))
                ) {
                    continue;
                }
            }

            $data[0] = $oAreaColl->getAreaId($data[0]);
            $data[1] = $perm->getIdForAction($data[1]);

            $where      =
                "user_id = '" . $db->escape($userid) . "' AND idclient = " . (int)$rights_client . " AND idlang = "
                . (int)$rights_lang . " AND idarea = " . (int)$data[0] . " AND idcat = " . (int)$data[2]
                . " AND idaction = " . (int)$data[1] . " AND type = 0";
            $oRightColl = new cApiRightCollection();
            $oRightColl->deleteByWhereClause($where);
        }

        unset($data);

        // Search for all mentioned checkboxes
        foreach ($arraySave as $value) {
            // Explodes the key it consists of areaid+actionid+itemid
            $data = explode('|', $value);

            // Since areas are stored in a numeric form in the rights table,
            // we have to convert them from strings into numbers
            $data[0] = $oAreaColl->getAreaId($data[0]);
            $data[1] = $perm->getIdForAction($data[1]);

            if (!isset($data[1])) {
                $data[1] = 0;
            }

            // Insert new right
            $oRightColl = new cApiRightCollection();
            $oRightColl->create($userid, $data[0], $data[1], $data[2], $rights_client, $rights_lang, 0);
        }

        $rights_list_old = $rights_list;

        return true;
    }

    /**
     * @return bool
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public static function saveGroupRights()
    {
        global $db, $groupid;
        global $rights_list, $rights_list_old, $rights_client, $rights_lang;
        global $aArticleRights, $aCategoryRights, $aTemplateRights;

        $perm = cRegistry::getPerm();

        // If no checkbox is checked
        if (!is_array($rights_list)) {
            $rights_list = [];
        }

        // Search all checks which are not in the new rights_list for deleting
        $arrayDel = array_diff(array_keys($rights_list_old), array_keys($rights_list));

        // Search all checks which are not in the rights_list_old for saving
        $arraySave = array_diff(array_keys($rights_list), array_keys($rights_list_old));

        $oAreaColl = new cApiAreaCollection();

        foreach ($arrayDel as $value) {
            $data = explode('|', $value);

            // Do not delete grouprights that does not display at this moment
            if (!empty($_REQUEST['filter_rights'])) {
                if (($_REQUEST['filter_rights'] != 'article' && in_array($data[1], $aArticleRights))
                    || ($_REQUEST['filter_rights'] != 'category' && in_array($data[1], $aCategoryRights))
                    || ($_REQUEST['filter_rights'] != 'template' && in_array($data[1], $aTemplateRights))
                ) {
                    continue;
                }

                if ($_REQUEST['filter_rights'] != 'other'
                    && !in_array($data[1], array_merge($aArticleRights, $aCategoryRights, $aTemplateRights))
                ) {
                    continue;
                }
            }

            $data[0] = $oAreaColl->getAreaId($data[0]);
            $data[1] = $perm->getIdForAction($data[1]);

            $where      =
                "user_id = '" . $db->escape($groupid) . "' AND idclient = " . (int)$rights_client . " AND idlang = "
                . (int)$rights_lang . " AND idarea = " . (int)$data[0] . " AND idcat = " . (int)$data[2]
                . " AND idaction = " . (int)$data[1] . " AND type = 1";
            $oRightColl = new cApiRightCollection();
            $oRightColl->deleteByWhereClause($where);
        }

        unset($data);

        // Search for all mentioned checkboxes
        foreach ($arraySave as $value) {
            // Explodes the key it consists of areaid+actionid+itemid
            $data = explode('|', $value);

            // Since areas are stored in a numeric form in the rights table,
            // we have to convert them from strings into numbers
            $data[0] = $oAreaColl->getAreaId($data[0]);
            $data[1] = $perm->getIdForAction($data[1]);

            if (!isset($data[1])) {
                $data[1] = 0;
            }

            // Insert new right
            $oRightColl = new cApiRightCollection();
            $oRightColl->create($groupid, $data[0], $data[1], $data[2], $rights_client, $rights_lang, 1);
        }

        $rights_list_old = $rights_list;

        return true;
    }

    /**
     * Build list of rights for all relevant and online areas except "login" and their relevant actions.
     *
     * @return array
     */
    public static function getRightsList()
    {
        $areas   = new cApiAreaCollection();
        $navSubs = new cApiNavSubCollection();
        $actions = new cApiActionCollection();

        try {
            $rights = [];

            $areas->select('relevant = 1 AND online = 1 AND name != "login" ORDER BY idarea ASC');
            while ($area = $areas->next()) {
                $right = [
                    'perm'     => $area->get('name'),
                    'location' => '',
                ];

                // get location
                $navSubs->select('idarea = ' . (int)$area->get('idarea') . ' ORDER BY idarea ASC');
                if ($navSubItem = $navSubs->next()) {
                    $right['location'] = $navSubItem->get('location');
                }

                // get relevant actions
                $actions->select('relevant = 1 AND idarea = ' . (int)$area->get('idarea') . ' ORDER BY idarea ASC');
                while ($action = $actions->next()) {
                    $right['action'][] = $action->get('name');
                }

                // insert into list
                if ($area->get('parent_id') == '0') {
                    $key = $area->get('name');
                } else {
                    $key = $area->get('parent_id');
                }
                $rights[$key][$area->get('name')] = $right;
            }
        } catch (cDbException $e) {
            $rights = [];
        } catch (cException $e) {
            $rights = [];
        }

        return $rights;
    }
}
