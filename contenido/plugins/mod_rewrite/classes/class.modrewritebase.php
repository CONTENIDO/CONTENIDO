<?php

/**
 * AMR base Mod Rewrite class
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
 * Abstract base mod rewrite class.
 *
 * Provides some common features such as common debugging, globals/configuration
 * access for children.
 *
 * @author     Murat Purc <murat@purc.de>
 * @package    Plugin
 * @subpackage ModRewrite
 */
abstract class ModRewriteBase
{

    /**
     * Returns enabled state of mod rewrite plugin
     *
     * @return  bool
     */
    public static function isEnabled()
    {
        return self::getConfig('use', 0) == 1;
    }

    /**
     * Sets the enabled state of mod rewrite plugin
     *
     * @param bool $bEnabled
     */
    public static function setEnabled($bEnabled)
    {
        self::setConfig('use', (bool)$bEnabled);
    }

    /**
     * Returns configuration of mod rewrite, content of global $cfg['mod_rewrite']
     *
     * @param string $key Name of configuration key
     * @param mixed $default Default value to return as a fallback
     * @return  mixed   Desired value mr configuration, either the full configuration
     *                  or one of the desired subpart
     */
    public static function getConfig($key = NULL, $default = NULL)
    {
        $cfg = cRegistry::getConfig();
        if ($key == NULL) {
            return $cfg['mod_rewrite'];
        } elseif ((string)$key !== '') {
            return (isset($cfg['mod_rewrite'][$key])) ? $cfg['mod_rewrite'][$key] : $default;
        } else {
            return $default;
        }
    }

    /**
     * Sets the configuration of mod rewrite, content of global $cfg['mod_rewrite']
     *
     * @param string $key Name of configuration key
     * @param mixed $value The value to set
     */
    public static function setConfig($key, $value)
    {
        // Use global here, we update the variable!
        global $cfg;
        $cfg['mod_rewrite'][$key] = $value;
    }

}
