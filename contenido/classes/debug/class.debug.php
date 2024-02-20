<?php

/**
 * This file contains the static debugger class.
 *
 * @package    Core
 * @subpackage Debug
 * @author     Rudi Bieller
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Debugger class
 *
 * @package    Core
 * @subpackage Debug
 */
class cDebug
{

    /**
     *
     * @var string
     */
    const DEBUGGER_VISIBLE = 'visible';

    /**
     *
     * @var string
     */
    const DEBUGGER_VISIBLE_ADV = 'visible_adv';

    /**
     *
     * @var string
     */
    const DEBUGGER_HIDDEN = 'hidden';

    /**
     *
     * @var string
     */
    const DEBUGGER_FILE = 'file';

    /**
     *
     * @var string
     */
    const DEBUGGER_VISIBLE_AND_FILE = 'vis_and_file';

    /**
     *
     * @var string
     */
    const DEBUGGER_DEVNULL = 'devnull';

    /**
     * Default debugger, defined in system settings
     *
     * @var string
     */
    protected static $_defaultDebuggerName;

    /**
     * Returns instance of debugger.
     * If not defined, it returns the debugger from the current system settings.
     *
     * @param string $sType [optional]
     *         The debugger to get, empty string to get debugger defined in system settings
     * @return cDebugInterface
     * @throws cInvalidArgumentException
     *         If type of debugger is unknown
     */
    public static function getDebugger(string $sType = ''): cDebugInterface
    {
        if (empty($sType)) {
            $sType = self::_getSystemSettingDebugger();
        }

        switch ($sType) {
            case self::DEBUGGER_VISIBLE:
                $oDebugger = cDebugVisible::getInstance();
                break;
            case self::DEBUGGER_VISIBLE_ADV:
                $oDebugger = cDebugVisibleAdv::getInstance();
                break;
            case self::DEBUGGER_HIDDEN:
                $oDebugger = cDebugHidden::getInstance();
                break;
            case self::DEBUGGER_FILE:
                $oDebugger = cDebugFile::getInstance();
                break;
            case self::DEBUGGER_VISIBLE_AND_FILE:
                $oDebugger = cDebugFileAndVisAdv::getInstance();
                break;
            case self::DEBUGGER_DEVNULL:
                $oDebugger = cDebugDevNull::getInstance();
                break;
            default:
                throw new cInvalidArgumentException('This type of debugger is unknown to cDebug: ' . $sType);
        }

        return $oDebugger;
    }

    /**
     * Prints a debug message if the settings allow it.
     * The debug messages will be
     * in a textarea in the header and in the file debuglog.txt. All messages are
     * immediately
     * written to the filesystem, but they will only show up when
     * cDebug::showAll() is called.
     *
     * @param string $message
     *         Message to display.
     *         NOTE: You can use buildStackString to show stacktrace
     *
     * @throws cInvalidArgumentException
     */
    public static function out(string $message)
    {
        self::getDebugger()->out($message);
    }

    /**
     * Adds a variable to the debugger.
     * This variable will be watched.
     *
     * @param mixed $var
     *                      A variable or an object
     * @param string $label [optional]
     *                      An optional description for the variable
     * @throws cInvalidArgumentException
     */
    public static function add($var, string $label = '')
    {
        self::getDebugger()->add($var, $label);
    }

    /**
     * Prints the cached debug messages to the screen
     *
     * @throws cInvalidArgumentException
     */
    public static function showAll()
    {
        self::getDebugger()->showAll();
    }

    /**
     * Returns default debugger name.
     *
     * @return string
     */
    public static function getDefaultDebuggerName(): string
    {
        return self::_getSystemSettingDebugger();
    }

    /**
     * Returns the debugger defined in system settings.
     *
     * @return string
     */
    protected static function _getSystemSettingDebugger(): string
    {
        if (isset(self::$_defaultDebuggerName)) {
            return self::$_defaultDebuggerName;
        }

        self::$_defaultDebuggerName = self::DEBUGGER_DEVNULL;

        try {
            if (getSystemProperty('debug', 'debug_to_file') == 'true') {
                self::$_defaultDebuggerName = self::DEBUGGER_FILE;
            } elseif (getSystemProperty('debug', 'debug_to_screen') == 'true') {
                self::$_defaultDebuggerName = self::DEBUGGER_VISIBLE_ADV;
            }
            if ((getSystemProperty('debug', 'debug_to_screen') == 'true') && (getSystemProperty('debug', 'debug_to_file') == 'true')) {
                self::$_defaultDebuggerName = self::DEBUGGER_VISIBLE_AND_FILE;
            }
        } catch (cDbException $e) {
        } catch (cException $e) {
        }

        return self::$_defaultDebuggerName;
    }

}
