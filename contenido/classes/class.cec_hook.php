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
 * @package     Contenido Backend classes
 * @subpackage  CEC
 * @version     0.5
 * @author      Murat Purc <murat@purc.de>
 * @copyright   four for business AG <www.4fb.de>
 * @license     http://www.contenido.org/license/LIZENZ.txt
 * @link        http://www.4fb.de
 * @link        http://www.contenido.org
 * @since       file available since contenido release >= 4.8.8
 *
 * {@internal
 *   created  2008-08-28, Murat Purc, initial implementation, port from Advanced Mod Rewrite Plugin
 *   modified 2008-09-10, Murat Purc, bugfix: add further condition handling to prevent overwriting of arguments
 *   modified 2008-11-11, Andreas Lindner,  when overwriting of arguments is prevented and break condition is set
 *                        added anoption to return break condition value directly, otherwise the args would be returned, which is not
 *                        desirable under all circumstances
 *   modified 2008-12-26, Murat Purc, bugfix: Not each registered chain function will get the same parameter
 *   modified 2009-12-26, Murat Purc, bugfix/redesign of CEC_Hook, see [#CON-256]
 *
 * }}
 *
 */


/**
 * Static CEC Hook class, provides some public methods to process registered chains
 * at CEC (Contenido Extension Chainer).
 *
 * Usage:
 * <code>
 * // example of executing a cec without a parameter and return value
 * CEC_Hook::execute('Contenido.Content.Somewhere');
 *
 * // example of executing a cec with a parameter but without a return value
 * $param = 'some value';
 * CEC_Hook::execute('Contenido.Content.Somewhere', $param);
 *
 * // example of executing a cec with multiple parameter but without a return value
 * $param = array('foo' => $bar, 'foo2' => $bar2);
 * $param = CEC_Hook::execute('Contenido.Content.Somewhere', $param);
 *
 *
 * // example of executing a cec without a parameter but a return value (with predefined
 * // default return value)
 * CEC_Hook::setDefaultReturnValue('this is the default title');
 * $title = CEC_Hook::executeAndReturn('Contenido.Content.CreateTitletag');
 *
 * // example of executing a cec with a parameter and a return value
 * // (usually the modified version of passed parameter)
 * $baseHref    = $cfgClient[$client]['path']['htmlpath'];
 * $newBaseHref = CEC_Hook::executeAndReturn('Contenido.Frontend.BaseHrefGeneration', $baseHref);
 *
 *
 * // example of executing a cec with a break condition and default return value
 * CEC_Hook::setBreakCondition(false, true); // break condition = "false", default return value = "true"
 * $allow = CEC_Hook::executeWhileBreakCondition('Contenido.Frontend.AllowEdit', $lang, $idcat, $idart, $auth->auth['uid']);
 * if ($allow == false) {
 *     die("You're not coming in!");
 * }
 *
 * // another example of executing a cec with a break condition and default return value
 * CEC_Hook::setBreakCondition(true, false); // break condition = "true", default return value = "false"
 * $allow = CEC_Hook::executeWhileBreakCondition('Contenido.Frontend.CategoryAccess', $lang, $idcat, $auth->auth['uid']);
 * if ($allow == false) {
 *     die("I said, you're not coming in!");
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
     * @deprecated  see CEC_Hook::setConditions()
     * @var  int
     */
    const BREAK_AT_TRUE  = 'true';

    /**
     * Value to break the cec execution at a false result
     * @deprecated  see CEC_Hook::setConditions()
     * @var  int
     */
    const BREAK_AT_FALSE = 'false';

    /**
     * Value to break the cec execution at a null result
     * @deprecated  see CEC_Hook::setConditions()
     * @var  int
     */
    const BREAK_AT_NULL  = 'null';

    /**
     * Flag to return the set break condition directly.
     * @deprecated No more needed
     * @var  bool
     */
    static private $_returnBreakConditionDirectly = false;

    /**
     * Flag to overwrite arguments.
     * @deprecated No more needed
     * @var  bool
     */
    static private $_overwriteArguments = true;

    /**
     * Temporaly stored break condition.
     * @var  int
     */
    static private $_breakCondition = null;

    /**
     * Temporaly stored default return value of CEC functions
     * @var  mixed
     */
    static private $_defaultReturnValue = null;

    /**
     * Temporaly stored position of argument to return. It's used by CEC_Hook::executeAndReturn()
     * to store/extract the return value into/from arguments list.
     * @var  int
     */
    static private $_returnArgumentPos = 1;


    /**
     * Temporaly setting of an execution conditions.
     *
     * @deprecated  Function is no more needed, still exists due to downwards compatibility!
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
     *                                  Flag to prevent overwriting of passed parameter to execute().
     *                                  Normally the parameter will be overwritten by return value of
     *                                  executed functions, but this is sometimes a not wanted side effect.
     *
     * @param  bool  $returnbreakconditiondirectly
     *                                  If a break condition is set and a chain function returns the condition
     *                                  set, setting this option forces the execute method to directly return
     *                                  that condition instead of the args
     *
     * @param  mixed  $defaultReturnValue
     *
     * @throws  InvalidArgumentException  If passed type is not one of CEC_Hook constants.
     */
    public static function setConditions($condition, $overwriteArguments=true, $returnbreakconditiondirectly=false, $defaultReturnValue=null)
    {
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

        self::$_defaultReturnValue = $defaultReturnValue;
    }


    /**
     * Temporaly setting of break condition and optional the default return value.
     *
     * @param   mixed  $condition
     * @param   mixed  $defaultReturnValue
     * @return  void
     */
    public static function setBreakCondition($condition, $defaultReturnValue=null)
    {
        self::$_breakCondition = $condition;
        self::setDefaultReturnValue($defaultReturnValue);
    }


    /**
     * Temporaly setting of default return value.
     *
     * @param   mixed  $defaultReturnValue
     * @return  void
     */
    public static function setDefaultReturnValue($defaultReturnValue)
    {
        self::$_defaultReturnValue = $defaultReturnValue;
    }


    /**
     * Temporaly setting of position in argument to return.
     *
     * @param   int  $pos  Position, feasible value greater 0
     * @return  void
     */
    public static function setReturnArgumentPos($pos)
    {
        if ((int) $pos < 1) {
            throw new Exception('Invalid return position');
        }
        self::$_returnArgumentPos = (int) $pos;
    }


    /**
     * Method to execute registered functions for Contenido Extension Chainer (CEC).
     *
     * Gets the desired CEC iterator and executes each registered chain function by passing the achieved
     * arguments to it. There is
     *
     * @param   string  $chainName  The chain name to process
     * @param   mixed   $param1     First parameter which will be forwarded to registered chain functions
     * @param   mixed   $param2     Second parameter which will be forwarded to registered chain functions
     * @param   mixed   $param3     Third parameter which will be forwarded to registered chain functions
     *                              NOTE: There is no restriction for number of passed parameter.
     * @return  void
     */
    public static function execute()
    {
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
                // invoke CEC function
                $chainEntry->setParameters($args);
                $chainEntry->execute();
            }
        }

        // reset properties to defaults
        self::_reset();
    }


    /**
     * Method to execute registered functions for Contenido Extension Chainer (CEC).
     *
     * Gets the desired CEC iterator and executes each registered chain function. You can pass as much
     * parameter as you want.
     *
     * @param   string  $chainName  The chain name to process
     * @param   mixed   $param1     First parameter which will be forwarded to registered chain functions
     * @param   mixed   $param2     Second parameter which will be forwarded to registered chain functions
     * @param   mixed   $param3     Third parameter which will be forwarded to registered chain functions
     *                              NOTE: There is no restriction for number of passed parameter.
     * @return  mixed   Parameter changed/processed by chain functions.
     *                  Note: If no chain function is registered, the first parameter $param after
     *                        $chainName will be returned
     */
    public static function executeAndReturn()
    {
        // get arguments
        $args = func_get_args();

        // get chainname
        $chainName = array_shift($args);

        // position of return value in arguments list
        $pos = self::$_returnArgumentPos - 1;

        // default return value
        $return = self::$_defaultReturnValue;

        // process CEC
        $cecIterator = cApiCECRegistry::getInstance()->getIterator($chainName);
        if ($cecIterator->count() > 0) {
            $cecIterator->reset();

            while ($chainEntry = $cecIterator->next()) {
                // invoke CEC function
                $chainEntry->setParameters($args);
                $return = $chainEntry->execute();
                if (isset($args[$pos])) {
                    $args[$pos] = $return;
                }
            }
        }

        if (isset($args[$pos])) {
            $return = $args[$pos];
        }

        // reset properties to defaults
        self::_reset();

        return $return;
    }



    /**
     * CEC function to process chains untill a break condition occurs.
     *
     * Gets the desired CEC iterator and executes each registered chain function as long as defined break
     * condition doesn't occur.
     *
     * @param   string  $chainName  The chain name to process
     * @param   mixed   $param1     First parameter which will be forwarded to registered chain functions
     * @param   mixed   $param2     Second parameter which will be forwarded to registered chain functions
     * @param   mixed   $param3     Third parameter which will be forwarded to registered chain functions
     *                              NOTE: There is no restriction for number of passed parameter.
     * @return  mixed   The break condition or it's default value
     */
    public static function executeWhileBreakCondition()
    {
        // get arguments
        $args = func_get_args();

        // get chainname
        $chainName = array_shift($args);

        // break condition and default return value
        $breakCondition = self::$_breakCondition;
        $return         = self::$_defaultReturnValue;

        // process CEC
        $cecIterator = cApiCECRegistry::getInstance()->getIterator($chainName);
        if ($cecIterator->count() > 0) {
            $cecIterator->reset();

            $bFirstIteration = true;
            while ($chainEntry = $cecIterator->next()) {
                // invoke CEC function
                $chainEntry->setParameters($args);
                $return = $chainEntry->execute();
                // process return value
                if (isset($return) && $return === $breakCondition) {
                    self::_reset();
                    return $return;
                    break;
                }
            }
        }

        // reset properties to defaults
        self::_reset();

        return $return;
    }


    /**
     * Resets some properties to defaults
     *
     * @return  void
     */
    private static function _reset()
    {
        self::$_breakCondition = null;
        self::$_returnBreakConditionDirectly = false;
        self::$_overwriteArguments = true;
        self::$_defaultReturnValue = null;
        self::$_returnArgumentPos = 1;
    }


    /**
     * Used to debug some status informations.
     *
     * Writes the debug value into a logfile (see contenido/logs/cec_hook_debug.log).
     *
     * @param   mixed   $var  The variable to dump
     * @param   string  $msg  Additional message
     * @return  void
     */
    private static function _debug($var, $msg='')
    {
        $content = ($msg !== '') ? $msg . ': ' : '';
        if (is_object($var) || is_array($var)) {
            $content .= print_r($var, true);
        } else {
            $content .= $var . "\n";
        }

        $sLogPathName = $GLOBALS['cfg']['path']['contenido'] . 'logs/cec_hook_debug.log';
        file_put_contents($sLogPathName, $content . "\n", FILE_APPEND);
    }

}
