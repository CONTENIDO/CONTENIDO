<?php

/**
 * This file contains configuration for plugin.
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

global $cfg;

$pluginName = basename(dirname(__DIR__, 1));

$cfg['plugins'][$pluginName] = cRegistry::getBackendPath() . $cfg['path']['plugins'] . "$pluginName/";

// define template names
$pluginTemplatesPath = cRegistry::getBackendPath() . $cfg['path']['plugins'] . "$pluginName/templates";
$cfg['templates']['user_forum_left_bottom'] = $pluginTemplatesPath . '/template.left_bottom.html';

// define table names
$cfg['tab']['user_forum'] = $cfg['sql']['sqlprefix'] . '_pi_user_forum';

// include necessary sources, setup autoloader for plugin
$pluginClassesPath = cRegistry::getBackendPath(true) . $cfg['path']['plugins'] . "$pluginName/classes";
cAutoload::addClassmapConfig([
    'ArticleForumCollection' => $pluginClassesPath . '/class.article_forum_collection.php',
    'ArticleForum' => $pluginClassesPath . '/class.article_forum.php',
    'ArticleForumLeftBottom' => $pluginClassesPath . '/class.article_forum_left_bottom.php',
    'ArticleForumRightBottom' => $pluginClassesPath . '/class.article_forum_right_bottom.php',
    'ArticleForumItem' => $pluginClassesPath . '/class.article_forum_item.php',
    'UserForum' => $pluginClassesPath . '/class.user_forum.php',
    'cContentTypeUserForum' => $pluginClassesPath . '/class.content.type.user_forum.php'
]);

unset($pluginName, $pluginTemplatesPath, $pluginClassesPath);
