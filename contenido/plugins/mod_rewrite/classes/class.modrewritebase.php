<?php
/**
 * Includes base mod rewrite class.
 *
 * @author      Murat Purc <murat@purc.de>
 * @date        24.09.2008
 * @package     Contenido
 * @subpackage  ModRewrite
 */


defined('CON_FRAMEWORK') or die('Illegal call');


/**
 * Abstract base mod rewrite class.
 *
 * Provides some common features such as common debugging, globals/configuration
 * access for childs.
 *
 * @author      Murat Purc <murat@purc.de>
 * @date        24.09.2008
 * @package     Contenido
 * @subpackage  ModRewrite
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
        return (self::getConfig('use', 0) == 1) ? true : false;
    }

    /**
     * Sets the enabled state of mod rewrite plugin
     *
     * @pparam  bool  $bEnabled
     */
    public static function setEnabled($bEnabled)
    {
        self::setConfig('use', (bool) $bEnabled);
    }


    /**
     * Returns configuration of mod rewrite, content of gobal $cfg['mod_rewrite']
     *
     * @param   string  $key  Name of configuration key
     * @return  mixed   Desired value mr configuration, either the full configuration
     *                  or one of the desired subpart
     */
    public static function getConfig($key=null, $default=null)
    {
        global $cfg;
        if ($key == null) {
            return $cfg['mod_rewrite'];
        } elseif ((string) $key !== '') {
            return (isset($cfg['mod_rewrite'][$key])) ? $cfg['mod_rewrite'][$key] : $default;
        } else {
            return $default;
        }
    }

    /**
     * Sets the configuration of mod rewrite, content of gobal $cfg['mod_rewrite']
     *
     * @param   string  $key    Name of configuration key
     * @param   mixed   $value  The value to set
     */
    public static function setConfig($key, $value)
    {
        global $cfg;
        $cfg['mod_rewrite'][$key] = $value;
    }

}
