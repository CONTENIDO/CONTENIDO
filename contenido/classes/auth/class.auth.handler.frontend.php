<?php
/**
 * This file contains the frontend authentication handler class.
 *
 * @package            Core
 * @subpackage        Authentication
 * @version            1.0
 *
 * @author            Dominik Ziegler
 * @copyright        four for business AG <www.4fb.de>
 * @license            http://www.contenido.org/license/LIZENZ.txt
 * @link            http://www.4fb.de
 * @link            http://www.contenido.org
 */

/**
 * @package            Core
 * @subpackage        Authentication
 *
 * This class contains the methods for the frontend authentication in CONTENIDO.
 */
class cAuthHandlerFrontend extends cAuthHandlerAbstract {
    public function preAuthorize() {
        $password = $_POST['password'];

        if ($password == '') {
            // Stay as nobody when an empty password is passed
            $uid = $this->auth['uname'] = $this->auth['uid'] = self::AUTH_UID_NOBODY;
            return $uid;
        }

        return $this->validateCredentials();
    }

    public function displayLoginForm() {
        include(cRegistry::getFrontendPath() . 'front_crcloginform.inc.php');
    }

    public function validateCredentials() {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $groupPerm = array();

        if ($password == '') {
            return false;
        }

        if (isset($username)) {
            $this->auth['uname'] = $username;
        } elseif ($this->_defaultNobody == true) {
            $uid = $this->auth['uname'] = $this->auth['uid'] = self::AUTH_UID_NOBODY;
            return $uid;
        }

        $uid = false;
        $perm = false;
        $pass = false;

        $client = cRegistry::getClientId();

        $frontendUserColl = new cApiFrontendUserCollection();
        $where = "username = '" . $username . "' AND idclient='" . $client . "' AND active=1";
        $frontendUserColl->select($where);

        while ($item = $frontendUserColl->next()) {
            $uid = $item->get('idfrontenduser');
            $perm = 'frontend';
            $pass = $item->get('password');
        }

        if ($uid == false) {
            $userColl = new cApiUserCollection();
            $where = "username = '" . $username . "'";
            $where .= " AND (valid_from <= NOW() OR valid_from = '0000-00-00' OR valid_from is NULL)";
            $where .= " AND (valid_to >= NOW() OR valid_to = '0000-00-00' OR valid_to is NULL)";

            $maintenanceMode = getSystemProperty('maintenance', 'mode');
            if ($maintenanceMode == 'enabled') {
                $where .= " AND perms = 'sysadmin'";
            }

            $userColl->select($where);

            while ($item = $userColl->next()) {
                $uid = $item->get('user_id');
                $perm = $item->get('perms');
                $pass = $item->get('password'); // Password is stored as a md5 hash
            }
        }

        if ($uid == false || md5($password) != $pass) {
            // No user found, sleep and exit
            sleep(5);
            return false;
        }

        if ($perm != '') {
            $groupPerm[] = $perm;
        }

        $groupColl = new cApiGroupCollection();
        $groups = $groupColl->fetchByUserID($uid);
        foreach ($groups as $group) {
            $groupPerm[] = $group->get('perms');
        }

        $perm = implode(',', $groupPerm);

        $this->auth['perm'] = $perm;
        return $uid;
    }

    public function logSuccessfulAuth() {
        return;
    }
}

/**
 * @deprecated    2012-09-22
 */
class Contenido_Frontend_Challenge_Crypt_Auth extends cAuthHandlerFrontend {
}