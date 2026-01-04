<?php

/**
 * AMR debugger class
 *
 * @package    Plugin
 * @subpackage ModRewrite
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Mod rewrite debugger class.
 *
 * @author     Murat Purc <murat@purc.de>
 * @package    Plugin
 * @subpackage ModRewrite
 */
class ModRewriteDebugger
{

    /**
     * Flag to enable debugger
     * @var bool
     */
    protected static $enabled = false;

    /**
     * Enable debugger setter.
     * @param bool $enabled
     */
    public static function setEnabled(bool $enabled)
    {
        self::$enabled = $enabled;
    }

    /**
     * Adds variable to debugger.
     * Wrapper for <code>cDebug::getDebugger('visible_adv')</code>.
     *
     * @param mixed $value The variable to dump
     * @param string $label Description for passed $value
     * @throws cInvalidArgumentException
     */
    public static function add($value, string $label = '')
    {
        if (!self::$enabled) {
            return;
        }
        cDebug::getDebugger()->add($value, $label);
    }

    /**
     * Returns output of all added variables to debug.
     *
     * @return string
     * @throws cInvalidArgumentException
     */
    public static function getAll(): string
    {
        if (!self::$enabled) {
            return '';
        }
        ob_start();
        cDebug::getDebugger()->showAll();
        $sOutput = ob_get_contents();
        ob_end_clean();
        return $sOutput;
    }

    /**
     * Logs variable to debugger.
     * Wrapper for <code>cDebug::getDebugger(cDebug::DEBUGGER_FILE)</code>.
     *
     * @param mixed $value The variable to log the contents
     * @param string $label Description for passed $value
     *
     * @throws cInvalidArgumentException
     */
    public static function log($value, string $label = '')
    {
        if (!self::$enabled) {
            return;
        }
        cDebug::getDebugger(cDebug::DEBUGGER_FILE)->show($value, $label);
    }

}
