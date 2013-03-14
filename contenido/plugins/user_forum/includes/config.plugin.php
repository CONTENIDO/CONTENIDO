<?php

/**
 *
 * @package Plugin
 * @subpackage user forum
 * @version SVN Revision $Rev:$
 * @author claus.schunk
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') or die('Illegal call');

/**
 *
 * @author claus.schunk
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
}

// define plugin path
$cfg['plugins']['user_forum'] = 'user_forum/';

// define template names
$cfg['templates']['user_forum_right_bottom'] = $cfg['plugins']['user_forum'] . 'templates/XXXXX';

// define table names
$cfg['tab']['user_forum'] = $cfg['sql']['sqlprefix'] . '_pi_user_forum';

// include necessary sources, setup autoloader for plugin
// @todo Use config variables for $pluginClassPath below!
$pluginClassPath = 'contenido/plugins/' . $cfg['plugins']['user_forum'];
cAutoload::addClassmapConfig(array(
    'ArticleForumCollection' => $pluginClassPath . 'classes/class.article_forum_collection.php',
    'ArticleForum' => $pluginClassPath . 'classes/class.article_forum.php',
    'ArticleForumLeftBottom' => $pluginClassPath . 'classes/class.article_forum_left_bottom.php',
    'ArticleForumRightBottom' => $pluginClassPath . 'classes/class.article_forum_right_bottom.php'
));

?>