<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Permission management
 *
 * Requirements:
 * @con_php_req 5
 *
 * @package    CONTENIDO Core
 * @version    0.3.1
 * @author     Boris Erdmann, Kristian Koehntopp
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <CONTENIDO Version>
 *
 * {@internal
 *   created  2002-08-16
 *   modified 2008-07-04, bilal arslan, added security fix
 *   modified 2009-10-29, Murat Purc, replaced deprecated functions (PHP 5.3 ready) and some formatting
 *   modified 2011-10-09, Murat Purc, Partly ported to PHP 5 and formatted/documented code
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/**
 * @package    CONTENIDO Core
 * @subpackage Permission
 */
class Contenido_Perm
{

    /**
     * Permission class name
     * @var string
     */
    public $classname = 'Contenido_Perm';

    /**
     * Area cache
     * @var array
     */
    public $areacache = array();

    /**
     * Actions cache
     * @var array
     */
    public $actioncache = array();

    /**
     * Database instance
     * @var DB_Contenido
     */
    public $db;

    /**
     * Returns all groups of a user
     * @param   string  $userId
     * @return  array   List of group ids
     */
    public function getGroupsForUser($userId)
    {
        $groups = array();

        $oGroupMemberColl = new cApiGroupMemberCollection();
        $oGroupMemberColl->select("user_id='" . $oGroupMemberColl->escape($userId) . "'");
        while ($oItem = $oGroupMemberColl->next()) {
            $groups[] = $oItem->get('group_id');
        }

        return $groups;
    }

    /**
     * Returns the id of an area. If passed area is numeric, it will returned directly.
     * @param   string|int $area
     * @return  int
     */
    public function getIDForArea($area)
    {
        if (is_numeric($area)) {
            return $area;
        } elseif (isset($this->areacache[$area])) {
            return $this->areacache[$area];
        }

        $oAreaColl = new cApiAreaCollection();
        $oAreaColl->select("name='" . $oAreaColl->escape($area) . "'");
        if ($oItem = $oAreaColl->next()) {
            $this->areacache[$area] = $oItem->get('idarea');
            $area = $oItem->get('idarea');
        }

        return $area;
    }

    /**
     * Returns the id of an action. If passed action is numeric, it will returned directly.
     * @param   string|int $action
     * @return  int
     */
    public function getIDForAction($action)
    {
        if (is_numeric($action)) {
            return $action;
        } elseif (isset($this->actioncache[$action])) {
            return $this->actioncache[$action];
        }

        $oActionColl = new cApiActionCollection();
        $oActionColl->select("name='" . $oActionColl->escape($action) . "'");
        if ($oItem = $oActionColl->next()) {
            $this->actioncache[$action] = $oItem->get('idaction');
            $action = $oItem->get('idaction');
        }

        return $action;
    }


    /**
     * Loads all permissions of groups where current logged in user is a member
     * and saves them in session.
     *
     * @param   bool  $force  Flag to force loading, event if they were ccached before
     * @return  string  Returns diffrent values, depending on state:
     *                  '1' (string) if permissions couldn't loaded
     *                  '3' (string) if permissions were successfull loaded
     */
    public function load_permissions($force = false)
    {
        global $sess, $area_rights, $item_rights, $auth, $changelang, $changeclient;

        $return = '1';

        // if not admin or sysadmin
        if (!$this->have_perm()) {
            $return = isset($area_rights);

            if (!isset($area_rights) || !isset($item_rights) || isset($changeclient) || isset($changelang) || $force) {
                $return = '3';
                //register variables
                $sess->register('area_rights');
                $sess->register('item_rights');
                $item_rights = array();
                $groups = $this->getGroupsForUser($auth->auth['uid']);

                if (is_array($groups)) {
                    foreach ($groups as $group) {
                        $this->load_permissions_for_user($group);
                    }
                }

                $this->load_permissions_for_user($auth->auth['uid']);
            }
        }

        return $return;
    }


    /**
     * Loads all permissions for a specific user or group.
     * Stores area rights in global variable $area_rights.
     * Stores item rights in global variable $item_rights.
     *
     * @param string $user User Id hash
     */
    public function load_permissions_for_user($user) {
        global $client, $lang;
        global $area_rights, $item_rights;

        $oRightColl = new cApiRightCollection();
        $sWhere = "user_id='" . $oRightColl->escape($user) . "'";
        $sWhere .= " AND idcat=0 AND " . "idclient=" . (int) $client;
        $sWhere .= " AND idlang=" . (int) $lang;
        $oRightColl->select($sWhere);

        // define $area_rights if not already done so
        if (!is_array($area_rights)) {
            $area_rights = array();
        }
        while ($oItem = $oRightColl->next()) {
            $idarea = $oItem->get('idarea');
            $idaction = $oItem->get('idaction');
            $area_rights[$idarea][$idaction] = true;
        }

        // Select Rights for Article and Sructure (Attention Hard code Areas)
        $oAreaColl = new cApiAreaCollection();
        $oAreaColl->select();
        while ($oItem = $oAreaColl->next()) {
            $idarea = $oItem->get('idarea');
            $tmp_area[] = $idarea;
        }

        $tmp_area_string = implode("','", array_values($tmp_area));
        $sWhere = "user_id='" . $oRightColl->escape($user) . "'";
        $sWhere .= " AND idclient=" . (int) $client;
        $sWhere .= " AND idlang=" . (int) $lang;
        $sWhere .= " AND idarea IN ('$tmp_area_string')";
        $sWhere .= "AND idcat != 0";
        $oRightColl->select($sWhere);
        while ($oItem = $oRightColl->next()) {
            $idarea = $oItem->get('idarea');
            $idaction = $oItem->get('idaction');
            $idcat = $oItem->get('idcat');
            $item_rights[$idarea][$idaction][$idcat] = $idcat;
        }
    }

    public function have_perm_area_action_anyitem($area, $action = 0)
    {
        global $item_rights;

        if ($this->have_perm_area_action($area, $action)) {
            return true;
        }

        $area = $this->getIDForArea($area);
        $action = $this->getIDForAction($action);

        return (isset($item_rights[$area][$action]));
    }

    public function have_perm_area_action_item($area, $action, $itemid)
    {
        global $item_rights, $auth, $client, $lang, $cfg;

        if ($this->have_perm()) {
            return true;
        }

        $area = $this->getIDForArea($area);
        $action = $this->getIDForAction($action);

        // If the user has a right on this action in this area check for the items
        if ($this->have_perm_area_action($area, $action)) {
            return true;
        }

        // Check rights for the action in this area at this item
        if (isset($item_rights[$area][$action][$itemid])) {
            // If have action for area + action +item  check right for client and lang
            return true;
        }

        if ($item_rights[$area] != 'noright') {
            $groupsForUser = $this->getGroupsForUser($auth->auth['uid']);
            $groupsForUser[] = $auth->auth['uid'];

            $userIdIn = implode("','", $groupsForUser);

            $oRightsColl = new cApiRightCollection();
            $where = "user_id IN ('" . $userIdIn . "') AND idclient=" . (int) $client
                   . " AND idlang=" . (int) $lang . " AND idarea=$area AND idcat != 0";

            if (!$oRightsColl->select($where)) {
                $item_rights[$area] = 'noright';
                return false;
            }

            while ($oItem = $$oRightsColl->next()) {
                $item_rights[$oItem->get('idarea')][$oItem->get('idaction')][$oItem->get('idcat')] = $oItem->get('idcat');
            }

            // Check
            if (isset($item_rights[$area][$action][$itemid])) {
                // If have action for area + action +item  check right for client and lang
                return true;
            }
        }
        return false;
    }

    public function getParentAreaId($area)
    {
        $oAreaColl = new cApiAreaCollection();
        return $oAreaColl->getParentAreaID($area);
    }

    public function have_perm_area_action($area, $action = 0)
    {
        global $area_rights, $client, $lang, $cfg;

        $area = $this->getIDForArea($area);
        $action = $this->getIDForAction($action);

        if ($action == 0) {
            $area = $this->getParentAreaId($area);
        }

        $area = $this->getIDForArea($area);

        if (!$this->have_perm()) {
            if ($action == 0 && $area_rights[$area]) {
                // If have action for area + action check right for client and lang
                return ($this->have_perm_client_lang($client, $lang));
            }

            // check rights for the action in this area
            if ($area_rights[$area][$action]) {
                // If have action for area + action check right for client and lang
                return $this->have_perm_client_lang($client, $lang);
            }

            return false;
        }
        return true;
    }

    public function have_perm_client_lang($client, $lang)
    {
        global $auth;

        // Changed back to a full featured function, as have_perm
        // needs $client as global variable - not provided by this
        // function
        //return ($this->have_perm("client[$client],lang[$lang]"));

        if (!isset($auth->auth['perm'])) {
            $auth->auth['perm'] = '';
        }

        // Split the permissions of the user
        $userperm = explode(',', $auth->auth['perm']);

        if (in_array('sysadmin', $userperm)) {
            return true; // User is sysadmin
        } elseif (in_array("admin[$client]", $userperm)) {
            return true; // User is admin
        } else {
            // Check rights for the client and the language
            $pageperm = explode(',', "client[$client],lang[$lang]");
            foreach ($pageperm as $value) {
                if (!in_array($value, $userperm)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Checks if a user has access rights for a specific client.
     *
     * @param   int     $iClient   idclient to check, or false for the current client
     * @param   object  $oUser     User object to check against, or false for the current user
     * @return  bool
     */
    public function hasClientPermission($iClient = false, $oUser = false)
    {
        global $auth, $client;

        if ($iClient === false) {
            $iClient = $client;
        }

        $oUser = $this->_checkUserObject($oUser);

        if ($this->isSysadmin($oUser) || $this->isClientAdmin($iClient, $oUser) || $this->isClientUser($iClient, $oUser)) {
            return true;
        } else {
            return false;
        }
        /* Commented out Timo Trautmann, because here only client access is checked, possibility for admin or sysadmin access was ignored
           functions isSysadmin isClientAdmin isClientUser also handles permission for groups
            #Check clients' rights of users' group(s)
            $aGroups = $this->getGroupsForUser($auth->auth["uid"]);
            if (is_array($aGroups))
            {
                foreach ($aGroups as $group)
                {
                    $oGroup = new cApiGroup($group);
                    if ($this->isClientGroup($iClient, $oGroup))
                    {
                        return true;
                    }
                }
            }

            return false;
        }*/
    }


    /**
     * Checks if the given user has access permission for a client
     *
     * @param   int     $iClient  idclient to check
     * @param   object  $oUser    User object to check against
     * @return  bool
     */
    public function isClientUser($iClient, $oUser)
    {
        $oUser = $this->_checkUserObject($oUser);

        $aPermissions = explode(',', $oUser->getEffectiveUserPerms());

        if (in_array("client[$iClient]", $aPermissions)) {
            return true;
        }

        return false;
    }

    /**
     * Checks if the given group has access permission for a client
     *
     * @param   int     $iClient  idclient to check
     * @param   object  $oGroup   Group object to check against
     * @return  bool
     */
    public function isClientGroup($iClient, $oGroup)
    {
        $aPermissions = explode(',', $oGroup->getField('perms'));

        if (in_array("client[$iClient]", $aPermissions)) {
            return true;
        }

        return false;
    }

    /**
     * Checks if the given user has an admin permission
     *
     * @param   integer  $iClient  idclient to check
     * @param   object   $oUser    User object to check against
     * @return  bool
     */
    public function isClientAdmin($iClient, $oUser)
    {
        $oUser = $this->_checkUserObject($oUser);

        $aPermissions = explode(',', $oUser->getEffectiveUserPerms());

        if (in_array("admin[$iClient]", $aPermissions)) {
            return true;
        }

        return false;
    }

    /**
     * Checks if the given user has sysadmin permission
     *
     * @param object $oUser    User object to check against
     */
    public function isSysadmin($oUser)
    {
        $oUser = $this->_checkUserObject($oUser);

        $aPermissions = explode(',', $oUser->getEffectiveUserPerms());

        if (in_array('sysadmin', $aPermissions)) {
            return true;
        }

        return false;
    }

    /**
     * Checks if the given object is a user object.
     *
     * If oUser is false, initialize the object from the currently logged in user. If oUser is not
     * a object of the class User, issue a warning.
     *
     * @param   object  $oUser  User object
     * @return  object
     */
    private function _checkUserObject($oUser)
    {
        if ($oUser === false) {
            global $currentuser;
            $oUser = $currentuser;
        }

        if (!is_object($oUser)) {
            global $auth;
            $oUser = new cApiUser($auth->auth['uid']);
        }

        if (get_class($oUser) != 'cApiUser') {
            cWarning(__FILE__, __LINE__, 'oUser parameter is not of type User');
        }

        return $oUser;
    }

    public function have_perm_client($p = 'x')
    {
        global $auth, $client;

        if (!isset ($auth->auth['perm'])) {
            $auth->auth['perm'] = '';
        }

        // Split the permissions of the user
        $userperm = explode(',', $auth->auth['perm']);

        // If User is sysadmin or admin at this client return true
        if (in_array('sysadmin', $userperm)) {
            return true;
        }

        // If there are more permissions to ask split them
        $pageperm = explode(',', $p);
        foreach ($pageperm as $value) {
            if (!in_array($value, $userperm)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Checks if user has permissions tp passed perm.
     * - Sysadmin has allways permission
     * - Client admin has allways permission
     *
     * @param   string  $p  Permissions (comma separated list of perms) to check
     * @return  bool
     */
    public function have_perm($p = 'x')
    {
        global $auth, $client;

        if (!isset($auth->auth['perm'])) {
            $auth->auth['perm'] = '';
        }

        // Split the permissions of the user
        $userperm = explode(',', $auth->auth['perm']);

        // If User is sysadmin or admin at this client return true
        if (in_array('sysadmin', $userperm)) {
            return true;
        } elseif (in_array("admin[$client]", $userperm)) {
            return true;
            // Else check rights for the client and the language
        } else {
            // If there are more permissions to ask split them
            $pageperm = explode(',', $p);
            foreach ($pageperm as $value) {
                if (!in_array($value, $userperm)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Checks if an item have any perms
     *
     * @param   string|int  $mainarea
     * @param   int  $itemid
     * @return  bool
     */
    public function have_perm_item($mainarea, $itemid)
    {
        global $cfg, $item_rights, $cfg, $client, $lang, $auth, $area_tree, $sess;

        $mainarea = $this->getIDForArea($mainarea);

        // If is admin or sysadmin
        if ($this->have_perm()) {
            return true;
        }

        // If is not admin or sysadmin

        if (!is_object($this->db)) {
            $this->db = cRegistry::getDb();
        }

        $this->showareas($mainarea);

        $flg = false;
        // Check if there are any rights for this areas
        foreach ($area_tree[$mainarea] as $value) {
            // If the flag noright is set there are no rights in this area
            if ($item_rights[$value] == 'noright') {
                continue;
            } elseif (is_array($item_rights[$value])) {
                // If there are any rights
                foreach ($item_rights[$value] as $value2) {
                    if (in_array($itemid, $value2)) {
                        return true;
                    }
                }
            } elseif ($item_rights[$value] != 'noright') {
                $groupsForUser = $this->getGroupsForUser($auth->auth[uid]);
                $groupsForUser[] = $auth->auth[uid];

                //else search for rights for this user in this area
                $sql = "SELECT
                            *
                         FROM
                            ".$cfg['tab']['rights']."
                         WHERE
                            user_id IN ('".implode("','", $groupsForUser)."') AND
                            idclient = ". cSecurity::toInteger($client)." AND
                            idlang = ". cSecurity::toInteger($lang)." AND
                            idarea = '$value' AND
                            idcat != 0";
                $this->db->query($sql);

                // If there are no rights for this area set the flag norights
                if ($this->db->affected_rows() == 0) {
                    $item_rights[$value] = 'noright';
                }

                // Set the rights
                while ($this->db->next_record()) {
                    if ($this->db->f('idcat') == $itemid) {
                        $flg = true;
                    }
                    $item_rights[$this->db->f('idarea')][$this->db->f('idaction')][$this->db->f('idcat')] = $this->db->f('idcat');
                }
            }
        }
        return $flg;
    }

    public function showareas($mainarea)
    {
        global $area_tree, $sess, $perm, $cfg;

        if (!is_object($this->db)) {
            $this->db = cRegistry::getDb();
        }

        $mainarea = $this->getIDForArea($mainarea);

        // If $area_tree for this area is not register
        if (!isset($area_tree[$mainarea])) {
            $sess->register('area_tree');

            // parent_id uses the name not the idarea
            $sql = "SELECT name FROM " . $cfg['tab']['area'] . " WHERE idarea=$mainarea";
            $this->db->query($sql);
            $this->db->next_record();
            $name = $this->db->f('name');

            // Check which subareas are there and write them in the array
            $sql = "SELECT idarea FROM " . $cfg['tab']['area'] . " WHERE parent_id='$name' OR idarea=$mainarea";
            $this->db->query($sql);
            $area_tree[$mainarea] = array();
            while ($this->db->next_record()) {
                $area_tree[$mainarea][] = $this->db->f('idarea');
            }
        }
        return $mainarea;
    }
}

?>