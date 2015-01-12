<?php
/**
 * This file contains initialisation for right bottom
 *
 * @package Plugin
 * @subpackage UserForum
 * @version SVN Revision $Rev:$
 *
 * @author Claus Schunk
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');


// generates obj that renders the content at the right side.
if (!empty($_REQUEST['idart'])) {
	$rightBottom = new ArticleForumRightBottom();
	$rightBottom->receiveData($_GET, $_POST);
	$rightBottom->render();
} else {
	$rightBottom = new ArticleForumRightBottom();
	$rightBottom->getStartpage();
	$rightBottom->render();
}
$tpl = new cTemplate();
$tpl->generate('plugins/user_forum/templates/template.right_bottom.html');

?>