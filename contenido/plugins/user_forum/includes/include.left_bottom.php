<?php

// perform action delete_form
if ($_GET['action'] === 'delete_form') {
    $arts = new ArticleForumCollection();
    $arts->deleteAllCommentsById($_GET['idart']);
}

// TODO duplicate instantiation of a cGuiPage
$leftBottom = new ArticleForumLeftBottom('left_bottom', 'commentedArticleList');
$menu = $leftBottom->getMenu();

$page = new cGuiPage("rights_menu");
$page->setContent($menu);
$page->render();

?>