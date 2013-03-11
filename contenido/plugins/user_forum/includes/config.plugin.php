<?php
defined('CON_FRAMEWORK') or die('Illegal call');

$cfg['plugins']['user_forum'] = 'user_forum/';

$cfg['templates']['user_forum_right_bottom'] = $cfg['plugins']['user_forum'] . 'templates/XXXXX';
$cfg['tab']['user_forum'] = $cfg['sql']['sqlprefix'] . '_pi_user_forum';

$pluginClassPath = 'contenido/plugins/user_forum/';
cAutoload::addClassmapConfig(array(
    'ArticleForumCollection' => $pluginClassPath . 'classes/class.article_forum_collection.php',
    'ArticleForum' => $pluginClassPath . 'classes/class.article_forum.php',
    'ArticleForumLeftBottom' => $pluginClassPath . 'classes/class.article_forum_left_bottom.php',
    'ArticleForumRightBottom' => $pluginClassPath . 'classes/class.article_forum_right_bottom.php',
));

?>