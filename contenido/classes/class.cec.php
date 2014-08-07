<?php
/**
 * This file contains the CEC registry class.
 *
 * @package Core
 * @subpackage CEC
 * @version SVN Revision $Rev:$
 *
 * @author Timo A. Hummel
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * CEC registry class.
 * Used to register chains and chain functions to invoke.
 *
 * Following 3 types of CEC functions/callbacks are supported at the moment:
 * - Callbacks, which should only be invoked. They don't return a value and have
 * no
 * break conditions, @see cApiCecHook::execute()
 * - Callbacks, which should return a value and/or should modify a passed
 * parameter,
 *
 * @see cApiCecHook::executeAndReturn() - Callbacks, which should be processed
 *      untill a defined break condition achieves,
 * @see cApiCecHook::executeWhileBreakCondition()
 *
 * @package Core
 * @subpackage CEC
 */
class cApiCecRegistry {

    /**
     * List of available chains
     *
     * @var array
     */
    private $_aChains;

    /**
     * Self instance
     *
     * @var cApiCecRegistry
     */
    private static $_instance = NULL;

    /**
     * Constructor
     */
    protected function __construct() {
        $this->_aChains = array();
    }

    /**
     * Prevent cloning
     */
    private function __clone() {
        // donut
    }

    /**
     * Returns a instance of cApiCecRegistry
     *
     * @return cApiCecRegistry
     */
    public static function getInstance() {
        if (self::$_instance == NULL) {
            self::$_instance = new cApiCecRegistry();
        }

        return self::$_instance;
    }

    /**
     * Registers a chain (adds the chain to the internal chain holder)
     * NOTE: The number of parameter is not restricted.
     * You can pass
     * as much parameter as you want.
     *
     * @param string $sChainName
	 * @deprecated 2014-08-07 - This method is deprecated and is not needed any longer
     */
    public function registerChain($sChainName) {
		cDeprecated('This method is deprecated and is not needed any longer');
    }

    /**
     * Unregisters a chain
     *
     * @param string $sChainName
     *
     * @throws cInvalidArgumentException if the given chain does not exist
	 * @deprecated 2014-08-07 - This method is deprecated and is not needed any longer
     */
    public function unregisterChain($sChainName) {
        cDeprecated('This method is deprecated and is not needed any longer');
    }

    /**
     * Checks if a chain is registered or not.
     *
     * @param string $sChainName
     *
     * @return bool
	 * @deprecated 2014-08-07 - This method is deprecated and is not needed any longer
     */
    public function isChainRegistered($sChainName) {
		cDeprecated('This method is deprecated and is not needed any longer');
        return true;
    }

    /**
     * Returns list of registered chain names
     *
     * @return array
	 * @deprecated 2014-08-07 - This method is deprecated and is not needed any longer
     */
    public function getRegisteredChainNames() {
		cDeprecated('This method is deprecated and is not needed any longer');
        return array();
    }

    /**
     * Adds the chain to the internal chain holder
     *
     * @param string $sChainName Chain name
     * @param array $aParameters Chain parameter
	 * @deprecated 2014-08-07 - This method is deprecated and is not needed any longer
     */
    protected function _addChain($sChainName, array $aParameters = array()) {
		cDeprecated('This method is deprecated and is not needed any longer');
        return null;
    }

    /**
     * Adds a chain function which is to invoke.
     *
     * @param string $sChainName Chain name
     * @param string $sFunctionName Name of function/callback to invoke.
     *        Feasible values are:
     *        - "ClassName->methodName" to invoke a method of a ClassName
     *        instance.
     *        A instance of the clas will be created here.
     *        - "ClassName::methodName" to invoke a static method of ClassName.
     *        - "FunctionName" to invoke a function.
     *        NOTE: Necessary files must be manually included before or by
     *        defined autoloader.
     *
     * @throws cInvalidArgumentException if the given chain is not registered or
     *         the given callback is not callable
     * @return bool True on success, otherwhise false
     */
    public function addChainFunction($sChainName, $sFunctionName) {
        $cfg = cRegistry::getConfig();
        // do not add the chain if the chain system is disabled
        if ($cfg['debug']['disable_chains']) {
            return;
        }

        if (strpos($sFunctionName, '->') > 0) {
            // chain function is a method of a object instance
            list($class, $method) = explode('->', $sFunctionName);
            if (!class_exists($class)) {
                throw new cInvalidArgumentException('Class ' . $class . ' doesn\'t exist, can\'t add ' . $sFunctionName . ' to chain ' . $sChainName);
            } elseif (!method_exists($class, $method)) {
                throw new cInvalidArgumentException('Method ' . $method . ' in class ' . $class . ' doesn\'t exist, can\'t add ' . $sFunctionName . ' to chain ' . $sChainName);
            }
            $call = array(
                new $class(),
                $method
            );
        } elseif (strpos($sFunctionName, '::') > 0) {
            // chain function is static method of a object
            list($class, $method) = explode('::', $sFunctionName);
            if (!class_exists($class)) {
                throw new cInvalidArgumentException('Class ' . $class . ' doesn\'t exist, can\'t add ' . $sFunctionName . ' to chain ' . $sChainName);
            } elseif (!method_exists($class, $method)) {
                throw new cInvalidArgumentException('Method ' . $method . ' in class ' . $class . ' doesn\'t exist, can\'t add ' . $sFunctionName . ' to chain ' . $sChainName);
            }
            $call = array(
                $class,
                $method
            );
        } else {
            // chain function is a function
            if (!function_exists($sFunctionName)) {
                throw new cInvalidArgumentException('Function ' . $sFunctionName . ' doesn\'t exist, can\'t add to chain ' . $sChainName);
            }
            $call = $sFunctionName;
        }

        // Last check if the callback is callable
        if (!is_callable($call)) {
            throw new cInvalidArgumentException('Function ' . $sFunctionName . ' isn\'t callable, can\'t add to chain ' . $sChainName);
        }

        $oChainItem = new cApiCecChainItem($sChainName, $sFunctionName, $this->_aChains[$sChainName]['parameters']);
        $oChainItem->setCallback($call);

		if (!is_array($this->_aChains[$sChainName])) {
			$this->_aChains[$sChainName] = array();
			$this->_aChains[$sChainName]['functions'] = array();
		}

		if (!is_array($this->_aChains[$sChainName]['functions'])) {
			$this->_aChains[$sChainName]['functions'] = array();
		}

        $this->_aChains[$sChainName]['functions'][] = $oChainItem;

        return true;
    }

    /**
     * Checks if a chain function exists.
     *
     * @param string $sChainName Chain name
     * @param string $sFunctionName Name of function to check
     *
     * @return bool
     */
    public function chainFunctionExists($sChainName, $sFunctionName) {
        $this->_resetIterator($sChainName);
        $chainFunctions = $this->_aChains[$sChainName]['functions'];
        foreach ($chainFunctions as $pos => $item) {
            if ($item->getFunctionName() == $sFunctionName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Removes a chain function.
     *
     * @param string $sChainName Chain name
     * @param string $sFunctionName Name of function to remove from chain.
     */
    public function removeChainFunction($sChainName, $sFunctionName) {
        $this->_resetIterator($sChainName);
        $chainFunctions = $this->_aChains[$sChainName]['functions'];
        foreach ($this->_aChains[$sChainName]['functions'] as $pos => $item) {
            if ($item->getFunctionName() == $sFunctionName) {
                unset($this->_aChains[$sChainName]['functions'][$pos]);

                return;
            }
        }
    }

    /**
     * Returns the iterator for a desired chain.
     *
     * @todo : cIterator should be replaced by ArrayIterator (@see
     *       http://www.php.net/spl)
     *       but ArrayIterator uses rewind() instead of reset()...
     *
     * @param string $sChainName Chain name
     *
     * @return cIterator
     */
    public function getIterator($sChainName) {
        return new cIterator($this->_aChains[$sChainName]['functions']);
    }

    /**
     * Resets the chain iterator.
     *
     * @param string $sChainName
     */
    protected function _resetIterator($sChainName) {
        $iterator = $this->getIterator($sChainName);
        $iterator->reset();
    }
	
	/**
	 * Flushs added chains
	 *
	 */
	public function flushAddedChains() {
		$this->_aChains = array();
	}
}

/**
 * CEC chain item class.
 *
 * @package Core
 * @subpackage CEC
 */
class cApiCecChainItem {

    /**
     * Chain name
     *
     * @var string
     */
    protected $_sChainName;

    /**
     * Name of function to invoke
     *
     * @var string
     */
    protected $_sFunctionName;

    /**
     * Callback name.
     * Contains either the function name to invoke, or a indexed array
     * (class/object and method)
     * and it's method to execute.
     *
     * @var array string
     */
    protected $_mCallback;

    /**
     * Parameter to pass to the function
     *
     * @var array
     */
    protected $_aParameters;

    /**
     * Temporary arguments holder
     *
     * @var array NULL
     */
    protected $_mTemporaryArguments;

    /**
     * Constructor, sets the CEC chain item properties.
     *
     * @param string $sChainName
     * @param string $sFunctionName
     * @param array $aParameters
     */
    public function __construct($sChainName, $sFunctionName, $aParameters) {
        $this->setChainName($sChainName);
        $this->setFunctionName($sFunctionName);
        $this->setCallback($this->getFunctionName());
    }

    /**
     * Sets the chain name
     *
     * @param string $sChainName
     */
    public function setChainName($sChainName) {
        $this->_sChainName = $sChainName;
    }

    /**
     * Returns the chain name
     *
     * @return string
     */
    public function getChainName() {
        return $this->_sChainName;
    }

    /**
     * Sets the function name
     *
     * @param string $sFunctionName
     */
    public function setFunctionName($sFunctionName) {
        $this->_sFunctionName = $sFunctionName;
    }

    /**
     * Returns the function name
     *
     * @return string
     */
    public function getFunctionName() {
        return $this->_sFunctionName;
    }

    /**
     * Sets the callback parameters
     *
     * @param array $aParameters
	 * @deprecated 2014-08-07 - This method is deprecated and is not needed any longer
     */
    public function setParameters(array $aParameters) {
		cDeprecated('This method is deprecated and is not needed any longer');
    }

    /**
     * Returns the function name
     *
     * @return array
	 * @deprecated 2014-08-07 - This method is deprecated and is not needed any longer
     */
    public function getParameters() {
		cDeprecated('This method is deprecated and is not needed any longer');
        return array();
    }

    /**
     * Sets the callback
     *
     * @param string|array $callback
     * @throws cInvalidArgumentException if the given callback is not a string
     *         or an array
     */
    public function setCallback($callback) {
        if (is_string($callback) || is_array($callback)) {
            $this->_mCallback = $callback;
        } else {
            throw new cInvalidArgumentException("Callback has to be a string or an array.");
        }
    }

    /**
     * Returns the callback
     *
     * @return string array
     */
    public function getCallback() {
        return $this->_mCallback;
    }

    /**
     * Another way to set the arguments before invoking execute() method.
     *
     * @param array $args
     */
    public function setTemporaryArguments(array $args = array()) {
        $this->_mTemporaryArguments = $args;
    }

    /**
     * Will be invoked by execute() method.
     * If temporary arguments where set before, it returns them and resets the
     * property.
     *
     * @return array
     */
    public function getTemporaryArguments() {
        $args = $this->_mTemporaryArguments;
        $this->_mTemporaryArguments = NULL;

        return $args;
    }

    /**
     * Invokes the CEC function/callback.
     *
     * @return mixed If available, the result of the CEC function/callback
     */
    public function execute() {
        // get temporary arguments, if the where set before
        if (!$args = $this->getTemporaryArguments()) {
            // no temporary arguments available, get them by func_get_args()
            $args = func_get_args();
        }

        return call_user_func_array($this->getCallback(), $args);
    }
}
