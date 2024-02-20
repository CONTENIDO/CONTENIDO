<?php

/**
 * This file contains initialisation for left bottom
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
 * @var array $cfg
 */

// generates obj that renders the menu structure on the left side.
$leftBottom = new ArticleForumLeftBottom();
$menu = $leftBottom->receiveData($_GET);

$tpl = new cTemplate();
$tpl->set('s', 'menu', $menu->render(false));
$tpl->set('s', 'DELETE_MESSAGE', UserForum::i18n('ALLDELETEFROMCAT'));
$tpl->generate($cfg['templates']['user_forum_left_bottom']);
