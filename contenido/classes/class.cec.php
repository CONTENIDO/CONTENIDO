<?php

/**
 * This file contains the CEC registry class.
 *
 * @package    Core
 * @subpackage CEC
 * @author     Timo A. Hummel
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * CEC registry class.
 * Used to register chains and chain functions to invoke.
 *
 * Following 3 types of CEC functions/callbacks are supported at the moment:
 * - Callbacks, which should only be invoked. They don't return a value and have no
 *   break conditions, @see cApiCecHook::execute()
 * - Callbacks, which should return a value and/or should modify a passed parameter,
 *
 * @see cApiCecHook::executeAndReturn() - Callbacks, which should be processed
 *      until a defined break condition achieves,
 * @see cApiCecHook::executeWhileBreakCondition()
 *
 * @package    Core
 * @subpackage CEC
 */
class cApiCecRegistry
{

    /**
     * @var array List of available chains
     */
    private $_aChains;

    /**
     * @var cApiCecRegistry Self instance
     */
    private static $_instance = NULL;

    /**
     * Constructor to create an instance of this class.
     */
    protected function __construct()
    {
        $this->_aChains = [];
    }

    /**
     * Prevent cloning
     */
    private function __clone()
    {
        // donut
    }

    /**
     * Returns a instance of cApiCecRegistry
     */
    public static function getInstance(): self
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new cApiCecRegistry();
        }

        return self::$_instance;
    }

    /**
     * @deprecated [2014-08-07] This method is deprecated and is not needed any longer
     */
    public function registerChain($chainName)
    {
        cDeprecated('This method is deprecated and is not needed any longer');
    }

    /**
     * @deprecated [2014-08-07] This method is deprecated and is not needed any longer
     */
    public function unregisterChain($chainName)
    {
        cDeprecated('This method is deprecated and is not needed any longer');
    }

    /**
     * @deprecated [2014-08-07] This method is deprecated and is not needed any longer
     */
    public function isChainRegistered($chainName)
    {
        cDeprecated('This method is deprecated and is not needed any longer');
        return true;
    }

    /**
     * @deprecated [2014-08-07] This method is deprecated and is not needed any longer
     */
    public function getRegisteredChainNames()
    {
        cDeprecated('This method is deprecated and is not needed any longer');

        return [];
    }

    /**
     * @deprecated [2014-08-07] This method is deprecated and is not needed any longer
     */
    protected function _addChain($chainName, array $parameters = [])
    {
        cDeprecated('This method is deprecated and is not needed any longer');
        return NULL;
    }

    /**
     * Adds a chain function which is to invoke.
     *
     * @param string $functionName Name of function/callback to invoke.
     *      Feasible values are:
     *      - "ClassName->methodName" to invoke a method of a ClassName instance.
     *         An instance of the clas will be created here.
     *      - "ClassName::methodName" to invoke a static method of ClassName.
     *      - "FunctionName" to invoke a function.
     *         NOTE: Necessary files must be manually included before or by defined autoloader.
     * @return bool True on success, otherwise false
     * @throws cInvalidArgumentException If the given chain is not registered or the given callback is not callable
     */
    public function addChainFunction(string $chainName, string $functionName): bool
    {
        $cfg = cRegistry::getConfig();

        // do not add the chain if the chain system is disabled
        if ($cfg['debug']['disable_chains']) {
            return false;
        }

        if (cString::findFirstPos($functionName, '->') > 0) {
            // chain function is a method of a object instance
            list($class, $method) = explode('->', $functionName);
            if (!class_exists($class)) {
                throw new cInvalidArgumentException(sprintf(
                    "Class %s doesn't exist, can't add %s to chain %s",
                    $class,
                    $functionName,
                    $chainName
                ));
            } elseif (!method_exists($class, $method)) {
                throw new cInvalidArgumentException(sprintf(
                    "Method %s in class %s doesn't exist, can't add %s to chain %s",
                    $method,
                    $class,
                    $functionName,
                    $chainName
                ));
            }
            $call = [
                new $class(),
                $method,
            ];
        } elseif (cString::findFirstPos($functionName, '::') > 0) {
            // chain function is static method of a object
            list($class, $method) = explode('::', $functionName);
            if (!class_exists($class)) {
                throw new cInvalidArgumentException(sprintf(
                    "Class %s doesn't exist, can't add %s to chain %s",
                    $class,
                    $functionName,
                    $chainName
                ));
            } elseif (!method_exists($class, $method)) {
                throw new cInvalidArgumentException(sprintf(
                    "Method %s in class %s doesn't exist, can't add %s to chain %s",
                    $method,
                    $class,
                    $functionName,
                    $chainName
                ));
            }
            $call = [
                $class,
                $method,
            ];
        } else {
            // chain function is a function
            if (!function_exists($functionName)) {
                throw new cInvalidArgumentException(sprintf(
                    "Function %s doesn't exist, can't add to chain %s", $functionName, $chainName
                ));
            }
            $call = $functionName;
        }

        // Last check if the callback is callable
        if (!is_callable($call)) {
            throw new cInvalidArgumentException(sprintf(
                "Function %s isn't callable, can't add to chain %s",
                $functionName,
                $chainName
            ));
        }

        if (!isset($this->_aChains[$chainName])) {
            $this->_aChains[$chainName] = [
                'functions' => [],
                'parameters' => [],
            ];
        }

        $oChainItem = new cApiCecChainItem($chainName, $functionName, $this->_aChains[$chainName]['parameters']);
        $oChainItem->setCallback($call);

        $this->_aChains[$chainName]['functions'][] = $oChainItem;

        return true;
    }

    /**
     * Checks if a chain function exists.
     *
     * @param string $functionName Name of function to check
     */
    public function chainFunctionExists(string $chainName, string $functionName): bool
    {
        $this->_resetIterator($chainName);
        $chainFunctions = $this->_aChains[$chainName]['functions'];
        foreach ($chainFunctions as $item) {
            if ($item->getFunctionName() == $functionName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Removes a chain function.
     *
     * @param string $functionName Name of function to remove from chain.
     */
    public function removeChainFunction(string $chainName, string $functionName)
    {
        $this->_resetIterator($chainName);

        foreach ($this->_aChains[$chainName]['functions'] as $pos => $item) {
            if ($item->getFunctionName() == $functionName) {
                unset($this->_aChains[$chainName]['functions'][$pos]);

                return;
            }
        }
    }

    /**
     * Returns the iterator for a desired chain.
     */
    public function getIterator(string $chainName): cIterator
    {
        return new cIterator($this->_aChains[$chainName]['functions'] ?? []);
    }

    /**
     * Resets the chain iterator.
     */
    protected function _resetIterator(string $chainName)
    {
        $this->getIterator($chainName)->reset();
    }

    /**
     * Flushes added chains
     */
    public function flushAddedChains()
    {
        $this->_aChains = [];
    }
}

/**
 * CEC chain item class.
 *
 * @package    Core
 * @subpackage CEC
 */
class cApiCecChainItem
{

    /**
     * @var string Chain name
     */
    protected $chainName;

    /**
     * @var string Name of the function to invoke
     */
    protected $functionName = '';

    /**
     * @var string|array Callback name. Contains either the function name to invoke,
     *      or an indexed array (class/object and method) and it's method to execute.
     */
    protected $_mCallback;

    /**
     * @var array Parameter to pass to the function
     * @deprecated [2014-08-07] This property is deprecated and is not needed any longer
     */
    protected $parameters;

    /**
     * @var ?array Temporary arguments holder
     */
    protected $temporaryArguments;

    /**
     * Constructor to create an instance of this class.
     *
     * Sets the CEC chain item properties.
     *
     * @param array $parameters @deprecated [2014-08-07] This parameter is deprecated and is not needed any longer
     * @throws cInvalidArgumentException
     */
    public function __construct(string $chainName, string $functionName, $parameters)
    {
        $this->setChainName($chainName);
        $this->setFunctionName($functionName);
        $this->setCallback($this->getFunctionName());
    }

    /**
     * Sets the chain name
     */
    public function setChainName(string $chainName)
    {
        $this->chainName = $chainName;
    }

    /**
     * Returns the chain name
     */
    public function getChainName(): string
    {
        return $this->chainName;
    }

    /**
     * Sets the function name
     */
    public function setFunctionName(string $functionName)
    {
        $this->functionName = $functionName;
    }

    /**
     * Returns the function name
     */
    public function getFunctionName(): string
    {
        return $this->functionName;
    }

    /**
     * @deprecated [2014-08-07] This method is deprecated and is not needed any longer
     */
    public function setParameters(array $parameters)
    {
        cDeprecated('This method is deprecated and is not needed any longer');
    }

    /**
     * @deprecated [2014-08-07] This method is deprecated and is not needed any longer
     */
    public function getParameters()
    {
        cDeprecated('This method is deprecated and is not needed any longer');
        return [];
    }

    /**
     * Sets the callback
     *
     * @param string|array $callback
     * @throws cInvalidArgumentException if the given callback is not a string or an array
     */
    public function setCallback($callback)
    {
        if (is_string($callback) || is_array($callback)) {
            $this->_mCallback = $callback;
        } else {
            throw new cInvalidArgumentException('Callback has to be a string or an array.');
        }
    }

    /**
     * Returns the callback
     *
     * @return string|array
     */
    public function getCallback()
    {
        return $this->_mCallback;
    }

    /**
     * Another way to set the arguments before invoking execute() method.
     */
    public function setTemporaryArguments(array $arguments = [])
    {
        $this->temporaryArguments = $arguments;
    }

    /**
     * Will be invoked by execute() method.
     * If temporary arguments where set before, it returns them and resets the property.
     */
    public function getTemporaryArguments(): array
    {
        $arguments = is_array($this->temporaryArguments) ? $this->temporaryArguments : [];
        $this->temporaryArguments = NULL;

        return $arguments;
    }

    /**
     * Invokes the CEC function/callback.
     *
     * Arguments can be passed to the callback in two ways:
     * 1. Via variadic parameters: $item->execute($arg1, $arg2, ...)
     * 2. Via setTemporaryArguments(): $item->setTemporaryArguments([$arg1, $arg2, ...])
     *
     * If temporary arguments were set, they take precedence over passed arguments.
     *
     * @param mixed ...$arguments Optional. Additional arguments passed to the chain function
     * @return mixed If available, the result of the CEC function/callback
     */
    public function execute(...$arguments)
    {
        // get temporary arguments, if the where set before
        if ($temporaryArgs = $this->getTemporaryArguments()) {
            $arguments = $temporaryArgs;
        }

        return call_user_func_array($this->getCallback(), $arguments);
    }
}
