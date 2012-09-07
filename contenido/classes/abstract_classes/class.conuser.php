<?php
/**
 * Abstract implementation of iConUser interface.
 *
 * This class is a basic implementation of iConUser interface. It
 * should be used as base class for specific user class.
 *
 * @package Contenido Backend Classes
 * @subpackages Backend User
 *
 * @version $Revision$
 * @author Holger Librenz
 * @copyright four for business AG
 *
 * {@internal
 *  created 2008-11-16
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
 * @package Contenido Backend Classes
 * @subpackage Backend User
 *
 * @version 0.0.1
 * @author Holger Librenz
 * @copyright four for business AG
 */
abstract class ConUser_Abstract implements iConUser {

	/**
	 * Referemces database abstraction instance
	 *
	 * @var DB_Contenido
	 */
	protected $oDb = null;

	/**
	 * Contenido configuration array
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
        if (!is_array($aCfg) || count($aCfg) <= 0) {
        	throw new ConUserException ("Illegal configuration array \$aCfg.");
        } else {
        	$this->aCfg = $aCfg;
        }

        if (is_null($oDb)) {
        	$this->oDb = new DB_Contenido();
        } else {
        	// is it a contenido DB instance?
        	if ($oDb instanceof DB_Contenido) {
        	   $this->oDb = $oDb;
        	} else {
        		throw new ConUserException("Given value for \$oDb is not a valid DB_Contenido instance!");
        	}
        }

        if (!is_null($sUserId)) {
        	$bLoaded = $this->load($sUserId);

        	if ($bLoaded == true) {
        		$this->sUserId = $sUserId;
        	} else {
        		throw new ConUserException("No user with given user ID found!");
        	}
        }
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
		return $this->sUserId;
	}

	/**
	 * Sets user ID.
	 *
	 * @param unknown_type $sUserId
	 *
	 * TODO check this
	 */
	public function setUserId ($sUserId) {
		$this->sUserId = $sUserId;
	}

	/**
	 * Generates new user id based on current user name.
	 *
	 * @return string
	 */
	public function generateUserId () {
		$sResult = "";

		$sCurUserName = $this->getUserName();

		if (!empty($sCurUserName)) {
			$sResult = md5($sCurUserName);
		} else {
			throw new ConUserException("No user name set yet");
		}

		$this->sUserId = $sResult;

		return $sResult;
	}

    /**
     * Returns user name, currently set
     *
     * @return string
     */
	public function getUserName () {
		return $this->sUserName;
	}

	/**
	 * Sets up new user name.
	 *
	 * @param string $sUserName
	 */
	public function setUserName ($sUserName) {
		$this->sUserName = $sUserName;
	}

	/**
	 * Checks password which has to be set and return PASS_* values (i.e.
	 * on success PASS_OK).
	 *
	 * @param string $sPassword
	 * @return int
	 */
	public function setPassword ($sPassword) {
	   $iResult = iConUser::PASS_OK;

	   $iMaskResult = $this->checkPasswordMask($sPassword);
	   if ($iMaskResult != iConUser::PASS_OK) {
	       $iResult = $iMaskResult;
	   } else {
	       $iStrengthResult = $this->checkPasswordStrength($sPassword);

	       if ($iStrengthResult != iConUser::PASS_OK) {
	           $iResult = $iStrengthResult;
	       } else {
	           $this->sPassword = $sPassword;
	       }
	   }

	   return $iResult;
	}

	/**
	 * Returns (unencoded!) password. This method should never be public
	 * available!
	 *
	 * @return string
	 */
	protected function getPassword () {
	    return $this->sPassword;
	}
}

?>