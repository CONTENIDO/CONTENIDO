<?php
$arts = new ArticleForumCollection();

$leftBottom = new ArticleForumLeftBottom('left_bottom','commentedArticleList');


if($_GET['action'] ==='delete_form')
{
    $arts->deleteAllCommentsById($_GET['idart']);

}

$page = new cGuiPage("rights_menu");
$menu = $leftBottom->getMenu();
$page->setContent($menu);
$page->render();

?>