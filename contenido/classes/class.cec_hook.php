<?php
/**
 * This file contains the CEC hook class.
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
 * Static CEC Hook class, provides some public methods to process registered
 * chains at CEC (CONTENIDO Extension Chainer).
 *
 * Usage:
 * <code>
 * // example of executing a cec without a parameter and return value
 * cApiCecHook::execute('Contenido.Content.Somewhere');
 *
 * // example of executing a cec with a parameter but without a return value
 * $param = 'some value';
 * cApiCecHook::execute('Contenido.Content.Somewhere', $param);
 *
 * // example of executing a cec with multiple parameter but without a return
 * value
 * $param = array('foo' => $bar, 'foo2' => $bar2);
 * $param = cApiCecHook::execute('Contenido.Content.Somewhere', $param);
 *
 * // example of executing a cec without a parameter but a return value (with
 * predefined
 * // default return value)
 * cApiCecHook::setDefaultReturnValue('this is the default title');
 * $title = cApiCecHook::executeAndReturn('Contenido.Content.CreateTitletag');
 *
 * // example of executing a cec with a parameter and a return value
 * // (usually the modified version of passed parameter)
 * $baseHref = cRegistry::getFrontendUrl();
 * $newBaseHref =
 * cApiCecHook::executeAndReturn('Contenido.Frontend.BaseHrefGeneration',
 * $baseHref);
 *
 * // example of executing a cec with a break condition and default return value
 * cApiCecHook::setBreakCondition(false, true); // break condition = "false",
 * default return value = "true"
 * $allow =
 * cApiCecHook::executeWhileBreakCondition('Contenido.Frontend.AllowEdit',
 * $lang, $idcat, $idart, $auth->auth['uid']);
 * if ($allow == false) {
 * die('You're not coming in!');
 * }
 *
 * // another example of executing a cec with a break condition and default
 * return value
 * cApiCecHook::setBreakCondition(true, false); // break condition = "true",
 * default return value = "false"
 * $allow =
 * cApiCecHook::executeWhileBreakCondition('Contenido.Frontend.CategoryAccess',
 * $lang, $idcat, $auth->auth['uid']);
 * if ($allow == false) {
 * die('I said, you're not coming in!');
 * }
 * </code>
 *
 * @package Core
 * @subpackage CEC
 */
class cApiCecHook {

    /**
     * Temporaly stored break condition.
     *
     * @var int
     */
    private static $_breakCondition = NULL;

    /**
     * Temporaly stored default return value of CEC functions
     *
     * @var mixed
     */
    private static $_defaultReturnValue = NULL;

    /**
     * Temporaly stored position of argument to return.
     * It's used by cApiCecHook::executeAndReturn()
     * to store/extract the return value into/from arguments list.
     *
     * @var int
     */
    private static $_returnArgumentPos = 1;

    /**
     * Temporaly setting of break condition and optional the default return
     * value.
     *
     * @param mixed $condition
     * @param mixed $defaultReturnValue
     */
    public static function setBreakCondition($condition, $defaultReturnValue = NULL) {
        self::$_breakCondition = $condition;
        self::setDefaultReturnValue($defaultReturnValue);
    }

    /**
     * Temporaly setting of default return value.
     *
     * @param mixed $defaultReturnValue
     */
    public static function setDefaultReturnValue($defaultReturnValue) {
        self::$_defaultReturnValue = $defaultReturnValue;
    }

    /**
     * Temporaly setting of position in argument to return.
     *
     * @param int $pos Position, feasible value greater 0
     *
     * @throws cInvalidArgumentException if the given position is less than 1
     */
    public static function setReturnArgumentPos($pos) {
        if ((int) $pos < 1) {
            throw new cInvalidArgumentException('Return position has to be greater or equal than 1.');
        }
        self::$_returnArgumentPos = (int) $pos;
    }

    /**
     * Method to execute registered functions for CONTENIDO Extension Chainer
     * (CEC).
     * Gets the desired CEC iterator and executes each registered chain function
     * by passing the given arguments to it. NOTE: the first param is interpeted
     * as $chainName. NOTE: There is no restriction for number of passed
     * parameter.
     */
    public static function execute() {
        // get arguments
        $args = func_get_args();

        // get chainname
        $chainName = array_shift($args);

        // process CEC
        $cecIterator = cApiCecRegistry::getInstance()->getIterator($chainName);
        if ($cecIterator->count() > 0) {
            $cecIterator->reset();

            while (($chainEntry = $cecIterator->next()) !== false) {
                // invoke CEC function
                $chainEntry->setTemporaryArguments($args);
                $chainEntry->execute();
            }
        }

        // reset properties to defaults
        self::_reset();
    }

    /**
     * Method to execute registered functions for CONTENIDO Extension Chainer
     * (CEC).
     * Gets the desired CEC iterator and executes each registered chain
     * function. You can pass as much parameters as you want. NOTE: the first
     * param is interpeted as $chainName. NOTE: There is no restriction for
     * number of passed parameter. NOTE: If no chain function is registered,
     * $_defaultReturnValue will be returned.
     *
     * @return mixed
     *         Parameter changed/processed by chain functions.
     */
    public static function executeAndReturn() {
        // get arguments
        $args = func_get_args();

        // get chainname
        $chainName = array_shift($args);

        // position of return value in arguments list
        $pos = self::$_returnArgumentPos - 1;

        // default return value
        $return = self::$_defaultReturnValue;

        // process CEC
        $cecIterator = cApiCecRegistry::getInstance()->getIterator($chainName);
        if ($cecIterator->count() > 0) {
            $cecIterator->reset();

            while (($chainEntry = $cecIterator->next()) !== false) {
                // invoke CEC function
                $chainEntry->setTemporaryArguments($args);
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
     * Gets the desired CEC iterator and executes each registered chain function
     * as long as defined break condition doesn't occur. NOTE: the first
     * param is interpeted as $chainName. NOTE: There is no restriction for
     * number of passed parameter. NOTE: If no chain function is registered,
     * $_defaultReturnValue will be returned.
     *
     * @return mixed
     *         The break condition or it's default value
     */
    public static function executeWhileBreakCondition() {
        // get arguments
        $args = func_get_args();

        // get chainname
        $chainName = array_shift($args);

        // break condition and default return value
        $breakCondition = self::$_breakCondition;
        $return = self::$_defaultReturnValue;

        // process CEC
        $cecIterator = cApiCecRegistry::getInstance()->getIterator($chainName);
        if ($cecIterator->count() > 0) {
            $cecIterator->reset();

            while (($chainEntry = $cecIterator->next()) !== false) {
                // invoke CEC function
                $chainEntry->setTemporaryArguments($args);
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
     */
    private static function _reset() {
        self::$_breakCondition = NULL;
        self::$_defaultReturnValue = NULL;
        self::$_returnArgumentPos = 1;
    }

    /**
     * Used to debug some status informations.
     *
     * @todo Implement cec_hook debug mode for automatic logging when activated.
     *       Writes the debug value into a logfile (see
     *       contenido/data/log/cec_hook_debug.log).
     *
     * @param mixed $var The variable to dump
     * @param string $msg Additional message
     */
    private static function _debug($var, $msg = '') {
        global $cfg;

        $content = ($msg !== '') ? $msg . ': ' : '';
        if (is_object($var) || is_array($var)) {
            $content .= print_r($var, true);
        } else {
            $content .= $var . "\n";
        }

        $sLogPathName = $cfg['path']['contenido_logs'] . 'cec_hook_debug.log';
        cFileHandler::write($sLogPathName, $content . "\n", true);

        cDebug::out($content);
    }
}

