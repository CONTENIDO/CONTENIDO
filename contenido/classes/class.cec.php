<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Contenido Extension Chainer (CEC).
 * See "docs/techref/plugins/Contenido Extension Chainer.pdf" for more details about CEC.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package     Contenido Backend classes
 * @subpackage  CEC
 * @version     1.2.0
 * @author      Timo A. Hummel
 * @author      Murat Purc <murat@purc.de>
 * @copyright   four for business AG <www.4fb.de>
 * @license     http://www.contenido.org/license/LIZENZ.txt
 * @link        http://www.4fb.de
 * @link        http://www.contenido.org
 * @since       file available since contenido release <= 4.6
 *
 * {@internal
 *   created unknown
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *   modified 2008-08-28, Murat Purc, add singleton pattern feature
 *   modified 2009-12-30, Murat Purc, redesign of cApiCECRegistry and pApiCECChainItem bearing in mind of
 *                        downwards compatibility and documenting the code, see [#CON-291], also regards to [#CON-256]
 *
 *   $Id$:
 * }}
 *
 */

if(!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/**
 * CEC registry class. Used to register chains and chain functions to invoke.
 *
 * Following 3 types of CEC functions/callbacks are supported at the moment:
 * - Callbacks, which should only be invoked. They don't return a value and have no
 *   break conditions, @see CEC_Hook::execute()
 * - Callbacks, which should return a value and/or should modify a passed parameter,
 *   @see CEC_Hook::executeAndReturn()
 * - Callbacks, which should be processed untill a defined break condition achieves,
 *   @see CEC_Hook::executeWhileBreakCondition()
 *
 * @author      Timo A. Hummel
 * @author      Murat Purc <murat@purc.de>
 * @package     Contenido Backend classes
 * @subpackage  CEC
 */
class cApiCECRegistry
{
    /**
     * List of available chains
     * @var  array
     */
    private $_aChains;

    /**
     * Self instance
     * @var  cApiCECRegistry
     */
    private static $_instance = null;


    /**
     * Constructor
     *
     * @return  void
     */
    protected function __construct()
    {
        $this->_aChains = array();
    }


    /**
     * Prevent cloning
     *
     * @return  void
     */
    private function __clone()
    {
        // donut
    }


    /**
     * Returns a instance of cApiCECRegistry
     *
     * @return  cApiCECRegistry
     */
    public static function getInstance()
    {
        if (self::$_instance == null) {
            self::$_instance = new cApiCECRegistry();
        }
        return self::$_instance;
    }


    /**
     * Registers a chain (adds the chain to the internal chain holder)
     *
     * @param   string  $sChainName
     * @param   mixed   First chain parameter
     * @param   mixed   Second chain parameter
     * @param   mixed   Third chain parameter...
     *                  NOTE: The number of parameter is not restricted, you can pass parameter as
     *                        much as you want.
     * @return  void
     */
    public function registerChain($sChainName)
    {
        $aParam = array();
        $iNumArgs = func_num_args();

        for ($iCount = 0; $iCount < $iNumArgs; $iCount++) {
            $aParam[$iCount] = func_get_arg($iCount);
        }

        $this->_addChain($sChainName, $aParam);
    }


    /**
     * Unregisters a chain
     *
     * @param   string  $sChainName
     * @return  void
     */
    public function unregisterChain($sChainName)
    {
        // Check if the chain exists
        if (!$this->isChainRegistered($sChainName)) {
            cWarning(__FILE__, __LINE__, "Chain " . $sChainName . " doesn't exist.");
            return false;
        }

        $functions = array();
        $this->_resetIterator($sChainName);
        $chainFunctions = $this->_aChains[$sChainName]['functions'];
        foreach ($chainFunctions as $pos => $item) {
            $functions[] = $item->getFunctionName();
        }

        foreach ($functions as $p => $func) {
            $this->removeChainFunction($sChainName, $func);
        }

        unset($this->_aChains[$sChainName]);
    }


    /**
     * Checks if a chain is registered or not.
     *
     * @param   string  $sChainName
     * @return  bool
     */
    public function isChainRegistered($sChainName)
    {
        return (isset($this->_aChains[$sChainName]));
    }


    /**
     * Returns list of registered chain names
     *
     * @return  array
     */
    public function getRegisteredChainNames()
    {
        return array_keys($this->_aChains);
    }


    /**
     * Adds the chain to the internal chain holder
     *
     * @param   string  $sChainName   Chain name
     * @param   array   $aParameters  Chain parameter
     * @return  void
     */
    protected function _addChain($sChainName, array $aParameters = array())
    {
        $this->_aChains[$sChainName]['parameters'] = $aParameters;
        $this->_aChains[$sChainName]['functions']  = array();
    }


    /**
     * Adds a chain function which is to invoke.
     *
     * @param   string  $sChainName     Chain name
     * @param   string  $sFunctionName  Name of function/callback to invoke. Feasible values are:
     *                                  - "ClassName->methodName" to invoke a method of a ClassName instance.
     *                                    A instance of the clas will be created here.
     *                                  - "ClassName::methodName" to invoke a static method of ClassName.
     *                                  - "FunctionName" to invoke a function.
     *                                  NOTE: Necessary files must be manually included before or by defined autoloader.
     * @return  bool    True on success, otherwhise false
     */
    public function addChainFunction($sChainName, $sFunctionName)
    {
        // Check if the chain exists
        if (!$this->isChainRegistered($sChainName)) {
            cWarning(__FILE__, __LINE__, "Chain " . $sChainName . " doesn't exist.");
            return false;
        }

        if (strpos($sFunctionName, '->') > 0) {
            // chain function is a method of a object instance
            list($class, $method) = explode('->', $sFunctionName);
            if (!class_exists($class)) {
                cWarning(__FILE__, __LINE__, "Class " . $class . " doesn't exist, can't add " . $sFunctionName . " to chain " . $sChainName);
                return false;
            } elseif (!method_exists($class, $method)) {
                cWarning(__FILE__, __LINE__, "Method " . $method . " in class " . $class . " doesn't exist, can't add " . $sFunctionName . " to chain " . $sChainName);
                return false;
            }
            $call = array(new $class(), $method);
        } elseif (strpos($sFunctionName, '::') > 0) {
            // chain function is static method of a object
            list($class, $method) = explode('::', $sFunctionName);
            if (!class_exists($class)) {
                cWarning(__FILE__, __LINE__, "Class " . $class . " doesn't exist, can't add " . $sFunctionName . " to chain " . $sChainName);
                return false;
            } elseif (!method_exists($class, $method)) {
                cWarning(__FILE__, __LINE__, "Method " . $method . " in class " . $class . " doesn't exist, can't add " . $sFunctionName . " to chain " . $sChainName);
                return false;
            }
            $call = array($class, $method);
        } else {
            // chain function is a function
            if (!function_exists($sFunctionName)) {
                cWarning(__FILE__, __LINE__, "Function " . $sFunctionName . " doesn't exist, can't add to chain " . $sChainName);
                return false;
            }
            $call = $sFunctionName;
        }

        // Last check if the callback is callable
        if (!is_callable($call)) {
            cWarning(__FILE__, __LINE__, "Function " . $sFunctionName . " isn't callable, can't add to chain " . $sChainName);
            return false;
        }

        $oChainItem = new pApiCECChainItem($sChainName, $sFunctionName, $this->_aChains[$sChainName]['parameters']);
        $oChainItem->setCallback($call);
        array_push($this->_aChains[$sChainName]['functions'], $oChainItem);

        return true;
    }


    /**
     * Checks if a chain function exists.
     *
     * @param   string  $sChainName     Chain name
     * @param   string  $sFunctionName  Name of function to check
     * @return  bool
     */
    public function chainFunctionExists($sChainName, $sFunctionName)
    {
        if (!$this->isChainRegistered($sChainName)) {
            return false;
        }

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
     * @param   string  $sChainName     Chain name
     * @param   string  $sFunctionName  Name of function to remove from chain.
     */
    public function removeChainFunction($sChainName, $sFunctionName)
    {
        if (!$this->isChainRegistered($sChainName)) {
            return;
        }

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
     * @TODO:  cIterator should be replaced by ArrayIterator (@see http://www.php.net/spl)
     *         but ArrayIterator uses rewind() instead of reset()...
     *
     * @param   string  $sChainName  Chain name
     * @return  cIterator
     */
    public function getIterator($sChainName)
    {
        cInclude('classes', 'class.iterator.php');
        return new cIterator($this->_aChains[$sChainName]['functions']);
    }


    /**
     * Resets the chain iterator.
     *
     * @param   string  $sChainName
     * @return  void
     */
    protected function _resetIterator($sChainName)
    {
        $iterator = $this->getIterator($sChainName);
        $iterator->reset();
    }

}


/**
 * CEC chain item class.
 *
 * @author      Timo A. Hummel
 * @author      Murat Purc <murat@purc.de>
 * @package     Contenido Backend classes
 * @subpackage  CEC
 */
class pApiCECChainItem
{
    /**
     * Chain name
     * @var  string
     */
    protected $_sChainName;

    /**
     * Name of function to invoke
     * @var  string
     */
    protected $_sFunctionName;

    /**
     * Callback name. Contains either the function name to invoke, or a array containing a class/object
     * and it's method to execute.
     * @var  array|string
     */
    protected $_mCallback;

    /**
     * Parameter to pass to the function
     * @var  array
     */
    protected $_aParameters;


    /**
     * Constructor, sets the CEC chain item properties.
     *
     * @param   string  $sChainName
     * @param   string  $sFunctionName
     * @param   array   $aParameters
     * @return  void
     */
    public function __construct($sChainName, $sFunctionName, $aParameters)
    {
        $this->setChainName($sChainName);
        $this->setFunctionName($sFunctionName);
        $this->setParameters($aParameters);
        $this->setCallback($this->getFunctionName());
    }


    /**
     * Sets the chain name
     *
     * @param   string  $sChainName
     * @return  void
     */
    public function setChainName($sChainName)
    {
        $this->_sChainName = $sChainName;
    }


    /**
     * Returns the chain name
     *
     * @return  string
     */
    public function getChainName()
    {
        return $this->_sChainName;
    }


    /**
     * Sets the function name
     *
     * @param   string  $sFunctionName
     * @return  void
     */
    public function setFunctionName($sFunctionName)
    {
        $this->_sFunctionName = $sFunctionName;
    }


    /**
     * Returns the function name
     *
     * @return  string
     */
    public function getFunctionName()
    {
        return $this->_sFunctionName;
    }


    /**
     * Sets the callback parameters
     *
     * @param   array  $aParameters
     * @return  void
     */
    public function setParameters(array $aParameters)
    {
        $this->_aParameters = $aParameters;
    }


    /**
     * Returns the function name
     *
     * @return  array
     */
    public function getParameters()
    {
        return $this->_aParameters;
    }


    /**
     * Sets the callback
     *
     * @return  string|array
     */
    public function setCallback($callback)
    {
        if (is_string($callback) || is_array($callback)) {
            $this->_mCallback = $callback;
        } else {
            throw new Exception("Passed argument isn't as expected");
        }
    }


    /**
     * Returns the callback
     *
     * @return  string|array
     */
    public function getCallback()
    {
        return $this->_mCallback;
    }


    /**
     * Invokes the CEC function/callback.
     *
     * @return  mixed  If available, the result of the CEC function/callback
     */
    public function execute()
    {
        return call_user_func_array($this->getCallback(), $this->getParameters());
    }

}
