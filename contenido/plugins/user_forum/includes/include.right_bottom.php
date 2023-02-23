<?php
/**
 * This file contains initialisation for right bottom
 *
 * @package Plugin
 * @subpackage UserForum
 * @author Claus Schunk
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// generates obj that renders the content on the right side.
if (!empty($_REQUEST['idart'])) {
	$rightBottom = new ArticleForumRightBottom();
	$rightBottom->receiveData($_GET, $_POST);
	$rightBottom->render();
} else {
	$rightBottom = new ArticleForumRightBottom();
	$rightBottom->getStartpage();
	$rightBottom->render();
}
