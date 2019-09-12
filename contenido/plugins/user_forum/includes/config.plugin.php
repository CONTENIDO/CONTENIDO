<?php
/**
 * This file contains configuration for plugin.
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
    'ArticleForumRightBottom' => $pluginClassPath . 'classes/class.article_forum_right_bottom.php',
    'ArticleForumItem' => $pluginClassPath .'classes/class.article_forum_item.php',
    'UserForum' => $pluginClassPath .'classes/class.user_forum.php',
    'cContentTypeUserForum' => $pluginClassPath .'classes/class.content.type.user_forum.php'
));

?>