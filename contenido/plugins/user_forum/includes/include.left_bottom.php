<?php
$leftBottom = new ArticleForumLeftBottom('left_bottom', 'commentedArticleList');
$leftBottom->receiveData($_GET);
$leftBottom->render();
?>