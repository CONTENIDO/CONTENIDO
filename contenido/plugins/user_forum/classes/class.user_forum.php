<?php
/**
 * This file contains the class for plugin settings
 *
 * @package Plugin
 * @subpackage UserForum
 * @author Claus Schunk
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains plugin settings.
 *
 *
 * @package Plugin
 * @subpackage UserForum
 */
class UserForum {

    /**
     * name of this plugin
     *
     * @var string
     */
    private static $_name = 'user_forum';

    /**
     */
    public static function getName() {
        return self::$_name;
    }

    public static function i18n($key) {
        $trans = i18n($key, self::$_name);

        return $trans;
    }

    /**
     * Return URL to this plugins folder.
     *
     * @return string
     */
    public static function getUrl() {
        $cfg = cRegistry::getConfig();

        $path = cRegistry::getBackendUrl() . $cfg['path']['plugins'];
        $path .= self::$_name . '/';

        return $path;
    }

}