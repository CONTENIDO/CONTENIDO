<?php
/**
 * This file contains initialisation for left bottom
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

 //generates obj that renders the menustructur on the left side.
$leftBottom = new ArticleForumLeftBottom();
$menu = $leftBottom->receiveData($_GET);

$tpl = new cTemplate();
$tpl->set('s', 'menu', $menu->render());
$tpl->generate('plugins/user_forum/templates/template.left_bottom.html');

?>