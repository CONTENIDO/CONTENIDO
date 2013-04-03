<?php

/**
 *
 * @package Plugin
 * @subpackage user_forum/includes/
 * @version SVN Revision $Rev:$
 * @author claus.schunk
 * @copyright four for business AG
 * @link http://www.4fb.de
 */
// generates obj that renders the content at the right side.
$rightBottom = new ArticleForumRightBottom('user_forum');
$rightBottom->receiveData($_GET, $_POST);
$rightBottom->render();
?>