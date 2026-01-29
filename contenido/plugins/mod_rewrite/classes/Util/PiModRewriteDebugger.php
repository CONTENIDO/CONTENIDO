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
class PiModRewriteDebugger
{

    /**
     * @var bool Flag to enable debugger
     */
    protected static $enabled = false;

    /**
     * @param bool $enabled Enable debugger setter.
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
     * @throws cInvalidArgumentException
     */
    public static function log($value, string $label = '')
    {
        if (!self::$enabled) {
            return;
        }
        cDebug::getDebugger(cDebug::DEBUGGER_FILE)->show($value, $label);
    }

    /**
     * Debug output only during development
     *
     * @param bool $print Flag to echo the debug data
     * @return ?string Either the debug data, if parameter $print is set to true, or `null`
     * @throws cInvalidArgumentException
     */
    public static function output(bool $print = true): ?string
    {
        $profileData = cDb::getProfileData();
        if (count($profileData) > 0) {
            self::add($profileData, 'sql statements');

            // Calculate total time consumption of queries
            $timeTotal = 0;
            foreach ($profileData as $item) {
                $timeTotal += $item['time'];
            }
            self::add($timeTotal, 'sql total time');
        }

        $sOutput = self::getAll();
        if ($print) {
            echo $sOutput;
            return null;
        } else {
            return $sOutput;
        }
    }

}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteDebugger} instead.
 */
class ModRewriteDebugger extends PiModRewriteDebugger
{}
