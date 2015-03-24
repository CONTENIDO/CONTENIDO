<?php
/**
 * This file contains the backend authentication handler class.
 *
 * @package    Core
 * @subpackage Authentication
 * @version    SVN Revision $Rev:$
 *
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains the methods for the backend authentication in CONTENIDO.
 *
 * @package    Core
 * @subpackage Authentication
 */
class cAuthHandlerBackend extends cAuthHandlerAbstract {

    /**
     * Constructor of the backend auth handler.
     * Automatically sets the lifetime of the authentication to the configured
     * value.
     */
    public function __construct() {
        $cfg = cRegistry::getConfig();
        $this->_lifetime = (int)$cfg['backend']['timeout'];

        if ($this->_lifetime == 0) {
            $this->_lifetime = 15;
        }
    }

    public function preAuthorize() {
        // there is no pre authorization in backend
        return false;
    }

    public function displayLoginForm() {
        // @TODO  We need a better solution for this. One idea could be to set the request/response
        //        type in global $cfg array instead of checking $_REQUEST['ajax'] everywhere...
        if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] != '') {
            $oAjax = new cAjaxRequest();
            $sReturn = $oAjax->handle('authentication_fail');
            echo $sReturn;
        } else {
            include(cRegistry::getBackendPath() . 'main.loginform.php');
        }
    }

    public function validateCredentials() {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $formtimestamp = $_POST['formtimestamp'];

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
            $pass = $item->get('password'); // Password is stored as a sha256 hash
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
