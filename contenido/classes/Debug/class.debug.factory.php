<?php
/**
 * Static debugger factory.
 *
 * @package Core
 * @subpackage Debug
 * @version SVN Revision $Rev:$
 * @version SVN Id $Id$
 *
 * @author Rudi Bieller
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Debugger factory class
 *
 * @package Core
 * @subpackage Debug
 */
class cDebugFactory {

    protected static $_systemSettingDebugger;

    /**
     * Returns instance of debugger. If not defined, it returns the debugger from the current system settings.
     * @param  string  $sType  The debugger to get, empty string to get debugger defined in system settings
     * @return cDebugInterface
     * @throws  InvalidArgumentException  If type of debugger is unknown
     */
    public static function getDebugger($sType = '') {
        if (empty($sType)) {
            $sType = self::_getSystemSettingDebugger();
        }

        $oDebugger = null;
        switch ($sType) {
            case 'visible':
                $oDebugger = cDebugVisible::getInstance();
                break;
            case 'visible_adv':
                $oDebugger = cDebugVisibleAdv::getInstance();
                break;
            case 'hidden':
                $oDebugger = cDebugHidden::getInstance();
                break;
            case 'file':
                $oDebugger = cDebugFile::getInstance();
                break;
            case 'vis_and_file':
                $oDebugger = cDebugFileAndVisAdv::getInstance();
                break;
            case 'devnull':
                $oDebugger = cDebugDevNull::getInstance();
                break;
            default:
                throw new InvalidArgumentException('This type of debugger is unknown to cDebugFactory: ' . $sType);
                break;
        }
        return $oDebugger;
    }

    /**
     * Return the debugger defined in system settings.
     * @return string
     */
    protected static function _getSystemSettingDebugger() {
        if (isset(self::$_systemSettingDebugger)) {
            return self::$_systemSettingDebugger;
        }
        self::$_systemSettingDebugger = 'devnull';
        if (getSystemProperty('debug', 'debug_to_file') == 'true') {
            self::$_systemSettingDebugger = 'file';
        } else if (getSystemProperty('debug', 'debug_to_screen') == 'true') {
            self::$_systemSettingDebugger = 'visible_adv';
        }
        if ((getSystemProperty('debug', 'debug_to_screen') == 'true') && (getSystemProperty('debug', 'debug_to_file') == 'true')) {
            self::$_systemSettingDebugger = 'vis_and_file';
        }
        return self::$_systemSettingDebugger;
    }

}

/** @deprecated  [2012-07-24]  Class was renamed to cDebugFactory */
class DebuggerFactory extends cDebugFactory {
    /** @deprecated Class was renamed to cDebugFactory */
    public static function getDebugger($sType) {
        cDeprecated('Use cDebugFactory::getDebugger()');
        return cDebugFactory::getDebugger($sType);
    }
}