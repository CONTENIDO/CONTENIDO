<?php

/**
 * This file contains the system property collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @author Bjoern Behrens
 * @author Holger Librenz
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * User collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiUserCollection extends ItemCollection {
    /**
     * Constructor to create an instance of this class.
     *
     * @param string|bool $where [optional]
     *                           The where clause in the select, usable to run select by creating
     *                           the instance
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     * @global array      $cfg
     */
    public function __construct($where = false) {
        global $cfg;
        parent::__construct($cfg['tab']['user'], 'user_id');
        $this->_setItemClass('cApiUser');
        if ($where !== false) {
            $this->select($where);
        }
    }

    /**
     * Createa a user by user name.
     *
     * @param string $username
     *
     * @return cApiUser|bool
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function create($username) {
        $primaryKeyValue = md5($username);

        $item = $this->createNewItem($primaryKeyValue);
        if ($item->usernameExists($username)) {
            return false;
        }

        $item->set('username', $username);
        $item->set('salt', md5($username . rand(1000, 9999) . rand(1000, 9999) . rand(1000, 9999)));
        $item->store();

        return $item;
    }

    /**
     * Removes the specified user from the database by users name.
     *
     * @param string $username
     *         Specifies the username
     *
     * @return bool
     *         True if the delete was successful
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function deleteUserByUsername($username) {
        $result = $this->deleteBy('username', $username);
        return ($result > 0) ? true : false;
    }

    /**
     * Returns all users which are accessible by the current user.
     *
     * @param array  $perms
     *                              Permissions array
     * @param bool   $includeAdmins [optional]
     *                              Flag to get admins (admin and sysadmin) too
     * @param string $orderBy       [optional]
     *                              Order by rule, uses 'realname, username' by default
     * @return array
     *                              Array of user objects
     * @throws cDbException
     * @throws cException
     */
    public function fetchAccessibleUsers($perms, $includeAdmins = false, $orderBy = '') {
        $users = array();
        $limit = array();
        $where = '';

        if (!in_array('sysadmin', $perms)) {
            // not sysadmin, compose where rules
            $clientColl = new cApiClientCollection();
            $allClients = $clientColl->getAvailableClients();

            foreach ($allClients as $key => $value) {
                if (in_array('client[' . $key . ']', $perms) || in_array('admin[' . $key . ']', $perms)) {
                    $limit[] = "perms LIKE '%client[" . $this->escape($key) . "]%'";
                    if ($includeAdmins) {
                        $limit[] = "perms LIKE '%admin[" . $this->escape($key) . "]%'";
                    }
                }
                if (in_array('admin[' . $key . ']', $perms)) {
                    $limit[] = "perms LIKE '%admin[" . $key . "]%'";
                }
            }

            if ($includeAdmins) {
                $limit[] = "perms LIKE '%sysadmin%'";
            }

            if (count($limit) > 0) {
                $where = '1 AND ' . implode(' OR ', $limit);
            }
        }

        if (empty($orderBy)) {
            $orderBy = 'realname, username';
        }

        $this->select($where, '', $this->escape($orderBy));
        while (($oItem = $this->next()) !== false) {
            $users[] = clone $oItem;
        }

        return $users;
    }

    /**
     * Returns all users which are accessible by the current user.
     * Is a wrapper of fetchAccessibleUsers() and returns contrary to that
     * function
     * a multidimensional array instead of a list of objects.
     *
     * @param array  $perms
     *                              Permissions array
     * @param bool   $includeAdmins [optional]
     *                              Flag to get admins (admin and sysadmin) too
     * @param string $orderBy       [optional]
     *                              Order by rule, uses 'realname, username' by default
     *
     * @return array
     *         Array of user like $arr[user_id][username], $arr[user_id][realname]
     * @throws cDbException
     * @throws cException
     */
    public function getAccessibleUsers($perms, $includeAdmins = false, $orderBy = '') {
        $users = array();
        $oUsers = $this->fetchAccessibleUsers($perms, $includeAdmins, $orderBy);
        foreach ($oUsers as $oItem) {
            $users[$oItem->get('user_id')] = array(
                'username' => $oItem->get('username'),
                'realname' => $oItem->get('realname')
            );
        }
        return $users;
    }

    /**
     * Returns all users available in the system
     *
     * @param string $orderBy [optional]
     *                        SQL order by part
     * @return array
     * @throws cDbException
     * @throws cException
     */
    public function fetchAvailableUsers($orderBy = 'realname ASC') {
        $users = array();

        $this->select('', '', $this->escape($orderBy));
        while (($oItem = $this->next()) !== false) {
            $users[] = clone $oItem;
        }

        return $users;
    }

    /**
     * Returns all system admins available in the system
     *
     * @param bool $forceActive [optional]
     *                          flag if only active sysadmins should be returned
     * @return array
     *                          Array of user objects
     * @throws cDbException
     * @throws cException
     */
    public function fetchSystemAdmins($forceActive = false) {
        $users = array();

        $where = "perms LIKE '%sysadmin%'";
        if ($forceActive === true) {
            $where .= " AND (valid_from <= NOW() OR valid_from = '0000-00-00 00:00:00')" . " AND (valid_to >= NOW() OR valid_to = '0000-00-00 00:00:00')";
        }

        $this->select($where);
        while (($item = $this->next()) !== false) {
            $users[] = clone $item;
        }

        return $users;
    }

    /**
     * Returns all system admins available in the system
     *
     * @param int $client
     * @return array
     *         Array of user objects
     * @throws cDbException
     * @throws cException
     */
    public function fetchClientAdmins($client) {
        $client = (int) $client;
        $users = array();

        $where = "perms LIKE '%admin[" . $client . "]%'";

        $this->select($where);
        while (($item = $this->next()) !== false) {
            $users[] = clone $item;
        }

        return $users;
    }
}

/**
 * User item
 *
 * In current version you can administer optional password checks
 * via following configuration values:
 *
 * - En- or disabling checks:
 * $cfg['password']['check_password_mask'] = [true|false]
 * Use this flag to enable (true) or disable (false) the mask checks.
 *
 * $cfg['password']['use_cracklib'] = [true|false]
 * Use this to enable (true) or disable (false) the strength check, currently
 * done with cracklib.
 *
 * - Mask checks:
 * Password mask checks are checks belonging to the "format" of the needed
 * password string.
 *
 * $cfg['password']['min_length'], int
 * Minimum length a password has to have. If not set, 8 chars are set as default
 * $cfg['password']['numbers_mandatory'], int
 * If set to a value greater than 0, at least
 * $cfg['password']['numbers_mandatory'] numbers
 * must be in password
 * $cfg['password']['symbols_mandatory'], int &&
 * $cfg['password']['symbols_regex'], String
 * If 'symbols_mandatory' set to a value greater than 0, at least so many
 * symbols has to appear in
 * given password. What symbols are regcognized can be administrated via
 * 'symbols_regex'. This has
 * to be a regular expression which is used to "find" the symbols in $password.
 * If not set, following
 * RegEx is used: "/[|!@#$%&*\/=?,;.:\-_+~^¨\\\]/"
 * $cfg['password']['mixed_case_mandatory'], int
 * If set to a value greater than 0 so many lower and upper case character must
 * appear in the password.
 * (e.g.: if set to 2, 2 upper and 2 lower case characters must appear)
 *
 * - Strength check
 * Passwords should have some special characteristics to be a strong, i.e. not
 * easy to guess, password. Currently
 * cracklib is supported. These are the configuration possibilities:
 *
 * $cfg['password']['cracklib_dict'], string
 * Path and file name (without file extension!) to dictionary you want to use.
 * This setting is
 * mandatory!
 *
 * Keep in mind that these type of check only works if crack module is
 * available.
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiUser extends Item {

    /**
     * Password is ok and stored.
     *
     * @var int
     *
     */
    const PASS_OK = 0;

    /**
     * Given password is to short
     *
     * @var int
     *
     */
    const PASS_TO_SHORT = 1;

    /**
     * Given password is not strong enough
     *
     * @var int
     *
     */
    const PASS_NOT_STRONG = 2;

    /**
     * Given password is not complex enough
     *
     * @var int
     *
     */
    const PASS_NOT_COMPLEX = 3;

    /**
     * Password does not contain enough numbers.
     *
     * @var int
     *
     */
    const PASS_NOT_ENOUGH_NUMBERS = 4;

    /**
     * Password does not contain enough symbols.
     *
     * @var int
     */
    const PASS_NOT_ENOUGH_SYMBOLS = 5;

    /**
     * Password does not contain enough mixed characters.
     *
     * @var int
     *
     */
    const PASS_NOT_ENOUGH_MIXED_CHARS = 6;

    /**
     * Password does not contain enough different characters.
     *
     * @var int
     *
     */
    const PASS_NOT_ENOUGH_DIFFERENT_CHARS = 7;

    /**
     * Exception code, which is used if you try to add an user
     * that already exists.
     *
     * @var int
     *
     */
    const EXCEPTION_USERNAME_EXISTS = 8;

    /**
     * Exception code, which is used if an password is set to save
     * that is not valid.
     *
     * @var int
     *
     */
    const EXCEPTION_PASSWORD_INVALID = 9;

    /**
     * This value will be used if no minimum length
     * for passwords are set via $cfg['password']['min_length']
     *
     * @var int
     *
     */
    const MIN_PASS_LENGTH_DEFAULT = 8;

    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $mId [optional]
     *                   Specifies the ID of item to load
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['user'], 'user_id');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Loads a user from the database by its userID.
     *
     * @param string $userId
     *         Specifies the userID
     * @return bool
     *         True if the load was successful
     *
     * @throws cDbException
     * @throws cException
     */
    public function loadUserByUserID($userId) {
        return $this->loadByPrimaryKey($userId);
    }

    /**
     * Loads a user entry by username.
     *
     * @param string $userName
     *         Specifies the username
     * @return bool
     *         True if the load was successful
     *
     * @throws cDbException
     * @throws cException
     */
    public function loadUserByUsername($userName) {
        return $this->loadBy('username', $userName);
    }

    /**
     * Checks if a user with the id $userId exists
     *
     * @param string $userId
     *
     * @return bool
     *         user exists or not
     *
     * @throws cDbException
     * @throws cException
     */
    public static function userExists($userId) {
        $test = new cApiUser();

        return $test->loadByPrimaryKey($userId);
    }

    /**
     * Checks if a username exists
     *
     * @param string $username
     *         the name
     * @return bool
     *         username exists or not
     *
     * @throws cDbException
     * @throws cException
     */
    public static function usernameExists($username) {
        $user = new cApiUser();
        return $user->loadBy('username', $username);
    }

    /**
     * Checks a given password against some predefined rules like minimum
     * character
     * length, required special character, etc...
     * This behaviour is configurable in global configuration $cfg['password'].
     *
     * @param string $password
     *         The password check
     * @return int
     *         One of defined PASS_* constants (PASS_OK if everything was ok)
     */
    public static function checkPasswordMask($password) {
        global $cfg;

        $iResult = self::PASS_OK;

        $cfgPw = $cfg['password'];

        if (!isset($cfgPw['check_password_mask']) || $cfgPw['check_password_mask'] == false) {
            // no or disabled password check configuration
            return $iResult;
        }

        // any min length in config set?
        $iMinLength = self::MIN_PASS_LENGTH_DEFAULT;
        if (isset($cfgPw['min_length'])) {
            $iMinLength = (int) $cfgPw['min_length'];
        }

        // check length...
        if (cString::getStringLength($password) < $iMinLength) {
            $iResult = self::PASS_TO_SHORT;
        }

        // check password elements
        // numbers.....
        if ($iResult == self::PASS_OK && isset($cfgPw['numbers_mandatory']) && (int) $cfgPw['numbers_mandatory'] > 0) {

            $aNumbersInPassword = array();
            preg_match_all('/[0-9]/', $password, $aNumbersInPassword);

            if (count($aNumbersInPassword[0]) < (int) $cfgPw['numbers_mandatory']) {
                $iResult = self::PASS_NOT_ENOUGH_NUMBERS;
            }
        }

        // symbols....
        if ($iResult == self::PASS_OK && isset($cfgPw['symbols_mandatory']) && (int) $cfgPw['symbols_mandatory'] > 0) {

            $aSymbols = array();
            $sSymbolsDefault = "/[|!@#$%&*\/=?,;.:\-_+~^¨\\\]/";
            if (isset($cfgPw['symbols_regex']) && !empty($cfgPw['symbols_regex'])) {
                $sSymbolsDefault = $cfgPw['symbols_regex'];
            }

            preg_match_all($sSymbolsDefault, $password, $aSymbols);

            if (count($aSymbols[0]) < (int) $cfgPw['symbols_mandatory']) {
                $iResult = self::PASS_NOT_ENOUGH_SYMBOLS;
            }
        }

        // mixed case??
        if ($iResult == self::PASS_OK && isset($cfgPw['mixed_case_mandatory']) && (int) $cfgPw['mixed_case_mandatory'] > 0) {

            $aLowerCaseChars = array();
            $aUpperCaseChars = array();

            preg_match_all('/[a-z]/', $password, $aLowerCaseChars);
            preg_match_all('/[A-Z]/', $password, $aUpperCaseChars);

            if ((count($aLowerCaseChars[0]) < (int) $cfgPw['mixed_case_mandatory']) || (count($aUpperCaseChars[0]) < (int) $cfgPw['mixed_case_mandatory'])) {
                $iResult = self::PASS_NOT_ENOUGH_MIXED_CHARS;
            }
        }

        return $iResult;
    }

    /**
     * Encodes a passed password (uses md5 to generate a hash of it).
     *
     * @param string $password
     *         The password to encode
     * @return string
     *         Encoded password
     */
    public function encodePassword($password) {
        return hash("sha256", md5($password) . $this->get("salt"));
    }

    /**
     * User defined field value setter.
     *
     * @see Item::setField()
     * @param string $sField
     *         Field name
     * @param string $mValue
     *         Value to set
     * @param bool $bSafe [optional]
     *         Flag to run defined inFilter on passed value
     * @return bool
     */
    public function setField($sField, $mValue, $bSafe = true) {
        if ('perms' === $sField) {
            if (is_array($mValue)) {
                $mValue = implode(',', $mValue);
            }
        }

        return parent::setField($sField, $mValue, $bSafe);
    }

    /**
     * Returns user id, currently set.
     *
     * @return string
     */
    public function getUserId() {
        return $this->get('user_id');
    }

    /**
     * User id settter.
     * NOTE: Setting the user id by this method will load the user model.
     *
     * @param string $uid
     *
     * @throws cDbException
     * @throws cException
     */
    public function setUserId($uid) {
        $this->loadByPrimaryKey($uid);
    }

    /**
     * Checks password which has to be set and return PASS_* values (i.e.
     * on success PASS_OK).
     *
     * @param string $password
     * @return int
     */
    public function setPassword($password) {
        $result = self::checkPasswordMask($password);
        if ($result != self::PASS_OK) {
            return $result;
        }

        $encPass = $this->encodePassword($password);

        if ($this->get('password') != $encPass) {
            $this->set('password', $encPass);
            $this->set('using_pw_request', '0');
        }

        return $result;
    }

    /**
     * This method saves the given password $password.
     *
     * The password has to be checked, before it is set to the database.
     *
     * The resulting integer value represents the result code.
     *
     * Use the PASS_* constants to check what happens.
     *
     * @param string $password
     * @return int|bool
     *         PASS_* or false if saving fails
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function savePassword($password) {
        if ($this->get('password') == $this->encodePassword($password)) {
            return self::PASS_OK;
        }

        $result = $this->setPassword($password);

        if ($this->store() === false) {
            return false;
        } else {
            return $result;
        }
    }

    /**
     * Returns user name, currently set
     *
     * @return string
     */
    public function getUserName() {
        return $this->get('username');
    }

    /**
     * Sets up new user name.
     *
     * @param string $sUserName
     */
    public function setUserName($sUserName) {
        if ($this->get('username') != $sUserName) {
            $this->set('username', $sUserName);
        }
    }

    /**
     * Getter method to get user realname
     *
     * @return string
     *         Realname of user
     */
    public function getRealName() {
        return $this->get('realname');
    }

    /**
     * Returns effective user name (if exists realname , otherwise username)
     *
     * @return string
     *         Realname or username of user
     */
    public function getEffectiveName() {
        $name = trim($this->get('realname'));
        if (cString::getStringLength($name) == 0) {
            $name = trim($this->get('username'));
        }
        return $name;
    }

    /**
     * Getter method to get user mail
     *
     * @return string
     */
    public function getMail() {
        return $this->get('email');
    }

    /**
     * Getter method to get user tel number
     *
     * @return string
     */
    public function getTelNumber() {
        return $this->get('telephone');
    }

    /**
     * Getter method to get user adress data
     *
     * @return array
     *         Address data array like:
     *         <pre>
     *         $aAddress['street'], $aAddress['city'], $aAddress['country'],
     *         $aAddress['zip']
     *         </pre>
     */
    public function getAddressData() {
        $aret = array(
            'street' => $this->get('address_street'),
            'city' => $this->get('address_city'),
            'country' => $this->get('address_country'),
            'zip' => $this->get('address_zip')
        );

        return $aret;
    }

    /**
     * Getter method to get user wysi
     *
     * @return int
     */
    public function getUseWysi() {
        return $this->get('wysi');
    }

    /**
     * Getter method to get user valid date from-to
     *
     * @return string
     */
    public function getValidDateTo() {
        return $this->get('valid_to');
    }

    /**
     * Getter method to get user valid date from-to
     *
     * @return string
     */
    public function getValidDateFrom() {
        return $this->get('valid_from');
    }

    /**
     * Getter method to get user perm name
     *
     * @return string
     */
    public function getPerms() {
        return $this->get('perms');
    }

    /**
     * Returns list of user permissions.
     *
     * @return array
     */
    public function getPermsArray() {
        return explode(',', $this->get('perms'));
    }

    /**
     * Setter method to set user real name
     *
     * @param string $sRealName
     */
    public function setRealName($sRealName) {
        if ($this->get('realname') != $sRealName) {
            $this->set('realname', $sRealName);
        }
    }

    /**
     * Setter method to set user mail address
     *
     * @param string $sMail
     */
    public function setMail($sMail) {
        if ($this->get('email') != $sMail) {
            $this->set('email', $sMail);
        }
    }

    /**
     * Setter method to set user tel number
     *
     * @param string $sTelNumber
     */
    public function setTelNumber($sTelNumber) {
        if ($this->get('telephone') != $sTelNumber) {
            $this->set('telephone', $sTelNumber);
        }
    }

    /**
     * Setter method to set address data
     *
     * @param string $sStreet
     * @param string $sCity
     * @param string $sZip
     * @param string $sCountry
     */
    public function setAddressData($sStreet, $sCity, $sZip, $sCountry) {
        if ($this->get('address_street') != $sStreet) {
            $this->set('address_street', $sStreet);
        }
        if ($this->get('address_city') != $sCity) {
            $this->set('address_city', $sCity);
        }
        if ($this->get('address_zip') != $sZip) {
            $this->set('address_zip', $sZip);
        }
        if ($this->get('address_country') != $sCountry) {
            $this->set('address_country', $sCountry);
        }
    }

    /**
     * Sets value for street.
     *
     * @param string $sStreet
     */
    public function setStreet($sStreet) {
        if ($this->get('address_street') != $sStreet) {
            $this->set('address_street', $sStreet);
        }
    }

    /**
     * Sets value for city.
     *
     * @param string $sCity
     */
    public function setCity($sCity) {
        if ($this->get('address_city') != $sCity) {
            $this->set('address_city', $sCity);
        }
    }

    /**
     * Sets value for ZIP.
     *
     * @param string $sZip
     */
    public function setZip($sZip) {
        if ($this->get('address_zip') != $sZip) {
            $this->set('address_zip', $sZip);
        }
    }

    /**
     * Sets value for country.
     *
     * @param string $sCountry
     */
    public function setCountry($sCountry) {
        if ($this->get('address_country') != $sCountry) {
            $this->set('address_country', $sCountry);
        }
    }

    /**
     * Setter method to set wysi
     *
     * @param int $iUseWysi
     */
    public function setUseWysi($iUseWysi) {
        if ($this->get('wysi') != $iUseWysi) {
            $this->set('wysi', $iUseWysi);
        }
    }

    /**
     * Setter method to set valid_to
     *
     * @todo add type check
     * @param string $sValidateTo
     */
    public function setValidDateTo($sValidateTo) {
        if ('0000-00-00' == $this->get('valid_to') && 0 == cString::getStringLength(trim($sValidateTo))) {
            return;
        }
        if ($this->get('valid_to') != $sValidateTo) {
            $this->set('valid_to', $sValidateTo);
        }
    }

    /**
     * Setter method to set valid_from
     *
     * @todo add type checks
     * @param string $sValidateFrom
     */
    public function setValidDateFrom($sValidateFrom) {
        if ('0000-00-00' == $this->get('valid_from') && 0 == cString::getStringLength(trim($sValidateFrom))) {
            return;
        }
        if ($this->get('valid_from') != $sValidateFrom) {
            $this->set('valid_from', $sValidateFrom);
        }
    }

    /**
     * Setter method to set perms
     *
     * @param array|string $perms
     */
    public function setPerms($perms) {
        if ($this->get('perms') != $perms) {
            $this->set('perms', $perms);
        }
    }

    /**
     * Function returns effective perms for user including group rights as perm
     * string.
     *
     * @author Timo Trautmann
     * @return string
     *         Current users permissions
     */
    public function getEffectiveUserPerms() {
        global $perm;

        // first get users own permissions and filter them into result array
        // $aUserPerms
        $aUserPerms = array();
        $aUserPermsSelf = explode(',', $this->values['perms']);
        foreach ($aUserPermsSelf as $sPerm) {
            if (trim($sPerm) != '') {
                $aUserPerms[] = $sPerm;
            }
        }

        // get all corresponding groups for this user
        $groups = $perm->getGroupsForUser($this->values['user_id']);

        foreach ($groups as $value) {
            // get global group permissions
            $oGroup = new cApiGroup($value);
            $aGroupPerms = $oGroup->getPermsArray();

            // add group permissions to $aUserPerms if they were not alredy
            // defined before
            foreach ($aGroupPerms as $sPerm) {
                if (trim($sPerm) != '' && !in_array($sPerm, $aUserPerms)) {
                    $aUserPerms[] = $sPerm;
                }
            }
        }
        return implode(',', $aUserPerms);
    }

    /**
     * Returns group names where the user is in.
     *
     * @param string $userid          [optional]
     *                                user id, uses id of loaded user by default.
     * @param bool   $bAddDescription [optional]
     *                                Flag to add description like "groupname (description)"
     * @return array
     * @throws cDbException
     * @throws cException
     */
    public function getGroupNamesByUserID($userid = NULL, $bAddDescription = true) {
        $userid = (NULL === $userid) ? $this->get('user_id') : $userid;

        $aGroups = array();

        $oGroupColl = new cApiGroupCollection();
        $groups = $oGroupColl->fetchByUserID($userid);

        foreach ($groups as $group) {
            $sTemp = $group->get('groupname');
            $sTemp = cString::getPartOfString($sTemp, 4, cString::getStringLength($sTemp) - 4);

            if (true === $bAddDescription) {
                $sDescription = trim($group->get('description'));
                if ($sDescription != '') {
                    $sTemp .= ' (' . $sDescription . ')';
                }
            }

            $aGroups[] = $sTemp;
        }

        return $aGroups;
    }

    /**
     * Returns group ids where the user is in.
     *
     * @param string $userid [optional]
     *                       user id, uses id of loaded user by default.
     * @return array
     * @throws cDbException
     * @throws cException
     */
    public function getGroupIDsByUserID($userid) {
        $userid = (NULL === $userid) ? $this->get('user_id') : $userid;

        $aGroups = array();

        $oGroupColl = new cApiGroupCollection();
        $groups = $oGroupColl->fetchByUserID($userid);

        foreach ($groups as $group) {
            $aGroups[] = $group->get('group_id');
        }

        return $aGroups;
    }

    /**
     * Retrieves the effective user property.
     *
     * @param string $type
     *                      Type (class, category etc) for the property to retrieve
     * @param string $name
     *                      Name of the property to retrieve
     * @param bool   $group [optional]
     *                      Flag to search in groups
     * @return string|bool
     *                      value of the retrieved property or false
     *
     * @throws cDbException
     * @throws cException
     */
    public function getUserProperty($type, $name, $group = false) {
        global $perm;

        if (!is_object($perm)) {
            $perm = new cPermission();
        }

        $result = false;

        if ($group == true) {
            // first get property by existing groups, if desired
            $groups = $perm->getGroupsForUser($this->values['user_id']);

            foreach ($groups as $groupid) {
                $groupPropColl = new cApiGroupPropertyCollection($groupid);
                $groupProp = $groupPropColl->fetchByGroupIdTypeName($type, $name);
                if ($groupProp) {
                    $result = $groupProp->get('value');
                }
            }
        }

        // get property of user
        $userPropColl = new cApiUserPropertyCollection($this->values['user_id']);
        $userProp = $userPropColl->fetchByUserIdTypeName($type, $name);
        if ($userProp) {
            $result = $userProp->get('value');
        }

        return ($result !== false) ? $result : false;
    }

    /**
     * Returns all user properties by type.
     *
     * @todo return value should be similar to getUserProperties()
     *
     * @param string $type
     *                      Type (class, category etc) of the properties to retrieve
     * @param bool   $group [optional]
     *                      Flag to retrieve in group properties. If enabled, group
     *                      properties will be merged with user properties where the user
     *                      poperties will overwrite group properties
     * @return array
     *                      Assoziative properties array as follows:
     *                      - $arr[name] = value
     * @throws cDbException
     * @throws cException
     */
    public function getUserPropertiesByType($type, $group = false) {
        global $perm;

        if (!is_object($perm)) {
            $perm = new cPermission();
        }

        $props = array();

        if ($group == true) {
            // first get properties by existing groups, if desired
            $groups = $perm->getGroupsForUser($this->values['user_id']);
            foreach ($groups as $groupid) {
                $groupPropColl = new cApiGroupPropertyCollection($groupid);
                $groupProps = $groupPropColl->fetchByGroupIdType($type);
                foreach ($groupProps as $groupProp) {
                    $props[$groupProp->get('name')] = $groupProp->get('value');
                }
            }
        }

        // get properties of user
        $userPropColl = new cApiUserPropertyCollection($this->values['user_id']);
        $userProps = $userPropColl->fetchByUserIdType($type);
        foreach ($userProps as $userProp) {
            $props[$userProp->get('name')] = $userProp->get('value');
        }

        return $props;
    }

    /**
     * Retrieves all available properties of the user.
     *
     * @return array
     *         Return value in new mode is:
     *         - $arr[iduserprop][name]
     *         - $arr[iduserprop][type]
     *         - $arr[iduserprop][value]
     *
     * @throws cDbException
     * @throws cException
     */
    public function getUserProperties() {
        $userPropColl = new cApiUserPropertyCollection($this->values['user_id']);
        $userProps = $userPropColl->fetchByUserId();

        $props = array();

        foreach ($userProps as $userProp) {
            $props[$userProp->get('iduserprop')] = array(
                'name' => $userProp->get('name'),
                'type' => $userProp->get('type'),
                'value' => $userProp->get('value')
            );
        }

        return $props;
    }

    /**
     * Stores a property to the database
     *
     * @param string $type
     *         Type (class, category etc) for the property to retrieve
     * @param string $name
     *         Name of the property to retrieve
     * @param string $value
     *         Value to insert
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function setUserProperty($type, $name, $value) {
        $userPropColl = new cApiUserPropertyCollection($this->values['user_id']);
        $userProps = $userPropColl->setValueByTypeName($type, $name, $value);
    }

    /**
     * Deletes a user property from the table.
     *
     * @param string $type
     *         Type (class, category etc) of property to retrieve
     * @param string $name
     *         Name of property to retrieve
     *
     * @return bool
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function deleteUserProperty($type, $name) {
        $userPropColl = new cApiUserPropertyCollection($this->values['user_id']);
        return $userPropColl->deleteByUserIdTypeName($type, $name);
    }

    /**
     * This static method provides a simple way to get error messages depending
     * on error code $iErrorCode, which is returned by checkPassword* methods.
     *
     * @param int $iErrorCode
     * @return string
     */
    public static function getErrorString($iErrorCode) {
        global $cfg;

        $sError = '';

        switch ($iErrorCode) {
            case self::PASS_NOT_ENOUGH_MIXED_CHARS:
                $sError = sprintf(i18n('Please use at least %d lower and upper case characters in your password!'), $cfg['password']['mixed_case_mandatory']);
                break;
            case self::PASS_NOT_ENOUGH_NUMBERS:
                $sError = sprintf(i18n('Please use at least %d numbers in your password!'), $cfg['password']['numbers_mandatory']);
                break;
            case self::PASS_NOT_ENOUGH_SYMBOLS:
                $sError = sprintf(i18n('Please use at least %d symbols in your password!'), $cfg['password']['symbols_mandatory']);
                break;
            case self::PASS_TO_SHORT:
                $sError = sprintf(i18n('Password is too short! Please use at least %d signs.'), ($cfg['password']['min_length'] > 0? $cfg['password']['min_length'] : self::MIN_PASS_LENGTH_DEFAULT));
                break;
            case self::PASS_NOT_ENOUGH_DIFFERENT_CHARS:
                $sError = sprintf(i18n('Password does not contain enough different characters.'));
                break;
            case self::PASS_NOT_STRONG:
                $sError = i18n('Please choose a more secure password!');
                break;
            default:
                $sError = 'I do not really know what has happened. But your password does not match the
                policies! Please consult your administrator. The error code is #' . $iErrorCode;
        }

        return $sError;
    }
}
