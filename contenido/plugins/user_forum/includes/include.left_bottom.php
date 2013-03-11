<?php
$arts = new ArticleForumCollection();

$leftButtom = new ArticleForumLeftBottom('left_bottom','commentedArticleList');


if($_GET['action'] ==='delete_form')
{
    $arts->deleteAllCommentsById($_GET['idart']);
    $leftButtom->getMenu()->render();

}
else {
    $leftButtom->getMenu()->render();
}

?>