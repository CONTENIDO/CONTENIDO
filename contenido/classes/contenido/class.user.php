<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * User access class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO API
 * @version    1.8
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2007-06-24
 *   modified 2011-03-15, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *   modified 2011-02-05, Murat Purc, takeover roperty management from User class
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/**
 * User collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiUserCollection extends ItemCollection
{
    public function __construct($select = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['phplib_auth_user_md5'], 'user_id');
        $this->_setItemClass('cApiUser');
        if ($select !== false) {
            $this->select($select);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiUserCollection($select = false)
    {
        cDeprecated("Use __construct() instead");
        $this->__construct($select);
    }

    /**
     * Createa a user by user name.
     *
     * @param  string  $username
     * @return  cApiUser|false
     */
    public function create($username)
    {
        if ($this->next()) {
            return false;
        } else {
            $item = parent::create();
            $item->set('username', $username);
            $item->store();

            return $item;
        }
    }
}


/**
 * User item
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiUser extends Item
{
	/**
	* Password is ok and stored.
	*
	* @var int
	* @final
	*/
	const PASS_OK = 0;
	
	/**
	 * Given password is to short
	 *
	 * @var int
	 * @final
	 */
	const PASS_TO_SHORT =  1;
	
	/**
	 * Given password is not strong enough
	 *
	 * @var int
	 * @final
	 */
	const PASS_NOT_STRONG =  2;
	
	/**
	 * Given password is not complex enough
	 *
	 * @var int
	 * @final
	 */
	const PASS_NOT_COMPLEX =  3;
	
	/**
	 * Password does not contain enough numbers.
	 *
	 * @var int
	 * @final
	 */
	const PASS_NOT_ENOUGH_NUMBERS =  4;
	
	/**
	 * Password does not contain enough symbols.
	 *
	 * @var int
	 * @final
	 */
	const PASS_NOT_ENOUGH_SYMBOLS =  5;
	
	/**
	 * Password does not contain enough mixed characters.
	 *
	 * @var int
	 * @final
	 */
	const PASS_NOT_ENOUGH_MIXED_CHARS =  6;
	
	/**
	 * Password does not contain enough different characters.
	 *
	 * @var int
	 * @final
	 */
	const PASS_NOT_ENOUGH_DIFFERENT_CHARS =  7;
	
	/**
	 * Exception code, which is used if you try to add an user
	 * that already exists.
	 *
	 * @var int
	 * @final
	 */
	const EXCEPTION_USERNAME_EXISTS =  8;
	
	
	/**
	 * Exception code, which is used if an password is set to save
	 * that is not valid.
	 *
	 * @var int
	 * @final
	 */
	const EXCEPTION_PASSWORD_INVALID =  9;
	
	/**
	 * This value will be used if no minimum length
	 * for passwords are set via $cfg['password']['min_length']
	 *
	 */
	const MIN_PASS_LENGTH_DEFAULT = 8;
	
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['phplib_auth_user_md5'], 'user_id');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiUser($mId = false)
    {
        cDeprecated("Use __construct() instead");
        $this->__construct($mId);
    }
    
    /**
     * Wrapper for store() for downwards compatibility
     *
     * @return bool Return value of store()
     */
    public function save()
    {
    	cDeprecated("Use cApiUser::store() instead.");
    	
    	return $this->store();
    }
    
    /**
     * Checks if a user with the id $userId exists
     *
     * @return bool user exists or not
     */
    public static function userExists($userId)
    {
    	$test = new cApiUser();
    	
    	return $test->loadByPrimaryKey($userId);
    }
    
    /**
     * Checks if a username exists
     * 
     * @param string $username the name
     * @return bool username exists or not
     */
    public static function usernameExists($username)
    {
    	$test = new cApiUser();
    	
    	return $test->loadBy("username", $username);
    }
    
    public static function encodePassword($password)
    {
    	return md5($password);
    }
    
    public static function checkPasswordMask($password)
    {
    	global $cfg;
    	
    	$iResult = cApiUser::PASS_OK;
    	
    	if (isset($cfg['password']['check_password_mask']) &&
    	$cfg['password']['check_password_mask'] == true) {
    		// any min length in config set?
    		$iMinLength = cApiUser::MIN_PASS_LENGTH_DEFAULT;
    		if (isset( $cfg ['password'] ['min_length'] )) {
    			$iMinLength = ( int ) $cfg ['password'] ['min_length'];
    		}
    	
    		// check length...
    		if (strlen ( $sNewPassword ) < $iMinLength) {
    			$iResult = cApiUser::PASS_TO_SHORT;
    		}
    	
    		// check password elements
    	
    		// numbers.....
    		if ($iResult == cApiUser::PASS_OK && isset($cfg['password']['numbers_mandatory']) &&
    		(int) $cfg['password']['numbers_mandatory'] > 0) {
    	
    			$aNumbersInPassword = array();
    			preg_match_all("/[0-9]/", $sNewPassword, $aNumbersInPassword) ;
    	
    			if (count($aNumbersInPassword[0]) < (int) $cfg['password']['numbers_mandatory']) {
    				$iResult = cApiUser::PASS_NOT_ENOUGH_NUMBERS;
    			}
    		}
    	
    		// symbols....
    		if ($iResult == cApiUser::PASS_OK && isset($cfg['password']['symbols_mandatory']) &&
    		(int) $cfg['password']['symbols_mandatory'] > 0) {
    	
    			$aSymbols = array();
    			$sSymbolsDefault = "/[|!@#$%&*\/=?,;.:\-_+~^¨\\\]/";
    			if (isset($cfg['password']['symbols_regex']) && !empty($cfg['password']['symbols_regex'])) {
    				$sSymbolsDefault = $cfg['password']['symbols_regex'];
    			}
    	
    			preg_match_all($sSymbolsDefault, $sNewPassword, $aSymbols);
    	
    			if (count($aSymbols[0]) < (int) $cfg['password']['symbols_mandatory']) {
    				$iResult = cApiUser::PASS_NOT_ENOUGH_SYMBOLS;
    			}
    		}
    	
    		// mixed case??
    		if ($iResult == cApiUser::PASS_OK && isset($cfg['password']['mixed_case_mandatory']) &&
    		(int) $cfg['password']['mixed_case_mandatory'] > 0) {
    	
    			$aLowerCaseChars = array();
    			$aUpperCaseChars = array();
    	
    			preg_match_all("/[a-z]/", $sNewPassword, $aLowerCaseChars);
    			preg_match_all("/[A-Z]/", $sNewPassword, $aUpperCaseChars);
    	
    			if ((count($aLowerCaseChars[0]) < (int) $cfg['password']['mixed_case_mandatory']) ||
    			(count($aUpperCaseChars[0]) < (int) $cfg['password']['mixed_case_mandatory'])) {
    				$iResult = cApiUser::PASS_NOT_ENOUGH_MIXED_CHARS;
    			}
    		}
    	}
    	
    	return $iResult;
    }
    
    /**
    * Returns user id, currently set.
    *
    * @return string
    */
    public function getUserId()
    {
    	return $this->get("user_id");
    }
    
    public function setUserId($uid)
    {
    	$this->loadByPrimaryKey($uid);
    }
    
	/**
	 * Checks password which has to be set and return PASS_* values (i.e.
	 * on success PASS_OK).
	 *
	 * @param string $sPassword
	 * @return int
	 */
    public function setPassword($password)
    {
    	$result = self::checkPasswordMask($password);
    	if($result != cApiUser::PASS_OK)
    	{
    		return $result;
    	}
    	
    	$encPass = self::encodePassword($password);
    	
    	$this->set("password", $encPass);
    	$this->set("using_pw_request", "0");
    	
    	return $result;
    }
    
    /**
    * This method saves the given password $sNewPassword. The password
     * has to be checked, before it is set to the database. The resulting
    * integer value represents the result code.
    * Use the PASS_* constants to check what happens.
    *
    * @param string $password
    * @return int|bool returns PASS_* or false if saving fails
    */
    public function savePassword($password)
    {
    	if($this->get("password") == self::encodePassword($password))
    	{
    		return cApiUser::PASS_OK;
    	}
    	
    	$result = $this->setPassword($password);
    	
    	if($this->store() === false)
    	{
    		return false;
    	}
    	else
    	{
    		return $result;
    	}
    }
    
    /**
    * Returns user name, currently set
     *
    * @return string
    */
    public function getUserName () {
    	return $this->get("username");
    }
    
    /**
    * Sets up new user name.
    *
    * @param string $sUserName
    */
    public function setUserName ($sUserName) {
    	$this->set("username", $sUserName);
    }
    
    /**
    * Getter method to get user realname
    * @return string Realname of user
    */
    public function getRealName() {
    	return $this-get("realname");
    }
    
    /**
     * Getter method to get user mail
     * @return string Realname of user
     */
    public function getMail() {
    	return $this->get("email");
    }
    
    /**
     * Getter method to get user tel number
     * @return string Realname of user
     */
    public function getTelNumber() {
    	return $this->get("telephone");
    }
    
    /**
     * Getter method to get user adress data
	 * $aAddress['street'], $aAddress['city'], $aAddress['country'],  $aAddress['zip']
     * @return string Realname of user
     */
    public function getAddressData() {
    	$aret = array();
    	
    	$aret['street'] = $this->get("address_street");
    	$aret['city'] = $this->get("address_city");
    	$aret['country'] = $this->get("address_country");
    	$aret['zip'] = $this->get("address_zip");
    	return $aret;
    }
    
    /**
     * Getter method to get user wysi
     * @return string Realname of user
     */
    public function getUseTiny() {
    	return $this->get("wysi");
    }
    
    /**
     * Getter method to get user valid date from-to
     * @return string Realname of user
     */
    public function getValidDateTo() {
    	return $this->get("valid_to");
    }
    
    /**
     * Getter method to get user valid date from-to
     * @return string Realname of user
     */
    public function getValidDateFrom() {
    	return $this->get("valid_from");
    }
    
    /**
     * Getter method to get user perm name
     * @return string Realname of user
     */
    public function getPerms() {
    	return $this->get("perms");
    }
    
    /**
     * Setter method to set user real name
     * @return void
     */
    public function setRealName($sRealName) {
    	$this->set("realname", $sRealName);
    }
    
    /**
     * Setter method to set user mail address
     * @return void
     */
    public function setMail($sMail) {
    	$this->set("email", $sMail);
    }
    
    /**
     * setter method to set user tel number
     * @return void
     */
    public function setTelNumber($sTelNumber) {
    	$this->set("telephone", $sTelNumber);
    }
    
    /**
     * Setter method to set Adress Data
     * @return void
     */
    public function setAddressData($sAddressStreet, $sAddressCity, $sAddressZip, $sAddressCountry) {
    	$this->set("address_street", $sAddressStreet);
    	$this->set("address_city", $sAddressCity);
    	$this->set("address_zip", $sAddressZip);
    	$this->set("address_country", $sAddressCountry);
    }
    
    /**
     * Sets value for street.
     *
     * @param string $sStreet
     */
    public function setStreet ($sStreet) {
    	$this->set("address_street", $sStreet);
    }
    
    /**
     * Sets value for city.
     *
     * @param string $sCity
     */
    public function setCity ($sCity) {
    	$this->set("address_city", $sCity);
    }
    
    /**
     * Sets value for ZIP.
     *
     * @param string $sZip
     */
    public function setZip ($sZip) {
    	$this->set("address_zip", $sZip);
    }
    
    /**
     * Sets value for country.
     *
     * @param string $sCountry
     */
    public function setCountry ($sCountry) {
    	$this->set("address_country", $sCountry);
    }
    
    /**
     * Setter method to set
     * @return void
     */
    public function setUseTiny($iUseTiny) {
    	$this->set("wysi", $iUseTiny);
    }

    /**
	 * setter method to set User
	 *
	 * @return void
	 *
	 * TODO add type check
	 */
    public function setValidDateTo($sValidateTo) {
    	$this->set("valid_to", $sValidateTo);
    }

    /**
	 * setter method to set
	 *
	 * @return void
	 *
	 * TODO add type checks
	 */
    public function setValidDateFrom($sValidateFrom) {
    	$this->set("valid_from", $sValidateFrom);
    }

    /**
	 * setter method to set
	 *
	 * @return void
	 *
	 * TODO add type checks
	 */
    public function setPerms($aPerms) {
        $this->set("perms", implode ( ",", $aPerms ));
    }

    /**
     * Retrieves the effective user property.
     * @param  string  $type   Type (class, category etc) for the property to retrieve
     * @param  string  $name   Name of the property to retrieve
     * @param  bool    $group  Flag to search in groups
     * @return string|bool  The value of the retrieved property or false
     */
    public function getUserProperty($type, $name, $group = false)
    {
        global $perm;

        if (!is_object($perm)) {
            $perm = new Contenido_Perm();
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

        return ($result !== false) ? urldecode($result) : false;
    }

    /**
     * Returns all user properties by type.
     *
     * @todo  return value should be similar to getUserProperties()
     *
     * @param   string  $type    Type (class, category etc) of the properties to retrieve
     * @param   bool    $group   Flag to retrieve in group properties. If enabled, group properties
     *                           will be merged with user properties where the user poperties will
     *                           overwrite group properties
     * @return  array   Assoziative properties array as follows:
     *                  - $arr[name] = value
     */
    public function getUserPropertiesByType($type, $group = false)
    {
        global $perm;

        if (!is_object($perm)) {
            $perm = new Contenido_Perm();
        }

        $props = array();

        if ($group == true) {
            // first get properties by existing groups, if desired
            $groups = $perm->getGroupsForUser($this->values['user_id']);
            foreach ($groups as $groupid) {
                $groupPropColl = new cApiGroupPropertyCollection($groupid);
                $groupProps = $groupPropColl->fetchByGroupIdType($type);
                foreach ($groupProps as $groupProp) {
                    $props[$groupProp->get('name')] = urldecode($groupProp->get('value'));
                }
            }
        }

        // get properties of user
        $userPropColl = new cApiUserPropertyCollection($this->values['user_id']);
        $userProps = $userPropColl->fetchByUserIdType($type);
        foreach ($userProps as $userProp) {
            $props[$userProp->get('name')] = urldecode($userProp->get('value'));
        }

        return $props;
    }

    /**
     * Retrieves all available properties of the user.
     * Works with a downwards compatible mode.
     *
     * NOTE: Even if downwards compatible mode is enbabled by default, this mode is deprecated...
     *
     * @param  bool  $beDownwardsCompatible  Flag to return downwards compatible values
     * @return array|bool  Returns a array or false in downwards compatible mode, otherwhise a array.
     *                     Return value in new mode is:
     *                     - $arr[iduserprop][name]
     *                     - $arr[iduserprop][type]
     *                     - $arr[iduserprop][value]
     *                     Return value in downwards compatible mode is:
     *                     - $arr[pos][name]
     *                     - $arr[pos][type]
     */
    public function getUserProperties($beDownwardsCompatible = true)
    {
        $userPropColl = new cApiUserPropertyCollection($this->values['user_id']);
        $userProps = $userPropColl->fetchByUserId();

        $props = array();

        if (true === $beDownwardsCompatible) {
            // @deprecated  [2011-11-03]
        	cDeprecated("$beDownwardsCompatible should not be true");
            if (count($userProps) == 0) {
                return false;
            }

            foreach ($userProps as $userProp) {
                $props[] = array(
                    'name' => $userProp->get('name'),
                    'type' => $userProp->get('type')
                );
            }
        } else {
            foreach ($userProps as $userProp) {
                $props[$userProp->get('iduserprop')] = array(
                    'name'  => $userProp->get('name'),
                    'type'  => $userProp->get('type'),
                    'value' => $userProp->get('value'),
                );
            }
        }

        return $props;
    }

    /**
     * Stores a property to the database
     * @param  string  $type  Type (class, category etc) for the property to retrieve
     * @param  string  $name  Name of the property to retrieve
     * @param  string  $value Value to insert
     * @return cApiUserProperty
     */
    public function setUserProperty($type, $name, $value)
    {
        $userPropColl = new cApiUserPropertyCollection($this->values['user_id']);
        $userProps = $userPropColl->setValueByTypeName($type, $name, $value);
    }

    /**
     * Deletes a user property from the table.
     * @param   string  $type  Type (class, category etc) of property to retrieve
     * @param   string  $name  Name of property to retrieve
     * @return  bool
     */
    public function deleteUserProperty($type, $name)
    {
        $userPropColl = new cApiUserPropertyCollection($this->values['user_id']);
        return $userPropColl->deleteByUserIdTypeName($type, $name);
    }
    
    /**
    * This static method provides a simple way to get error messages depending
     * on error code $iErrorCode, which is returned by checkPassword* methods.
    *
    * @param int $iErrorCode
    * @param array $aCfg CONTENIDO configuration array
    * @return string
    */
    public static function getErrorString ($iErrorCode, $aCfg) {
	    $sError = "";
	     
	    switch ($iErrorCode) {
	    	case cApiUser::PASS_NOT_ENOUGH_MIXED_CHARS: {
		    	$sError = sprintf(i18n("Please use at least %d lower and upper case characters in your password!"),
		        $aCfg['password']['mixed_case_mandatory']);
		    	break;
	    	}
	    	case cApiUser::PASS_NOT_ENOUGH_NUMBERS: {
	    		$sError = sprintf(i18n("Please use at least %d numbers in your password!"),
	    		$aCfg['password']['numbers_mandatory']);
	    		break;
	    	}
	        case cApiUser::PASS_NOT_ENOUGH_SYMBOLS : {
	    		$sError = sprintf(i18n("Please use at least %d symbols in your password!"),
	    		$aCfg['password']['symbols_mandatory']);
	    		break;
	    	}
	    	case cApiUser::PASS_TO_SHORT: {
	    		$sError = sprintf(i18n("Password is too short! Please use at least %d signs."),
	    		($aCfg['password']['min_length'] >  0 ? $aCfg['password']['min_length'] :
	    		cApiUser::MIN_PASS_LENGTH_DEFAULT));
	    		break;
	    	}
	    	case cApiUser::PASS_NOT_ENOUGH_DIFFERENT_CHARS : {
	    		$sError = sprintf(i18n("Password does not contain enough different characters."));
                break;
	    	}
	        case cApiUser::PASS_NOT_ENOUGH_MIXED_CHARS: {
		    	$sError = sprintf(i18n("Please use at least %d lower and upper case characters in your password!"),
		    	$aCfg['password']['mixed_case_mandatory']);
	            break;
	        }
	        case cApiUser::PASS_NOT_STRONG: {
		    	$sError = i18n("Please choose a more secure password!");
		    	break;
	    	}
	    	default: {
		    	$sError = "I do not really know what has happened. But your password does not match the
		    	policies! Please consult your administrator. The error code is #" . $iErrorCode;
	    	}
	    
	    }
	    
	    return $sError;
    }
}

?>