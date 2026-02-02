<?php

/**
 * This file contains the class for plugin settings
 *
 * @package    Plugin
 * @subpackage UserForum
 * @author     Claus Schunk
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains plugin settings.
 *
 *
 * @package    Plugin
 * @subpackage UserForum
 */
class UserForum
{

    /**
     * name of this plugin
     *
     * @var string
     */
    private static $name = 'user_forum';

    /**
     */
    public static function getName(): string
    {
        return self::$name;
    }

    /**
     * Return path to this plugins' folder.
     */
    public static function getPath(): string
    {
        $cfg = cRegistry::getConfig();

        $path = cRegistry::getBackendPath() . $cfg['path']['plugins'];
        $path .= self::$name . '/';

        return $path;
    }

    /**
     * Return URL to this plugins' folder.
     */
    public static function getUrl(): string
    {
        $cfg = cRegistry::getConfig();

        $path = cRegistry::getBackendUrl() . $cfg['path']['plugins'];
        $path .= self::$name . '/';

        return $path;
    }

    public static function i18n(string $key): string
    {
        try {
            return i18n($key, self::$name);
        } catch (\cException $e) {
            error_log(sprintf(
                'Plugin "%s" translation error: %s. Key: %s', self::$name, $e->getMessage(), $key)
            );
            return $key;
        }
    }

}
