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
//	// plugin startpage
//
//	$db = cRegistry::getDb();
//
//	$sql = 'SELECT DISTINCT idart, idcat FROM con_pi_user_forum WHERE idlang=1 and idclient=1';
//
//	$db->query($sql);
//
//	// get commented articles
//	$articles = array();
//	while($db->next_record()) {
//		$articles[] = array(
//			'idart' => $db->f('idart'),
//			'idcat' => $db->f('idcat')
//		);
//	}
//
//	$userForumCollection = new ArticleForumCollection();
//
//	foreach($articles as $article) {
//
//		// check if mod mode is active for this article
//		if ($userForumCollection->getModModeActive($article)) {
//
//			$sql = 'SELECT * FROM con_pi_user_forum WHERE idlang=1 and idclient=1 and idart=69 and online=0 and moderated=0';
//
//			$db->query($sql);
//
//			// get commented articles
//			$list = array();
//			while($db->next_record()) {
//				$list[] = $db->getRecord();
//			}
//
//			$rightBottom->getForum(69,56,1);
//			$rightBottom->render();
//		}
//	}

//echo '<pre>';
//	var_dump($list);
//echo '</pre>';

?>