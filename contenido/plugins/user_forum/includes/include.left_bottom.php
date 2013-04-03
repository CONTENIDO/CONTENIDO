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
// generates obj that renders the menustructur on the left side.
$leftBottom = new ArticleForumLeftBottom('left_bottom', 'commentedArticleList');
$leftBottom->receiveData($_GET);
$leftBottom->render();
?>