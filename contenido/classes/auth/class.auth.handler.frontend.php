<?php
/**
 * This file contains the frontend authentication handler class.
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
 * This class contains the methods for the frontend authentication in CONTENIDO.
 *
 * @package    Core
 * @subpackage Authentication
 */
class cAuthHandlerFrontend extends cAuthHandlerAbstract {
    protected $_defaultNobody = true;

    public function __construct() {
    	$cfg = cRegistry::getConfig();
    	$this->_lifetime = (int)$cfg['frontend']['timeout'];

    	if ($this->_lifetime == 0) {
    		$this->_lifetime = 15;
    	}
    }

    public function preAuthorize() {
        $password = $_POST['password'];

        if ($password == '') {
            // Stay as nobody when an empty password is passed
            $this->auth['uname'] = $this->auth['uid'] = self::AUTH_UID_NOBODY;

            return false;
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

        $frontendUserColl = new cApiFrontendUserCollection();
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
            $where .= " AND (valid_from <= NOW() OR valid_from = '0000-00-00' OR valid_from is NULL)";
            $where .= " AND (valid_to >= NOW() OR valid_to = '0000-00-00' OR valid_to is NULL)";

            $maintenanceMode = getSystemProperty('maintenance', 'mode');
            if ($maintenanceMode == 'enabled') {
                $where .= " AND perms = 'sysadmin'";
            }

            $userColl->select($where);

            while (($item = $userColl->next()) !== false) {
                $uid = $item->get('user_id');
                $perm = $item->get('perms');
                $pass = $item->get('password'); // Password is stored as a sha256 hash
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

    public function logSuccessfulAuth() {
        return;
    }

}
