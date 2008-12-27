<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Includes CEC hook class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @author     Murat Purc
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release >= 4.8.8
 * 
 * {@internal 
 *   created 2008-08-28, Murat Purc, initial implementation, port from Advanced Mod Rewrite Plugin
 *   modified 2008-09-10, Murat Purc, Bugfix: add further condition handling to prevent overwriting of arguments
 *   modified 2008-11-11, Andreas Lindner,  when overwriting of arguments is prevented and break condition is set
 *   					added anoption to return break condition value directly, otherwise the args would be returned, which is not
 *   					desirable under all circumstances
 *   modified 2008-12-26, Murat Purc, Bugfix: Not each registered chain function will get the same parameter
 *
 * }}
 * 
 */


/**
 * Static CEC Hook class, which provides a public method to process registered chains at 
 * Contenido Extension Chainer (CEC).
 * 
 * Will work with chain functions, which accept one argument (single variable or assoziative/indexed array), 
 * or also multiple arguments.
 * A registered chain function should return the passed single argument or another value, see 
 * config.chains.php.
 * 
 * Usage:
 * <code>
 * // example of executing a cec with multiple parameter
 * $param = array('foo' => $bar, 'foo2' => $bar2);
 * $param = CEC_Hook::execute('Contenido.Content.Somewhere', $param);
 * 
 * // other example of executing a cec with multiple parameter
 * $foo = 1;
 * $bar = 2;
 * $return = CEC_Hook::execute('Contenido.Content.Somewhere', $foo, $bar);
 * 
 * // example of executing a cec with a single parameter
 * $foo = 'bar';
 * $foo = CEC_Hook::execute('Contenido.Content.Somewhere', $foo);
 *
 * // example of executing a cec with a break condition
 * $cat = 123;
 * CEC_Hook::setConditions(CEC_Hook::BREAK_AT_TRUE);
 * $result = CEC_Hook::execute('Contenido.Somewhere.IsValidCat', $cat);
 * if ($result === true) {
 *     // the cec execution has returned true, do something
 * }
 * </code>
 *
 * @author      Murat Purc <murat@purc.de>
 * @package     Contenido Backend classes
 * @subpackage  CEC
 */
class CEC_Hook {

	/**
	 * Value to break the cec execution at a true result
	 * @var  int
	 */
    const BREAK_AT_TRUE  = 1;

	/**
	 * Value to break the cec execution at a false result
	 * @var  int
	 */
    const BREAK_AT_FALSE = 2;

	/**
	 * Value to break the cec execution at a null result
	 * @var  int
	 */
    const BREAK_AT_NULL  = 3;

	/**
	 * Contains temporaly stored break condition.
	 * @var  int
	 */
    static private $_breakCondition = null;

	/**
	 * Flag to return the set break condition directly.
	 * @var  bool
	 */
    static private $_returnBreakConditionDirectly = false;

	/**
	 * Flag to overwrite arguments.
	 * @var  bool
	 */
    static private $_overwriteArguments = true;


    /**
     * Temporaly setting of an execution conditions.
     *
     * This is usefull, if at least on of defined cec functions returns a specific value and the 
     * execution of further functions is no more needed.
     *
     * The defined condition will be reset in execute() method.
     *
     * @param   mixed   $condition  One of CEC_Hook constants, with following control mechanism:
     *                              - CEC_Hook::BREAK_AT_TRUE = Breaks the iteration of cec functions 
     *                                and returns the parameter, if the result of an function is true.
     *
     *                              - CEC_Hook::BREAK_AT_FALSE = Breaks the iteration of cec functions 
     *                                and returns the parameter, if the result of an function is false.
     *
     *                              - CEC_Hook::BREAK_AT_NULL = Breaks the iteration of cec functions 
     *                                and returns the parameter, if the result of an function is null.
     *
     * @param  bool  $overwriteArguments
     * 	  								Flag to prevent overwriting of passed parameter to execute().
     *                                  Normally the parameter will be overwritten by return value of 
     *                                  executed functions, but this is sometimes a not wanted side effect.
     *
     * @param  bool  $returnbreakconditiondirectly
     * 									If a break condition is set and a chain function returns the condition
     * 									set, setting this option forces the execute method to directly return
     * 									that condition instead of the args	         
     *                                     
     *                                    
     *
     * @throws  InvalidArgumentException  If passed type is not one of CEC_Hook constants.
     */
    static public function setConditions($condition, $overwriteArguments=true, $returnbreakconditiondirectly = false) {

        switch ($condition) {
            case CEC_Hook::BREAK_AT_TRUE:
                self::$_breakCondition = CEC_Hook::BREAK_AT_TRUE;
                break;
            case CEC_Hook::BREAK_AT_FALSE:
                self::$_breakCondition = CEC_Hook::BREAK_AT_FALSE;
                break;
            case CEC_Hook::BREAK_AT_NULL:
                self::$_breakCondition = CEC_Hook::BREAK_AT_NULL;
                break;
            default:
                throw new InvalidArgumentException('Condition "' . $condition . '" is not supported!');
                break;
        }
        
        self::$_overwriteArguments = (bool) $overwriteArguments;
        
		self::$_returnBreakConditionDirectly = (bool) $returnbreakconditiondirectly;

    }


    /**
     * Temporaly setting of an break condition.
     *
     * This is usefull, if at least on of defined cec functions returns a specific value and the 
     * execution of further functions is no more needed.
     *
     * The defined condition will be reset in execute() method.
     *
     * @param   mixed   $condition  One of CEC_Hook constants, with following control mechanism:
     *                              - CEC_Hook::BREAK_AT_TRUE = Breaks the iteration of cec functions 
     *                                and returns the parameter, if the result of an function is true.
     *
     *                              - CEC_Hook::BREAK_AT_FALSE = Breaks the iteration of cec functions 
     *                                and returns the parameter, if the result of an function is false.
     *
     *                              - CEC_Hook::BREAK_AT_NULL = Breaks the iteration of cec functions 
     *                                and returns the parameter, if the result of an function is null.
     *
     * @deprecated  Method setConditions() does the job
     *
     * @throws  InvalidArgumentException  If passed type is not one of CEC_Hook constants.
     */
    static public function setBreakCondition($condition) {

        switch ($condition) {
            case CEC_Hook::BREAK_AT_TRUE:
                self::$_breakCondition = CEC_Hook::BREAK_AT_TRUE;
                break;
            case CEC_Hook::BREAK_AT_FALSE:
                self::$_breakCondition = CEC_Hook::BREAK_AT_FALSE;
                break;
            case CEC_Hook::BREAK_AT_NULL:
                self::$_breakCondition = CEC_Hook::BREAK_AT_NULL;
                break;
            default:
                throw new InvalidArgumentException('Condition "' . $condition . '" is not supported!');
                break;
        }

    }

    
    /**
     * Main method to execute registered functions for Contenido Extension Chainer (CEC).
     *
     * Gets the desired CEC iterator and executes each registered chain function. You can pass as much
     * parameter as you want.
     * 
     * TODO: It would be nice to execute registered class::staticMethod and object->method, 
     * see /PEAR/Cache/Function.php for howto.
     *
     * @param   string  $chainName  The chain name to process
     * @param   mixed   $param1     First parameter which will be forwarded to registeded chain functions
     * @param   mixed   $param2     Second parameter which will be forwarded to registeded chain functions
     * @param   mixed   $param3     Third parameter which will be forwarded to registeded chain functions
     *                              NOTE: There are no restriction for number of passed parameter.
     * @return  mixed   Parameter changed/processed by chain functions.
     *                  Note: If no chain function is registered, the first parameter $param after 
     *                        $chainName will be returned
     */
    static public function execute() {

        // get arguments
        $args = func_get_args();

        // get chainname
        $chainName = array_shift($args);

        // process CEC
        $cecIterator = cApiCECRegistry::getInstance()->getIterator($chainName);
        if ($cecIterator->count() > 0) {
            $cecIterator->reset();

            $bFirstIteration = true;
            while ($chainEntry = $cecIterator->next()) {

                // get function to call
                $functionName = $chainEntry->getFunctionName();

                $return = call_user_func_array($functionName, $args);

                // process return value
                if (isset($return)) {
                    if (self::$_overwriteArguments == true) {
                        $args = $return;
                        if ($bFirstIteration == false) {
                            // make an indexed array, otherwhise next call of call_user_func_array
                            // will pass a wrong parameter.
                            $args = array($args);
                        }
                    }

                    // check, if iteration of the loop is to break
                    if (self::$_breakCondition !== null) {
                        if (self::$_breakCondition == self::BREAK_AT_TRUE && $return === true) {
                            if (self::$_returnBreakConditionDirectly) {
								return true;
							}	
							break;
                        } elseif (self::$_breakCondition == self::BREAK_AT_FALSE && $return === false) {
                            if (self::$_returnBreakConditionDirectly) {
	                            return false;
	                        }
                            break;
                        } elseif (self::$_breakCondition == self::BREAK_AT_NULL && $return === null) {
                            if (self::$_returnBreakConditionDirectly) {
                            	return null;
                            }
                            break;
                        }
					}
                }
                $bFirstIteration = false;

            }
        } else {
            // no chain functions are to execute, set the first argument if available
            if (count($args) > 0) {
                $args = $args[0];
            }
        }

        // reset conditions to defaults
        self::$_breakCondition = null;
        self::$_overwriteArguments = true;

        return $args;
    }

}
