<?php

/**
 * This file contains the frontend authentication handler class.
 *
 * @package    Core
 * @subpackage Authentication
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class is the frontend authentication handler for CONTENIDO.
 *
 * @package    Core
 * @subpackage Authentication
 */
class cAuthHandlerFrontend extends cAuthHandlerAbstract {

    /**
     *
     * @var bool
     */
    protected $_defaultNobody = true;

    /**
     * Constructor to create an instance of this class.
     *
     * Automatically sets the lifetime of the authentication to the
     * configured value.
     */
    public function __construct() {
        $cfg = cRegistry::getConfig();
        $this->_lifetime = (int) $cfg['frontend']['timeout'];
        if ($this->_lifetime == 0) {
            $this->_lifetime = 15;
        }
    }

    /**
     * Handle the pre authorization.
     * Returns a valid user ID to be set before the login form is handled,
     * otherwise false.
     *
     * @see cAuthHandlerAbstract::preAuthorize()
     *
     * @return string|false
     *
     * @throws cDbException
     * @throws cException
     */
    public function preAuthorize() {
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if ($password == '') {
            // Stay as nobody when an empty password is passed
            $this->auth['uname'] = $this->auth['uid'] = self::AUTH_UID_NOBODY;

            return false;
        }

        return $this->validateCredentials();
    }

    /**
     * Display the login form.
     * Includes a file which displays the login form.
     *
     * @see cAuthHandlerAbstract::displayLoginForm()
     */
    public function displayLoginForm() {
        include(cRegistry::getFrontendPath() . 'front_crcloginform.inc.php');
    }

    /**
     * Validate the credentials.
     *
     * Validate the users input against source and return a valid user
     * ID or false.
     *
     * @see cAuthHandlerAbstract::validateCredentials()
     *
     * @return string|false
     *
     * @throws cDbException
     * @throws cException
     */
    public function validateCredentials() {
		$frontendUserColl = new cApiFrontendUserCollection();
		
        $username = $frontendUserColl->escape(stripslashes(trim($_POST['username'])));
        $password = $_POST['password'];

        $groupPerm = array();

        if (isset($username)) {
            $this->auth['uname'] = $username;
        } elseif ($this->_defaultNobody == true) {
            $uid = $this->auth['uname'] = $this->auth['uid'] = self::AUTH_UID_NOBODY;

            return $uid;
        }

        if ($password == '') {
            return false;
        }

        $uid = false;
        $perm = false;
        $pass = false;
        $salt = false;

        $client = cRegistry::getClientId();

        $where = "username = '" . $username . "' AND idclient='" . $client . "' AND active=1";
        $frontendUserColl->select($where);

        while (($item = $frontendUserColl->next()) !== false) {
            $uid = $item->get('idfrontenduser');
            $perm = 'frontend';
            $pass = $item->get('password');
            $salt = $item->get('salt');
        }

        if ($uid == false) {
            $userColl = new cApiUserCollection();
            $where = "username = '" . $username . "'";
            $where .= " AND (valid_from <= NOW() OR valid_from = '0000-00-00 00:00:00' OR valid_from is NULL)";
            $where .= " AND (valid_to >= NOW() OR valid_to = '0000-00-00 00:00:00' OR valid_to is NULL)";

            $maintenanceMode = getSystemProperty('maintenance', 'mode');
            if ($maintenanceMode == 'enabled') {
                $where .= " AND perms = 'sysadmin'";
            }

            $userColl->select($where);

            while (($item = $userColl->next()) !== false) {
                $uid = $item->get('user_id');
                $perm = $item->get('perms');
                // password is stored as a sha256 hash
                $pass = $item->get('password');
                $salt = $item->get('salt');
            }
        }

        if ($uid == false || hash("sha256", md5($password) . $salt) != $pass) {
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

    /**
     * Log the successful authentication.
     *
     * Frontend logins won't be logged.
     *
     * @see cAuthHandlerAbstract::logSuccessfulAuth()
     */
    public function logSuccessfulAuth() {
        return;
    }

    /**
     * Returns true if a user is logged in.
     *
     * @see cAuthHandlerAbstract::isLoggedIn()
     * @return bool
     */
    public function isLoggedIn() {
        $authInfo = $this->getAuthInfo();

        if(isset($authInfo['uid'])) {
            $user = new cApiUser($authInfo['uid']);
            $frontendUser = new cApiFrontendUser($authInfo['uid']);

            return $user->get('user_id') != '' || $frontendUser->get('idfrontenduser') != '';
        } else {
            return false;
        }
    }
}
