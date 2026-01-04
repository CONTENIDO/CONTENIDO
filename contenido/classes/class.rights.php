<?php

/**
 * This file contains the rights class.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Marcus Gnaß
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
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
     * @param string $area Main area name (e.g. 'lay', 'mod', 'str', 'tpl', etc.)
     * @param int $itemId ID of element (category) to copy from
     * @param int $newItemId ID of the new element (category)
     * @param int|false $languageId ID of language, if passed only rights for this language will be created,
     *      otherwise for all existing languages.
     * @return bool True on success otherwise false
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public static function copyRightsForElement($area, $itemId, $newItemId, $languageId = false): bool
    {
        global $area_tree;

        $perm = cRegistry::getPerm();
        $auth = cRegistry::getAuth();

        if (!is_object($perm) || !is_object($auth)) {
            return false;
        }

        $oDestRightCol = new cApiRightCollection();
        $oSourceRightsColl = new cApiRightCollection();
        $whereUsers = [];
        $whereAreaActions = [];

        // get all user_id values for con_rights add groups if available
        $userIDContainer = $perm->getGroupsForUser($auth->getUserId());
        // add user_id of current user
        $userIDContainer[] = $auth->getUserId();
        foreach ($userIDContainer as $key) {
            $whereUsers[] = sprintf("`user_id` = '%s'", $oDestRightCol->escape($key));
        }
        // only duplicate on user and where user is member of
        $whereUsers = sprintf('(%s)', implode(' OR ', $whereUsers));
        // get all idarea values for $area
        $areaContainer = $area_tree[$perm->showAreas($area)];

        // get all actions for corresponding area
        $oActionColl = new cApiActionCollection();
        $oActionColl->select(sprintf('`idarea` IN (%s)', implode(',', $areaContainer)));
        while ($oItem = $oActionColl->next()) {
            $whereAreaActions[] = sprintf(
                '(`idarea` = %d AND `idaction` = %d)',
                $oItem->get('idarea'),
                $oItem->get('idaction')
            );
        }
        // only correct area action pairs possible
        $whereAreaActions = '(' . implode(' OR ', $whereAreaActions) . ')';

        // final where clause to get all affected elements in con_right
        $sWhere = sprintf(
            "%s AND %s AND `idcat` = %d",
            $whereAreaActions,
            $whereUsers,
            $itemId
        );
        if ($languageId) {
            $sWhere .= sprintf(' AND `idlang` = %d', $languageId);
        }

        $oSourceRightsColl->select($sWhere);
        while ($oItem = $oSourceRightsColl->next()) {
            $rs = $oItem->toObject();
            $oDestRightCol->create(
                $rs->user_id,
                $rs->idarea,
                $rs->idaction,
                $newItemId,
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
     * @param string $area Main area name (e.g. 'lay', 'mod', 'str', 'tpl', etc.)
     * @param int $itemId ID of new element (category)
     * @param int|false $languageId ID of language, if passed only rights for this language will be created,
     *      otherwise for all existing languages
     * @return bool True on success otherwise false
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public static function createRightsForElement($area, $itemId, $languageId = false): bool
    {
        global $area_tree;

        $perm = cRegistry::getPerm();
        $auth = cRegistry::getAuth();
        $client = cRegistry::getClientId();

        if (!is_object($perm) || !is_object($auth)) {
            return false;
        }

        $oDestRightCol = new cApiRightCollection();
        $oSourceRightsColl = new cApiRightCollection();
        $whereUsers = [];
        $rightsCache = [];

        // get all user_id values for con_rights add groups if available
        $userIDContainer = $perm->getGroupsForUser($auth->getUserId());
        // add user_id of current user
        $userIDContainer[] = $auth->getUserId();
        foreach ($userIDContainer as $key) {
            $whereUsers[] = sprintf("`user_id` = '%s'", $oDestRightCol->escape($key));
        }
        // only duplicate on user and where user is member of
        $whereUsers = sprintf('(%s)', implode(' OR ', $whereUsers));
        // get all idarea values for $area short way
        $areaContainer = $area_tree[$perm->showAreas($area)];

        // statement to get all existing actions/areas for corresponding area.
        // all existing rights for same area will be taken over to new item.
        $sWhere = sprintf(
            '`idclient` = %d AND `idarea` IN (%s) AND `idcat` != 0 AND `idaction` != 0 AND %s',
            $client,
            implode(',', $areaContainer),
            $whereUsers
        );
        if ($languageId) {
            $sWhere .= sprintf(' AND `idlang` = %d', $languageId);
        }

        $oSourceRightsColl->select($sWhere);
        while ($oItem = $oSourceRightsColl->next()) {
            $rs = $oItem->toObject();

            // concatenate a key to use it to prevent double entries
            $key = $rs->user_id . '-' . $rs->idarea . '-' . $rs->idaction . '-' . $itemId . '-' . $rs->idclient . '-'
                . $rs->idlang . '-' . $rs->type;
            if (isset($rightsCache[$key])) {
                continue;
            }

            // create new right entry
            $oDestRightCol->create(
                $rs->user_id,
                $rs->idarea,
                $rs->idaction,
                $itemId,
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
     * @param string $area Main area name
     * @param int $itemId ID of the element (category)
     * @param int|false $languageId ID of lang parameter
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public static function deleteRightsForElement($area, $itemId, $languageId = false)
    {
        global $area_tree;

        $perm = cRegistry::getPerm();
        $client = cRegistry::getClientId();

        // get all idarea values for $area
        $areaContainer = $area_tree[$perm->showAreas($area)];
        $sWhere = sprintf(
            "`idcat` = %d AND `idclient` = %d AND `idarea` IN (%s)",
            $itemId,
            $client,
            implode(',', $areaContainer)
        );

        if ($languageId) {
            $sWhere .= sprintf(" AND `idlang` = %d", $languageId);
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
     * @param bool $addUserToClient Flag to add current user to current client, if no client is specified.
     * @throws cDbException
     * @todo Do we really need to add other perms, if the user/group gets the 'sysadmin' permission?
     */
    public static function buildUserOrGroupPermsFromRequest(bool $addUserToClient = false): array
    {
        // Globals build from request
        global $msysadmin, $madmin, $mclient, $mlang;

        $auth = cRegistry::getAuth();
        $client = cRegistry::getClientId();

        // check and prevalidation

        $isSysadmin = isset($msysadmin) && $msysadmin;

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

        if ($isSysadmin) {
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
        if (count($aClient) == 0 && $addUserToClient) {
            if (!cPermission::checkSysadminPermission($auth->getPerms())) {
                $aPerms[] = sprintf('client[%s]', $client);
            }
        }

        // adding language perms makes sense if we have also at least one selected client
        if (count($aLang) > 0 && count($aClient) > 0) {
            foreach ($aLang as $languageId) {
                $oClientLanguageCollection = new cApiClientLanguageCollection();
                $hasLanguageInClients = $oClientLanguageCollection->hasLanguageInClients($languageId, $aClient);
                if ($hasLanguageInClients) {
                    $aPerms[] = sprintf('lang[%s]', $languageId);
                }
            }
        }

        return $aPerms;
    }

    /**
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public static function saveRights(): bool
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

            $where =
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
     * @throws cDbException|cException|cInvalidArgumentException
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

            $where =
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
     */
    public static function getRightsList(): array
    {
        $areas = new cApiAreaCollection();
        $navSubs = new cApiNavSubCollection();
        $actions = new cApiActionCollection();

        try {
            $rights = [];

            $areas->select('`relevant` = 1 AND `online` = 1 AND `name` != "login" ORDER BY `idarea` ASC');
            while ($area = $areas->next()) {
                $right = [
                    'perm' => $area->get('name'),
                    'location' => '',
                ];

                // get location
                $navSubs->select(sprintf(
                    '`idarea` = %d ORDER BY `idarea` ASC',
                    cSecurity::toInteger($area->get('idarea'))
                ));
                if (($navSubItem = $navSubs->next()) !== false) {
                    $right['location'] = $navSubItem->get('location');
                }

                // get relevant actions
                $actions->select(sprintf(
                    '`relevant` = 1 AND `idarea` = %d ORDER BY `idarea` ASC',
                    cSecurity::toInteger($area->get('idarea'))
                ));
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
        } catch (cDbException|cException $e) {
            $rights = [];
        }

        return $rights;
    }
}
