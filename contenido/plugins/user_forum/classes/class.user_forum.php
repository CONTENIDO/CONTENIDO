<?php
// assert CONTENIDO framework
defined('CON_FRAMEWORK') or die('Illegal call');
/**
 *
 * @package Plugin
 * @subpackage user_forum
 * @version SVN Revision $Rev:$
 * @author claus.schunk
 * @copyright four for business AG
 * @link http://www.4fb.de
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