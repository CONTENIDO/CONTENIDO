<?php
$rightBottom = new ArticleForumRightBottom('user_forum');
$rightBottom->receiveData($_GET, $_POST);
$rightBottom->render();
?>