<?php
/**
 * Abstract implementation of iConUser interface.
 *
 * This class is a basic implementation of iConUser interface. It
 * should be used as base class for specific user class.
 *
 * @package  CONTENIDO Backend Classes
 * @subpackages Backend User
 *
 * @version 1.0.0
 * @author Holger Librenz
 * @copyright four for business AG
 *
 * {@internal
 *  created 2008-11-16
 *
 * $Id:$
 * }}
 */

// include interface...
cInclude('interfaces', 'interface.conuser.php');

// include exception
cInclude('exceptions', 'exception.conuser.php');

/**
 * This abstract class implements interface iConUser and should
 * be user as base class for backend user classes.
 *
 * @package  CONTENIDO Backend Classes
 * @subpackage Backend User
 *
 * @version 0.0.1
 * @author Holger Librenz
 * @copyright four for business AG
 * 
 * @deprecated Please use cApiUser instead [2012-02-23]
 */
abstract class ConUser_Abstract implements iConUser {

	/**
	 * Referemces database abstraction instance
	 *
	 * @var DB_Contenido
	 */
	protected $oDb = null;

	/**
	 *  CONTENIDO configuration array
	 *
	 * @var array
	 */
	protected $aCfg = null;

	/**
	 * current User ID
	 *
	 * @var string
	 */
	private $sUserId = null;

	/**
	 * Login name of current user.
	 *
	 * @var string
	 */
	private $sUserName = null;

	/**
	 * Holds the password which should be set.
	 *
	 * @var unknown_type
	 */
	private $sPassword = null;

	/**
	 * Constructor
	 *
	 * Checks given values and initializes class.
	 *
	 * @throws ConUserException
	 */
	function __construct($aCfg, $oDb = null, $sUserId = null) {
    	cDeprecated("Deprecated class. Please use cApiUser instead");
	}

//	/**
//	 * This method checks "the mask" of password $sNewPassword. If
//	 * it matches the administrators rules iConUser::PASS_OK will be
//	 * returned.
//	 *
//	 * In this abstract class, it always returns PASS_OK!
//	 *
//	 * @param string $sNewPassword
//	 * @return int
//	 *
//	 * @see iConUser::checkPasswordMask()
//	 */
//	public static function checkPasswordMask($sNewPassword) {
//		return iConUser::PASS_OK;
//	}

//	/**
//	 * Returns true if password $sNewPassword is strong enough.
//	 *
//	 * In this abstract class, it always returns true.
//	 *
//	 * @param string $sNewPassword
//	 * @return int
//	 *
//	 * @see iConUser::checkPasswordStrength()
//	 */
//	public static function checkPasswordStrength($sNewPassword) {
//        return iConUser::PASS_OK;
//	}

	/**
	 * Returns user id, currently set.
	 *
	 * @return string
	 */
	public function getUserId () {
    	cDeprecated("Deprecated class. Please use cApiUser instead");
	}

	/**
	 * Sets user ID.
	 *
	 * @param unknown_type $sUserId
	 *
	 * TODO check this
	 */
	public function setUserId ($sUserId) {
    	cDeprecated("Deprecated class. Please use cApiUser instead");
	}

	/**
	 * Generates new user id based on current user name.
	 *
	 * @return string
	 */
	public function generateUserId () {
    	cDeprecated("Deprecated class. Please use cApiUser instead");
	}

    /**
     * Returns user name, currently set
     *
     * @return string
     */
	public function getUserName () {
    	cDeprecated("Deprecated class. Please use cApiUser instead");
	}

	/**
	 * Sets up new user name.
	 *
	 * @param string $sUserName
	 */
	public function setUserName ($sUserName) {
    	cDeprecated("Deprecated class. Please use cApiUser instead");
	}

	/**
	 * Checks password which has to be set and return PASS_* values (i.e.
	 * on success PASS_OK).
	 *
	 * @param string $sPassword
	 * @return int
	 */
	public function setPassword ($sPassword) {
		cDeprecated("Deprecated class. Please use cApiUser instead");
	}

	/**
	 * Returns (unencoded!) password. This method should never be public
	 * available!
	 *
	 * @return string
	 */
	protected function getPassword () {
    	cDeprecated("Deprecated class. Please use cApiUser instead");
	}
}

?>