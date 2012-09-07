<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * This class will be a replacement for all other
 * user classes, which encapsulates only small parts
 * of user related tasks.
 *
 * In current version you can administer optional password checks
 * via following configuration values:
 *
 * - En- or disabling checks:
 *  $cfg['password']['check_password_mask'] = [true|false]
 *  Use this flag to enable (true) or disable (false) the mask checks.
 *
 *  $cfg['password']['use_cracklib'] = [true|false]
 *  Use this to enable (true) or disable (false) the strength check, currently done with cracklib.
 *
 * - Mask checks:
 *  Password mask checks are checks belonging to the "format" of the needed password string.
 *
 *  $cfg['password']['min_length'], int
 *     Minimum length a password has to have. If not set, 8 chars are set as default
 *  $cfg['password']['numbers_mandatory'], int
 *     If set to a value greater than 0, at least $cfg['password']['numbers_mandatory'] numbers
 *     must be in password
 *  $cfg['password']['symbols_mandatory'], int && $cfg['password']['symbols_regex'], String
 *      If 'symbols_mandatory' set to a value greater than 0, at least so many symbols has to appear in
 *      given password. What symbols are regcognized can be administrated via 'symbols_regex'. This has
 *      to be a regular expression which is used to "find" the symbols in $sNewPassword. If not set, following
 *      RegEx is used: "/[|!@#$%&*\/=?,;.:\-_+~^¨\\\]/"
 *  $cfg['password']['mixed_case_mandatory'], int
 *      If set to a value greater than 0 so many lower and upper case character must appear in the password.
 *      (e.g.: if set to 2, 2 upper and 2 lower case characters must appear)
 *
 * - Strength check
 *  Passwords should have some special characteristics to be a strong, i.e. not easy to guess, password. Currently
 *  cracklib is supported. These are the configuration possibilities:
 *
 *  $cfg['password']['cracklib_dict'], string
 *     Path and file name (without file extension!) to dictionary you want to use. This setting is
 *     mandatory!
 *
 *  Keep in mind that these type of check only works if crack module is available.
 *
 * @package Contenido Backend classes
 * @subpackage Backend User
 *
 * @version $Revision$
 * @author Bilal Arslan, Holger Librenz
 * @copyright four for business AG
 *
 * {@internal
 *  created 04.11.2008
 *  modified 2008-11-16,  H. Librenz - added structure, comments fixed, code debugged
 *  modified 2008-11-21,  H. Librenz - some documentation stuff added
 *  modified 2008-11-25, Timo Trautman - removed not existing include
 *  modified 2008-12-04, Bilal Arslan, Bugfixed for set passwort length, comments "how to use" fixed.
 *  Bugfixed for password, lower Case Upper case count, for symbols count and numbers count.
 *  modified 2008-12-04, Timo Trautman, Added Contenido $cfg as param for getErrorString()
 *
 *  @Id
 * }}
 *
 **/

if (! defined ( 'CON_FRAMEWORK' )) {
    die ( 'Illegal call' );
}

// Exception classes
cInclude ( "exceptions", "exception.conuser.php" );

// load base classe
cInclude ( "classes", 'abstract_classes/class.conuser.php' );

/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Class User to handle all user related task.
 * In first implementations, it will only do some little
 * things, like checking and setting passwords.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend classes
 * @subpackage Backend user
 *
 * @version    0.2.0
 * @author     Bilal Arslan, Holger Librenz
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release => 4.8.8
 *
 */
class ConUser extends ConUser_Abstract {

    /**
	 * Realname
	 * @var string
	 */
    private $sRealName = "";

    /**
	 * Mail address
	 *
	 * @var string
	 */
    private $sMail = "";

    /**
	 * Telephone number
	 * @var string
	 */
    private $sTelNumber = "";

    /**
	 * Array of address data fill like below values
	 *
	 * $aAddress['street'], $aAddress['city'], $aAddress['country'],  $aAddress['zip']
	 * @var array
	 */
    private $aAddress = array ();

    /**
	 * To Use Tiny Editor
	 * @var int
	 */
    private $iUseTiny = null;

    /**
	 * User valid date to
	 * @var string
	 */
    private $sValidDateTo = null;

    /**
	 * User valid date from
	 * @var string
	 */
    private $sValidDateFrom = null;

    /**
	 * Permname
	 * @var string
	 */
    private $sPermName = "";

    /**
	 * Currently not implemented!
	 *
	 * @see ConUser_Abstract::load()
	 *
	 * @param string $sUserId
	 * @return boolean
	 *
	 * @todo implement it
	 */
    public function load($sUserId) {
        return true;
    }

    /**
	 * @see ConUser_Abstract::save()
	 *
	 * @return boolean
	 */
    public function save() {
        $bResult = false;

        $sUserId = $this->getUserId ();

        if (! empty ( $sUserId )) {
            $bResult = $this->update ();
        } else {
            $bResult = $this->insert ();
        }

        return $bResult;
    }

    /**
	 * Updates a user
	 *
	 * This method update base user informations in user table. It is called
	 * within the iConUser::save() method.
	 *
	 * @return boolean
	 *
	 * @todo add type checks!
	 */
    protected function update() {
        $bResult = false;

        $sUserId = $this->getUserId ();

        if (! empty ( $sUserId )) {
            $sSql = "
	        UPDATE
	           `" . $this->aCfg ["tab"] ["phplib_auth_user_md5"] . "`
	        SET
	           realname = '" . Contenido_Security::escapeDB ( $this->sRealName, $this->oDb ) . "',
	           email = '" . Contenido_Security::escapeDB ( $this->sMail, $this->oDb ) . "',
	           telephone = '" . Contenido_Security::escapeDB ( $this->sTelNumber, $this->oDb ) . "',
	           address_street = '" . Contenido_Security::escapeDB ( $this->aAddress ['street'], $this->oDb ) . "',
	           address_city = '" . Contenido_Security::escapeDB ( $this->aAddress ['city'], $this->oDb ) . "',
	           address_country = '" . Contenido_Security::escapeDB ( $this->aAddress ['country'], $this->oDb ) . "',
	           address_zip = '" . Contenido_Security::escapeDB ( $this->aAddress ['zip'], $this->oDb ) . "',
	           wysi = '" . Contenido_Security::toInteger ( $this->iUseTiny ) . "',
	           valid_from = '" . Contenido_Security::escapeDB ( $this->sValidDateFrom, $this->oDb ) . "',
	           valid_to = '" . Contenido_Security::escapeDB ( $this->sValidDateTo, $this->oDb ) . "',
	           perms = '" . $this->sPermName . "'
	        WHERE
	           user_id = '" . Contenido_Security::escapeDB ( $sUserId, $this->oDb ) . "'";

            // try to update
            if (! $this->oDb->query ( $sSql )) {
                throw new ConUserException ( "Could not update user informations" );
            } else {
                // try to update password, if set
                $sNewPass = $this->getPassword();

                if (!is_null($sNewPass)) {
                    $iPassResult = $this->savePassword($sNewPass);

                    if ($iPassResult != iConUser::PASS_OK) {
                        // throw an exception, caus I do not know what to do uin that case, cause user update was successful!
                        throw new ConUserException( "Given password is not valid! [#" . $iPassResult . ']',
                        iConUser::EXCEPTION_PASSWORD_INVALID);
                    }
                }

                $bResult = true;
            }
        }

        return $bResult;
    }

    /**
	 * Creates new user.
	 *
	 * This method creates a new user with base informations. It is called
	 * within the iConUser::save() method.
	 *
	 * @return boolean
	 *
	 * @todo add value checks!
	 */
    protected function insert() {
        $bResult = false;

        $sUserName = $this->getUserName ();

        if (! empty ( $sUserName )) {

            // check if user already exists
            if (self::usernameExists($sUserName)) {
                throw new ConUserException ("Username already exists!", iConUser::EXCEPTION_USERNAME_EXISTS);
            }

            // create user id
            $sNewUserId = $this->generateUserId ();

            $sSql = "
            INSERT INTO
               `" . $this->aCfg ["tab"] ["phplib_auth_user_md5"] . "`
            SET
               username = '" . Contenido_Security::escapeDB( $sUserName, $this->oDb ) . "',
               user_id = '" . Contenido_Security::escapeDB ( $sNewUserId, $this->oDb ) . "',
               realname = '" . Contenido_Security::escapeDB ( $this->sRealName, $this->oDb ) . "',
               email = '" . Contenido_Security::escapeDB ( $this->sMail, $this->oDb ) . "',
               telephone = '" . Contenido_Security::escapeDB ( $this->sTelNumber, $this->oDb ) . "',
               address_street = '" . Contenido_Security::escapeDB ( $this->aAddress ['street'], $this->oDb ) . "',
               address_city = '" . Contenido_Security::escapeDB ( $this->aAddress ['city'], $this->oDb ) . "',
               address_country = '" . Contenido_Security::escapeDB ( $this->aAddress ['country'], $this->oDb ) . "',
               address_zip = '" . Contenido_Security::escapeDB ( $this->aAddress ['zip'], $this->oDb ) . "',
               wysi = '" . Contenido_Security::toInteger ( $this->iUseTiny ) . "',
               valid_from = '" . Contenido_Security::escapeDB ( $this->sValidDateFrom, $this->oDb ) . "',
               valid_to = '" . Contenido_Security::escapeDB ( $this->sValidDateTo, $this->oDb ) . "',
               perms = '" . Contenido_Security::escapeDB ( $this->sPermName, $this->oDb ) . "'";

            // try to update
            if (! $this->oDb->query ( $sSql )) {
                throw new ConUserException ( "Could not create user in database" );
            } else {
                if ($this->oDb->affected_rows () == 1) {
                    // set password, if available...
                    $sNewPass = $this->getPassword();

                    if (!is_null($sNewPass)) {
                        $iPassResult = $this->savePassword($sNewPass);

                        if ($iPassResult != iConUser::PASS_OK) {
                            throw new ConUserException( "Given password is not valid! [#" . $iPassResult . ']',
                            iConUser::EXCEPTION_PASSWORD_INVALID);
                        }
                    }

                    $bResult = true;
                }
            }
        }

        return $bResult;
    }

    /**
	 * Checks if an user with user id $sUserId already exists in DB.
	 *
	 * @param string $iUserId
	 * @return boolean
	 */
    public function userExists($sUserId) {
        $bResult = false;

        $sSql = "
        SELECT
            count(*) as user_cnt
        FROM
            `" . $this->aCfg ["tab"] ["phplib_auth_user_md5"] . "`
        WHERE
            user_id = '" . Contenido_Security::escapeDB ( strtolower ( $sUserId ), $this->oDb ) . "'";

        if ($this->oDb->query ( $sSql) !== false && $this->oDb->next_record()) {
            $iCount = (int) $this->oDb->f('user_cnt');

            $bResult = ($iCount != 0);
        } else {
            throw new ConUserException("User existence check failed!");
        }

        return $bResult;
    }

    /**
	 * Checks if username $sUsername is already in use.
	 *
	 * @param string $sUsername
	 * @return boolean
	 *
	 * @todo to be implemented
	 */
    public function usernameExists($sUsername) {
        $bResult = false;

        /*
        * Check count of entries that use username $sUsername.
        * I use count(*) instead of selecting a field (e.g. user_id) cause
        * MySQL MyISAM tables use optimized
        */
        $sSql = "
        SELECT
            count(*) as user_cnt
        FROM
            `" . $this->aCfg ["tab"] ["phplib_auth_user_md5"] . "`
        WHERE
            LOWER(`username`) = '" . Contenido_Security::escapeDB ( strtolower ( $sUsername ), $this->oDb ) . "'";

        if ($this->oDb->query ( $sSql) !== false && $this->oDb->next_record()) {
            $iCount = (int) $this->oDb->f('user_cnt');

            $bResult = ($iCount != 0);
        } else {
            throw new ConUserException("Could check if username is already in use!");
        }

        return $bResult;
    }

    /**
	 * @see ConUser_Abstract::savePassword()
	 *
	 * The method uses following config values:
	 *
	 *
	 * @param string $sNewPassword Password to set
	 * @return int
	 *
	 * @throws ConUserException
	 */
    public function savePassword($sNewPassword) {
        $iResult = 0;

        // get current user id...
        $sUserId = $this->getUserId ();

        // check user id...
        if (empty ( $sUserId )) {
            throw new ConUserException ( "Could not set password for anonymous user." );
        } else {
            $bSaveAllowed = true;

            // check password for strength and complexity
            if ($this->aCfg ['password'] ['check_password_mask']) {
                $iMaskResult = self::checkPasswordMask( $sNewPassword );

                if ($iMaskResult != iConUser::PASS_OK) {
                    $iResult = $iMaskResult;
                } else {
                    $bSaveAllowed = true;
                }
            }

            if ($bSaveAllowed && $this->aCfg ['password'] ['use_cracklib']) {
                $iStrengthResult = iConUser::checkPasswordStrength ( $sNewPassword );

                if ($iStrengthResult != iConUser::PASS_OK) {
                    $iResult = $iStrengthResult;
                } else {
                    $bSaveAllowed = true;
                }
            }

            if ($bSaveAllowed) {
                // now it is time to save...

                // passwords are encoded as md5 hashes
                $sPass = self::encodePassword( $sNewPassword );
                $sSql = "
                  UPDATE
                      `" . $this->aCfg ["tab"] ["phplib_auth_user_md5"] . "`
                  SET
                      password='" . Contenido_Security::escapeDB ( $sPass, $this->oDb ) . "'
                  WHERE
                      user_id = '" . Contenido_Security::escapeDB ( $sUserId, $this->oDB ) . "'";

                $bQueryResult = $this->oDb->query ( $sSql );

                if (! $bQueryResult || $this->oDb->affected_rows () < 1) {
                    throw new ConUserException ( "Could not set password! A DB error occured." );
                } else {
                    $iResult = iConUser::PASS_OK;
                }
            }
        }

        return $iResult;
    }

    /**
	 * Calls constructor in base class.
	 *
	 * @param array $aCfg
	 * @param DB_Contenido $oDB
	 * @param string $sIdUser User ID the instnace of this class represents
	 *
	 * @return ConUser
	 * @throws ConUserException
	 */
    public function __construct($aCfg, $oDb = null, $sUserId = null) {
        parent::__construct ( $aCfg, $oDb, $sUserId );
    }

    /**
	 * This function does update without password column to all columns of con_phplib_auth_user_md5 table.
	 *
	 * @return void
	 */
    public function saveUser() {
        if ($this->sIdUser != "") {
            $sSql = ' UPDATE ' . $this->aCfg ["tab"] ["phplib_auth_user_md5"] . ' SET
						  realname="' . Contenido_Security::escapeDB ( $this->sRealName, $this->oDB ) . '",
						  email="' . Contenido_Security::escapeDB ( $this->sMail, $this->oDB ) . '",
						  telephone="' . Contenido_Security::escapeDB ( $this->sTelNumber, $this->oDB ) . '",
						  address_street="' . Contenido_Security::escapeDB ( $this->aAddress ['street'], $this->oDB ) . '",
						  address_city="' . Contenido_Security::escapeDB ( $this->aAddress ['city'], $this->oDB ) . '",
						  address_country="' . Contenido_Security::escapeDB ( $this->aAddress ['country'], $this->oDB ) . '",
						  address_zip="' . Contenido_Security::escapeDB ( $this->aAddress ['zip'], $this->oDB ) . '",
						  wysi="' . Contenido_Security::toInteger ( $this->iUseTiny ) . '",
						  valid_from="' . Contenido_Security::escapeDB ( $this->sValidDateFrom, $this->oDB ) . '",
						  valid_to="' . Contenido_Security::escapeDB ( $this->sValidDateTo, $this->oDB ) . '",
						  perms="' . $this->sPermName . '"
					  WHERE user_id = "' . Contenido_Security::escapeDB ( $this->sIdUser, $this->oDB ) . '"';

            try {
                if (( int ) $this->oDB->query ( $sSql ) != 1 || $this->oDB->affected_rows () != 1) {
                    throw new ConDbException ( "Update could not possible" );
                }
            } catch ( ConDbException $e ) {
                print $e->getMessage ();
            }
        }
    }

    /**
	 * Getter method to get user realname
	 * @return string Realname of user
	 */
    public function getRealName() {
        return $this->sRealName;
    }

    /**
	 * Getter method to get user mail
	 * @return string Realname of user
	 */
    public function getMail() {
        return $this->sMail;
    }

    /**
	 * Getter method to get user tel number
	 * @return string Realname of user
	 */
    public function getTelNumber() {
        return $this->sTelNumber;
    }

    /**
	 * Getter method to get user adress data
	 * @return string Realname of user
	 */
    public function getAddressData() {
        return $this->aAddress;
    }

    /**
	 * Getter method to get user wysi
	 * @return string Realname of user
	 */
    public function getUseTiny() {
        return $this->iUseTiny;
    }

    /**
	 * Getter method to get user valid date from-to
	 * @return string Realname of user
	 */
    public function getValidDateTo() {
        return $this->sValidDateTo;
    }

    /**
	 * Getter method to get user valid date from-to
	 * @return string Realname of user
	 */
    public function getValidDateFrom() {
        return $this->sValidDateFrom;
    }

    /**
	 * Getter method to get user perm name
	 * @return string Realname of user
	 */
    public function getPerms() {
        return $this->sPermName;
    }

    /**
	 * Setter method to set user real name
	 * @return void
	 */
    public function setRealName($sRealName) {
        $this->sRealName = $sRealName;
    }

    /**
	 * Setter method to set user mail address
	 * @return void
	 */
    public function setMail($sMail) {
        $this->sMail = $sMail;
    }

    /**
	 * setter method to set user tel number
	 * @return void
	 */
    public function setTelNumber($sTelNumber) {
        $this->sTelNumber = $sTelNumber;
    }

    /**
	 * Setter method to set Adress Data
	 * @return void
	 */
    public function setAddressData($sAddressStreet, $sAddressCity, $sAddressZip, $sAddressCountry) {
        $this->aAddress ['street'] = $sAddressStreet;
        $this->aAddress ['city'] = $sAddressCity;
        $this->aAddress ['zip'] = $sAddressZip;
        $this->aAddress ['country'] = $sAddressCountry;
    }

    /**
	 * Sets value for street.
	 *
	 * @param string $sStreet
	 */
    public function setStreet ($sStreet) {
        $this->aAddress['street'] = $sStreet;
    }

    /**
	 * Sets value for city.
	 *
	 * @param string $sCity
	 */
    public function setCity ($sCity) {
        $this->aAddress['city'] = $sCity;
    }

    /**
	 * Sets value for ZIP.
	 *
	 * @param string $sZip
	 */
    public function setZip ($sZip) {
        $this->aAddress['zip'] = $sZip;
    }

    /**
	 * Sets value for country.
	 *
	 * @param string $sCountry
	 */
    public function setCountry ($sCountry) {
        $this->aAddress['country'] = $sCountry;
    }

    /**
	 * Setter method to set
	 * @return void
	 */
    public function setUseTiny($iUseTiny) {
        $this->iUseTiny = $iUseTiny;
    }

    /**
	 * setter method to set User
	 *
	 * @return void
	 *
	 * TODO add type check
	 */
    public function setValidDateTo($sValidateTo) {
        $this->sValidDateTo = $sValidateTo;
    }

    /**
	 * setter method to set
	 *
	 * @return void
	 *
	 * TODO add type checks
	 */
    public function setValidDateFrom($sValidateFrom) {
        $this->sValidDateFrom = $sValidateFrom;
    }

    /**
	 * setter method to set
	 *
	 * @return void
	 *
	 * TODO add type checks
	 */
    public function setPerms($aPerms) {
        $this->sPermName = implode ( ",", $aPerms );
    }

    /**
 
	 *
	 * Following configuration values are recognized:
	 * $this->aCfg['password']['check_password_mask'], bool
	 *     En- or disable these checks...
	 * $this->aCfg['password']['min_length'], int
	 *     Minimum length a password has to have. If not set, 8 chars are set as default
	 * $this->aCfg['password']['numbers_mandatory'], int
	 *     If set to a value greater than 0, at least $this->aCfg['password']['numbers_mandatory'] numbers
	 *     must be in password
	 * $this->aCfg['password']['symbols_mandatory'], int &&
     * $this->aCfg['password']['symbols_regex'], String
     *      If 'symbols_mandatory' set to a value greater than 0, at least so many symbols has to appear in
     *      given password. What symbols are regcognized can be administrated via 'symbols_regex'. This has
     *      to be a regular expression which is used to "find" the symbols in $sNewPassword. If not set, following
     *      RegEx is used: "/[|!@#$%&*\/=?,;.:\-_+~^¨\\\]/"
     * $this->aCfg['password']['mixed_case_mandatory'], int
     *      If set to a value greater than 0 so many lower and upper case character must appear in the password.
     *      (e.g.: if set to 2, 2 upper and 2 lower case characters must appear)
	 *
	 * @param string $sNewPassword
	 * @return int
	 *
	 */
    public function checkPasswordMask($sNewPassword) {
        $iResult = iConUser::PASS_OK;

        if (isset($this->aCfg['password']['check_password_mask']) &&
        $this->aCfg['password']['check_password_mask'] == true) {
            // any min length in config set?
            $iMinLength = iConUser::MIN_PASS_LENGTH_DEFAULT;
            if (isset( $this->aCfg ['password'] ['min_length'] )) {
                $iMinLength = ( int ) $this->aCfg ['password'] ['min_length'];
            }

            // check length...
            if (strlen ( $sNewPassword ) < $iMinLength) {
                $iResult = iConUser::PASS_TO_SHORT;
            }

            // check password elements

            // numbers.....
            if ($iResult == iConUser::PASS_OK && isset($this->aCfg['password']['numbers_mandatory']) &&
            (int) $this->aCfg['password']['numbers_mandatory'] > 0) {

                $aNumbersInPassword = array();
                preg_match_all("/[0-9]/", $sNewPassword, $aNumbersInPassword) ;

                if (count($aNumbersInPassword[0]) < (int) $this->aCfg['password']['numbers_mandatory']) {
                    $iResult = iConUser::PASS_NOT_ENOUGH_NUMBERS;
                }
            }

            // symbols....
            if ($iResult == iConUser::PASS_OK && isset($this->aCfg['password']['symbols_mandatory']) &&
            (int) $this->aCfg['password']['symbols_mandatory'] > 0) {

                $aSymbols = array();
                $sSymbolsDefault = "/[|!@#$%&*\/=?,;.:\-_+~^¨\\\]/";
                if (isset($this->aCfg['password']['symbols_regex']) && !empty($this->aCfg['password']['symbols_regex'])) {
                    $sSymbolsDefault = $this->aCfg['password']['symbols_regex'];
                }

                preg_match_all($sSymbolsDefault, $sNewPassword, $aSymbols);
				
                if (count($aSymbols[0]) < (int) $this->aCfg['password']['symbols_mandatory']) {
                    $iResult = iConUser::PASS_NOT_ENOUGH_SYMBOLS;
                }
            }

            // mixed case??
            if ($iResult == iConUser::PASS_OK && isset($this->aCfg['password']['mixed_case_mandatory']) &&
            (int) $this->aCfg['password']['mixed_case_mandatory'] > 0) {

                $aLowerCaseChars = array();
                $aUpperCaseChars = array();

                preg_match_all("/[a-z]/", $sNewPassword, $aLowerCaseChars);
                preg_match_all("/[A-Z]/", $sNewPassword, $aUpperCaseChars);

                if ((count($aLowerCaseChars[0]) < (int) $this->aCfg['password']['mixed_case_mandatory']) ||
                (count($aUpperCaseChars[0]) < (int) $this->aCfg['password']['mixed_case_mandatory'])) {
                    $iResult = iConUser::PASS_NOT_ENOUGH_MIXED_CHARS;
                }
            }
        }

        return $iResult;
    }

    /**
	 * This password checks the password strength. In "standard" implementation, it uses
	 * cracklib, if administrated and available. Other possible checks are checks against
	 * user list with birth dates or similar, non direct "maskable" checks.
	 *
	 * Following configuration values are recognized:
	 * $this->aCfg['password']['use_cracklib'], bool
	 *     En- or disable these checks...
	 * $this->aCfg['password']['cracklib_dict'], string
	 *     Path and file name (without file extension!) to dictionary you want to use. This setting is
	 *     mandatory!
	 *
	 * Please ensure that you have a working crack module installed. If the function crack_opendict is
	 * not available, the check are omitted and the result is iConUser::PASS_OK.
	 *
	 * @param string $sNewPassword
	 * @return int
	 */
    public function checkPasswordStrength ($sNewPassword) {
        $iResult = iConUser::PASS_OK;

        // if cracklib functions available and cracklib checks are enabled, check password against cracklib...
        if (function_exists('crack_opendict')) {
            if (isset($this->aCfg['password']['use_cracklib']) && $this->aCfg['password']['use_cracklib'] == true) {
                //print "CHECK 1<br>\n";
                if (isset($this->aCfg['password']['cracklib_dict']) && !empty($this->aCfg['password']['cracklib_dict'])) {
                    $rCrackLib = crack_opendict ($this->aCfg['password']['cracklib_dict']);

                    if ($rCrackLib !== false) {
                        $bCrackResult = crack_check ($rCrackLib, $sNewPassword);

                        if ($bCrackResult != true) {
                            // check last message and map it to PASS_* constant
                            $sLastMessage = crack_getlastmessage();
							#echo '<br>LastMessage: '.$sLastMessage;
                            switch (strtolower($sLastMessage)) {
                                case "strong password": {
                                    // hmm, seems as it is strong enough?!
                                    $iResult = iConUser::PASS_OK;
                                    break;
                                }

                                case "it is too short":
                                case "it's way too short": {
                                    $iResult = iConUser::PASS_TO_SHORT;
                                    break;
                                }

                                case "it does not contain enough different characters": {
                                    $iResult = iConUser::PASS_NOT_ENOUGH_DIFFERENT_CHARS;
                                    break;
                                }

                                /*
                                * I list all strings addtionally to default case here for
                                * further "differentiation".
                                */
                                case "it is too simplistic/systematic":
                                case "it is all whitespace":
                                case "it looks like a national insurance number.":
                                case "it is based on a dictionary word":
                                case "it is based on a (reversed) dictionary word":
                                default: {
                                    $iResult = iConUser::PASS_NOT_STRONG;
                                    break;
                                }

                            }
                        }
                    }

                    // close dictionary...
                    crack_closedict($rCrackLib);
                }
            }
        }

        return $iResult;
    }

    /**
     * This static method provides a simple way to get error messages depending
     * on error code $iErrorCode, which is returned by checkPassword* methods.
     *
     * @param int $iErrorCode
	* @param array $aCfg Contenido configuration array
     * @return string
     */
    public static function getErrorString ($iErrorCode, $aCfg) {
        $sError = "";
   
        switch ($iErrorCode) {
            case iConUser::PASS_NOT_ENOUGH_MIXED_CHARS: {
                $sError = sprintf(i18n("Please use at least %d lower and upper case characters in your password!"),
                    $aCfg['password']['mixed_case_mandatory']);
                break;
            }
            case iConUser::PASS_NOT_ENOUGH_NUMBERS: {
                $sError = sprintf(i18n("Please use at least %d numbers in your password!"),
                    $aCfg['password']['numbers_mandatory']);
                break;
            }
            case iConUser::PASS_NOT_ENOUGH_SYMBOLS : {
                $sError = sprintf(i18n("Please use at least %d symbols in your password!"),
                    $aCfg['password']['symbols_mandatory']);
                break;
            }
            case iConUser::PASS_TO_SHORT: {
                $sError = sprintf(i18n("Password is too short! Please use at least %d signs."),
                    ($aCfg['password']['min_length'] >  0 ? $aCfg['password']['min_length'] :
                    iConUser::MIN_PASS_LENGTH_DEFAULT));
                break;
            }
            case iConUser::PASS_NOT_ENOUGH_DIFFERENT_CHARS : {
                $sError = sprintf(i18n("Password does not contain enough different characters."));
                break;
            }
            case iConUser::PASS_NOT_ENOUGH_MIXED_CHARS: {
                $sError = sprintf(i18n("Please use at least %d lower and upper case characters in your password!"),
                    $aCfg['password']['mixed_case_mandatory']);
                break;
           }
            case iConUser::PASS_NOT_STRONG: {
                $sError = i18n("Please choose a more secure password!");
                break;
            }
            default: {
                $sError = "I do not really know whats happened. But your password does not match the
                            policies! Please consult your administrator. The error code is #" . $iErrorCode;
            }

        }

        return $sError;
    }

    /**
     * {@see iConUser::encodePassword()}
     *
     * @param string $sPassword
     * @return string
     */
    public static function encodePassword ($sPassword) {
        return md5($sPassword);
    }
}
?>