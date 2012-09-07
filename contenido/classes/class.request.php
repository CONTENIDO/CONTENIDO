<?php


/**
 * Request class
 * @version $Id: request.class.php 2289 2008-09-29 13:46:08Z atelierq $
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright &copy; 2008, w3concepts AG
 */

/*
 * Example usage
 */
/*
Request :: getInstance()
	->register(request :: GET, 'myFirstPara', request :: TYPE_STRING | request :: TYPE_ARRAY, 2, 10)
	->register(request :: GET, 'mySecondPara', request :: TYPE_STRING, null, null, null, request :: TRANS_STRIP_HTML)
	->register(request :: GET, 'myThirdPara', request :: TYPE_INTEGER | request :: TYPE_ARRAY, 0, 100)
	->finish();

echo '<pre>';
var_dump(Request :: get('myFirstPara')); // normal usage
var_dump(Request :: get('mySecondPara')); // normal usage
var_dump(Request :: request('myThirdPara', true)); // still has the ValidationError objects in case of errors.
var_dump(Request :: hasValidationErrors(Request :: GET, array ('myFirstPara', 'mySecondPara', 'myThirdPara')); // returns true, if a validation error occured.
echo '</pre>';
*/

class Request {

	const POST = 'POST';
	const GET = 'GET';
	const COOKIE = 'COOKIE';

	const TYPE_INTEGER = 1;
	const TYPE_FLOAT = 2;
	const TYPE_STRING = 4;
	const TYPE_ARRAY = 8;

	const TRANS_STRIP_HTML = 1;
	const TRANS_STRIP_PHP = 2;

	const DATE_PATTERN_ISO = '/^\\d{4}\\-\\d{2}\\-\\d{2}$/';
	const DATE_PATTERN_DE = '/^\\d{2}\\.\\d{2}\\.(?:\\d{2}|\\d{4})$/';
	const DATETIME_PATTERN = '/^\\d{4}\\-\\d{2}\\-\\d{2}\\s\\d{2}\\:\\d{2}$/';
	const DATETIME_PATTERN_DE = '/^\\d{2}\\.\\d{2}\\.(?:\\d{2}|\\d{4})\\s\\d{2}.\\d{2}$/';
	const HEXDEC_HASH = '/^[\\da-f]*$/';

	private $POST;
	private $GET;
	private $COOKIE;

	private $POSTcleared;
	private $GETcleared;
	private $COOKIEcleared;

	private $bHasValidationErrors;

	/**
	 * Constructor.
	 * @access private
	 */
	private function __construct() {

		$this->POST = array ();
		$this->COOKIE = array ();
		$this->GET = array ();
	}

	/**
	 * Factory.
	 * @static
	 * @access public
	 * @return Object Reference to the current single instance of the class.
	 */
	public static function getInstance() {

		static $oCurrentInstance;

		if (!isset ($oCurrentInstance)) {
			$oCurrentInstance = new self();
		}

		return $oCurrentInstance;
	}

	/**
	 * Registers a request variable with the request class.
	 * @access public
	 * @param String sRequestType Either POST, GET or COOKIE.
	 * @param String sName Parameter name.
	 * @param Integer iType Parameter type (either integer, float or string).
	 * @param Float iMin Minimum length (string) or minimum value (float, integer).
	 * @param Float iMax Maximum length (string) or maximum value (float, integer).
	 * @param String sRegex Regex pattern the value is checked against.
	 * @return Object Reference to the current instance to enable method chaining.
	 */
	public function register($sRequestType, $sName, $iType, $iMin = null, $iMax = null, $sRegex = null, $iTransformation = null) {

		if ($sRequestType == self :: POST) {
			$mRequest = $_POST;
		}
		elseif ($sRequestType == self :: GET) {
			$mRequest = $_GET;
		}
		elseif ($sRequestType == self :: COOKIE) {
			$mRequest = $_COOKIE;
		}

		if (!array_key_exists($sName, $mRequest) || strlen($mRequest[$sName]) == 0) {
			/*
			 * The specified paramter does not exist.
			 */
			$this-> {
				$sRequestType }
			[$sName] = null;
			return $this;
		}

		if (is_array($mRequest[$sName]) && ($iType & self :: TYPE_ARRAY) != self :: TYPE_ARRAY) {
			/*
			 * The parameter is of type array and array is 
			 * not allowed according to the registration.
			 */
			$this-> {
				$sRequestType }
			[$sName] = ValidationError :: set(ValidationError :: NOT_SCALAR, $mRequest[$sName], 'The given value is not scalar.');
			return $this;
		}

		if (is_array($mRequest[$sName])) {
			array_walk_recursive($mRequest[$sName], array (
				'self',
				'validateArray'
			), array (
				'iType' => $iType,
				'iMin' => $iMin,
				'iMax' => $iMax,
				'sRegex' => $sRegex,
				'iTransformation' => $iTransformation
			));
			$this-> {
				$sRequestType }
			[$sName] = $mRequest[$sName];
			return $this;
		}

		$this-> {
			$sRequestType }
		[$sName] = $this->validateValue($mRequest[$sName], $iType, $iMin, $iMax, $sRegex, $iTransformation);

		return $this;
	}

	/**
	 * Callback function for validating items of arrays.
	 * @static
	 * @access private
	 * @param Reference mItem Reference to the item of the array to be validated.
	 * @param Mixed mKey Key of the current item to be processed.
	 * @param Array aParams Parameter array to be used in the validation process.
	 * @return Void 
	 */
	private static function validateArray(& $mItem, $mKey, $aParams) {

		$mItem = self :: validateValue($mItem, $aParams['iType'], $aParams['iMin'], $aParams['iMax'], $aParams['sRegex'], $aParams['iTransformation']);
	}

	/**
	 * Callback function to clear the resulting arrays from ValidationError objects.
	 * @static
	 * @access private
	 * @param Reference mItem Reference to the item of the array to be cleared.
	 * @param Mixed mKey Key of the current item to be processed.
	 * @return Void 
	 */
	private static function setErrorsToNull(& $mItem, $mKey) {

		if (is_object($mItem)) {
			$mItem = null;
		}
	}

	/**
	 * Sets the member bValidationError to the given value.
	 * @param Boolean bValidationError True, if a validation error occured.
	 * @return Void 
	 */
	private function setValidationError($bValidationError) {
		$this->bHasValidationErrors = $bValidationError;
	}

	/**
	 * Returns true, if one of the specified parameters has given a
	 * validation error.
	 * @param String sRequestType Request type.
	 * @param Array aParams Array of parameters to be checked.
	 * @return Boolean True if no validation errors occured. False otherwise.
	 */
	public static function hasValidationErrors($sRequestType, $aParams) {

		$oCurrentInstance = self :: getInstance();
		
		if ($sRequestType == self :: POST) {
			$mRequest = $oCurrentInstance->POST;
		}
		elseif ($sRequestType == self :: GET) {
			$mRequest = $oCurrentInstance->GET;
		}
		elseif ($sRequestType == self :: COOKIE) {
			$mRequest = $oCurrentInstance->COOKIE;
		}

		$oCurrentInstance->setValidationError(false);

		foreach ($aParams as $sName) {
			if (is_array($mRequest[$sName])) {
				array_walk_recursive($mRequest[$sName], array (
					'self',
					'hasError'
				));
			} elseif (is_object($mRequest[$sName])) {
				$oCurrentInstance->setValidationError(true);
			}
		}

		return $oCurrentInstance->bHasValidationErrors;
	}

	/**
	 * Callback function to check whether a validation error
	 * occured or not.
	 * @static
	 * @access private
	 * @param Reference mItem Reference to the current item.
	 * @param Mixed mKey Current key.
	 * @return Void 
	 */
	private static function hasError(& $mItem, $mKey) {

		echo '<pre>';
		var_dump($mItem);
		echo '</pre>';
		
		if (is_object($mItem)) {
			self :: getInstance()->setValidationError(true);
		}
	}

	/**
	 * Replaces all error objects with null. Must be called after registering
	 * the required parameters.
	 * @return Object Reference to the current instance.
	 */
	public function finish() {

		$this->POSTcleared = $this->POST;
		$this->GETcleared = $this->GET;
		$this->COOKIEcleared = $this->COOKIE;

		array_walk_recursive($this->POSTcleared, array (
			'self',
			'setErrorsToNull'
		));

		array_walk_recursive($this->GETcleared, array (
			'self',
			'setErrorsToNull'
		));

		array_walk_recursive($this->COOKIEcleared, array (
			'self',
			'setErrorsToNull'
		));

		return $this;
	}

	/**
	 * Validates the value against type, range, pattern, optionally after a transformation process.
	 * @static
	 * @access private
	 * @param Mixed mValue Value to be validated.
	 * @param Integer iType Type (integer, float or string).
	 * @param Integer iMin Minimum value (integer and float) or minimum length (string).
	 * @param Integer iMax Maximum value (integer and float) or maximum length (string).
	 * @param Integer iTransformation Sum of transformations to be applied.
	 * @return Mixed Either the transformed and validated value or null.
	 */
	private static function validateValue($mValue, $iType, $iMin, $iMax, $sRegex, $iTransformation) {

		$mValue = self :: transform($mValue, $iTransformation);

		if (($iType & self :: TYPE_INTEGER) == self :: TYPE_INTEGER && !self :: isInteger($mValue)) {
			/*
			 * Type should be integer, is something else
			 */
			return ValidationError :: set(ValidationError :: NOT_INTEGER, $mValue, 'The given value is not of type integer.');
		}

		if (($iType & self :: TYPE_FLOAT) == self :: TYPE_FLOAT && !self :: isFloat($mValue)) {
			/*
			 * Type should be float, is something else
			 */
			return ValidationError :: set(ValidationError :: NOT_FLOAT, $mValue, 'The given value is not a number.');
		}

		if (($iType & self :: TYPE_INTEGER) == self :: TYPE_INTEGER || ($iType & self :: TYPE_FLOAT) == self :: TYPE_FLOAT) {
			if (($iMin != null && $mValue < $iMin) || ($iMax != null && $mValue > $iMax)) {
				/*
				 * Value is either lower than min or greater than max.
				 */
				if ($iMin != null && $mValue < $iMin) {
					return ValidationError :: set(ValidationError :: TOO_SMALL, $mValue, 'The given value is too small.');
				} else {
					return ValidationError :: set(ValidationError :: TOO_BIG, $mValue, 'The given value is too big.');
				}
			}
		}

		if (($iType & self :: TYPE_STRING) == self :: TYPE_STRING) {
			if (($iMin != null && strlen($mValue) < $iMin) || ($iMax != null && strlen($mValue) > $iMax)) {
				/*
				 * String length is either too short or too long.
				 */
				if ($iMin != null && strlen($mValue) < $iMin) {
					return ValidationError :: set(ValidationError :: TOO_SHORT, $mValue, 'The given string is too short.');
				} else {
					return ValidationError :: set(ValidationError :: TOO_LONG, $mValue, 'The given string is too long.');
				}
			}
		}

		if ($sRegex != null && !preg_match($sRegex, $mValue)) {
			/*
			 * Value does not match the given pattern.
			 */
			return ValidationError :: set(ValidationError :: WRONG_PATTERN, $mValue, 'The given string does not match the given pattern.');
		}

		return $mValue;
	}

	/**
	 * Transforms the value according to the given rules.
	 * @static
	 * @access private
	 * @param String sValue Value to be transformed.
	 * @param Integer iRules Sum of the transformation rules to be applied.
	 * @return String Transformed value.
	 */
	private static function transform($sValue, $iRules) {

		if (($iRules & self :: TRANS_STRIP_HTML) == self :: TRANS_STRIP_HTML) {
			$sValue = strip_tags($sValue);
		}

		if (($iRules & self :: TRANS_STRIP_PHP) == self :: TRANS_STRIP_PHP) {
			$sValue = strip_tags($sValue);
		}

		return $sValue;
	}

	/**
	 * Checks whether or not the given value is a integer value.
	 * @static
	 * @access private
	 * @param Mixed mValue Value to be checked.
	 * @return Boolean True if the value is a integer value.
	 */
	private static function isInteger($mValue) {

		if (!is_numeric($mValue)) {
			return false;
		}

		return $mValue == round($mValue);
	}

	/**
	 * Checks whether or not the given value is a double value.
	 * @static
	 * @access private
	 * @param Mixed mValue Value to be checked.
	 * @return Boolean True if the value is a float or integer value.
	 */
	private static function isFloat($mValue) {

		return is_numeric($mValue);
	}

	/**
	 * Returns the parameter specified by sName and the request type sRequestType.
	 * The method throws an exception if the specified paramter has not yet been registered.
	 * @access private
	 * @param String sRequestType Request type (GET, POST or COOKIE).
	 * @param String sName Name of the parameter.
	 * @return Mixed Value of the specified parameter.
	 */
	private function getVar($sRequestType, $sName, $bWithErrors = false) {

		if (!array_key_exists($sName, $this-> $sRequestType)) {
			throw new Exception('Request variable ' . $sName . ' is not registered yet. Please register it first.');
		}

		if (!$bWithErrors) {
			$sRequestType .= 'cleared';
		}

		return $this-> {
			$sRequestType }
		[$sName];
	}

	private function isRegistered($sName, $sRequestType) {
		return array_key_exists($sName, $this-> $sRequestType);
	}

	/**
	 * Returns the GET value of the specified parameter. Null if it does
	 * not exist or if it is not valid.
	 * @static
	 * @access public
	 * @param String sName Name of the parameter.
	 * @return Mixed Value of the parameter or null.
	 */
	public static function get($sName, $bWithErrors = false) {
		return self :: getInstance()->getVar(self :: GET, $sName, $bWithErrors);
	}

	/**
	 * Returns the POST value of the specified parameter. Null if it does
	 * not exist or if it is not valid.
	 * @static
	 * @access public
	 * @param String sName Name of the parameter.
	 * @return Mixed Value of the parameter or null.
	 */
	public static function post($sName, $bWithErrors = false) {
		return self :: getInstance()->getVar(self :: POST, $sName, $bWithErrors);
	}

	/**
	 * Returns the COOKIE value of the specified parameter. Null if it does
	 * not exist or if it is not valid.
	 * @static
	 * @access public
	 * @param String sName Name of the parameter.
	 * @return Mixed Value of the parameter or null.
	 */
	public static function cookie($sName, $bWithErrors = false) {
		return self :: getInstance()->getVar(self :: COOKIE, $sName, $bWithErrors);
	}

	/**
	 * Returns the REQUEST value of the specified parameter. Null if it does
	 * not exist or if it is not valid.
	 * @static
	 * @access private
	 * @param String sName Name of the parameter.
	 * @return Mixed Value of the parameter or null.
	 */
	public static function request($sName, $bWithErrors = false) {

		if (self :: getInstance()->isRegistered($sName, self :: GET) && self :: get($sName) != null) {
			return self :: get($sName, $bWithErrors);
		}

		if (self :: getInstance()->isRegistered($sName, self :: POST) && self :: post($sName) != null) {
			return self :: post($sName, $bWithErrors);
		}

		if (self :: getInstance()->isRegistered($sName, self :: COOKIE) && self :: cookie($sName) != null) {
			return self :: cookie($sName, $bWithErrors);
		}

		if (!self :: getInstance()->isRegistered($sName, self :: GET) && !self :: getInstance()->isRegistered($sName, self :: POST) && !self :: getInstance()->isRegistered($sName, self :: COOKIE)) {
			throw new Exception('Request variable ' . $sName . ' is not registered yet. Please register it first.');
		}

		return null;
	}
}

class ValidationError {

	const NOT_INTEGER = 1;
	const NOT_FLOAT = 2;
	const NOT_SCALAR = 3;
	const TOO_LONG = 4;
	const TOO_SHORT = 5;
	const TOO_BIG = 6;
	const TOO_SMALL = 7;
	const WRONG_PATTERN = 8;

	private $iType;
	private $sMessage;
	private $mTransValue;

	/**
	 * Constructor.
	 * @access private
	 * @param Integer iType Error type (see constants).
	 * @param String sMessage Error message.
	 * @param Mixed mTransValue Original and eventually transformed value.
	 */
	private function __construct($iType, $sMessage, $mTransValue) {

		$this->iType = $iType;
		$this->sMessage = $sMessage;
		$this->mTransValue = $mTransValue;
	}

	/**
	 * Returns the error type.
	 * @access public
	 * @return Integer Error type.
	 */
	public function getError() {
		return $this->iType;
	}

	/**
	 * Returns the error message.
	 * @access public
	 * @return String Error message.
	 */
	public function getMessage() {
		return $this->sMessage;
	}

	/**
	 * Returns the eventually transformed value.
	 * @access public
	 * @return Mixed Transformed value.
	 */
	public function getValue() {
		return $this->mTransValue;
	}

	/**
	 * Returns an object reference of the given type.
	 * @static
	 * @access public
	 * @param Integer iType Error type.
	 * @param Mixed mTransValue Original and eventually transformed value.
	 * @param String sMessage Error message.
	 * @return Object New validation error object.
	 */
	public static function set($iType, $mTransValue, $sMessage = '') {

		$oCurrentInstance = new self($iType, $sMessage, $mTransValue);
		return $oCurrentInstance;
	}
}

?>