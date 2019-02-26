<?php

/**
 * This file contains the backend authentication handler class.
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
 * This class is the backend authentication handler for CONTENIDO.
 *
 * @package    Core
 * @subpackage Authentication
 */
class cAuthHandlerBackend extends cAuthHandlerAbstract {

    /**
     * Constructor to create an instance of this class.
     *
     * Automatically sets the lifetime of the authentication to the
     * configured value.
     */
    public function __construct() {
        $cfg = cRegistry::getConfig();
        $this->_lifetime = (int) $cfg['backend']['timeout'];
        if ($this->_lifetime == 0) {
            $this->_lifetime = 15;
        }
    }

    /**
     * Handle the pre authentication.
     *
     * There is no pre authentication in backend so false is returned.
     *
     * @see cAuthHandlerAbstract::preAuthorize()
     * @return false
     */
    public function preAuthorize() {
        return false;
    }

    /**
     * Display the login form.
     * Includes a file which displays the login form.
     *
     * @see cAuthHandlerAbstract::displayLoginForm()
     * 
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function displayLoginForm() {
        // @TODO  We need a better solution for this.
        //        One idea could be to set the request/response type in
        //        global $cfg array instead of checking $_REQUEST['ajax']
        //        everywhere...
        if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] != '') {
            $oAjax = new cAjaxRequest();
            $sReturn = $oAjax->handle('authentication_fail');
            echo $sReturn;
        } else {
            include(cRegistry::getBackendPath() . 'main.loginform.php');
        }
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
        $username = isset($_POST['username']) ? $_POST['username'] : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $formtimestamp = isset($_POST['formtimestamp']) ? $_POST['formtimestamp'] : '';

        // add slashes if they are not automatically added
        if (cRegistry::getConfigValue('simulate_magic_quotes') !== true) {
            // backward compatiblity of passwords
            $password = addslashes($password);
            // avoid sql injection in query by username on cApiUserCollection select string
            $username = addslashes($username);
        }

        $groupPerm = array();

        if ($password == '') {
            return false;
        }

        if (($formtimestamp + (60 * 15)) < time()) {
            return false;
        }

        if (isset($username)) {
            $this->auth['uname'] = $username;
        } else if ($this->_defaultNobody == true) {
            $uid = $this->auth['uname'] = $this->auth['uid'] = self::AUTH_UID_NOBODY;

            return $uid;
        }

        $uid = false;
        $perm = false;
        $pass = false;
        $salt = false;

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
            $salt = $item->get("salt");
        }

        if ($uid == false || hash("sha256", md5($password) . $salt) != $pass) {
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

    /**
     * Log the successful authentication.
     *
     * @see cAuthHandlerAbstract::logSuccessfulAuth()
     * 
     * @throws cDbException
     * @throws cException
     */
    public function logSuccessfulAuth() {
        global $client, $lang, $saveLoginTime;

        $perm = new cPermission();

        // Find the first accessible client and language for the user
        $clientLangColl = new cApiClientLanguageCollection();
        $clientLangColl->select();

        $bFound = false;
        while ($bFound == false) {
            if (($item = $clientLangColl->next()) === false) {
                break;
            }

            $iTmpClient = $item->get('idclient');
            $iTmpLang = $item->get('idlang');

            if ($perm->have_perm_client_lang($iTmpClient, $iTmpLang)) {
                $client = $iTmpClient;
                $lang = $iTmpLang;
                $bFound = true;
            }
        }

        if (!is_numeric($client) || !is_numeric($lang)) {
            return;
        }

        $idaction = $perm->getIDForAction('login');

        $authInfo = $this->getAuthInfo();
        $uid = $authInfo['uid'];

        // create a actionlog entry
        $actionLogCol = new cApiActionlogCollection();
        $actionLogCol->create($uid, $client, $lang, $idaction, 0);

        $sess = cRegistry::getSession();
        $sess->register('saveLoginTime');
        $saveLoginTime = true;
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

            return $user->get('user_id') != '';
        } else {
            return false;
        }
    }

}
