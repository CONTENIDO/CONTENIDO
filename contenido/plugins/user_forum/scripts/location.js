function deleteArticlesByIdLeft(idart) {

    url = 'main.php?area=user_forum';
    url += '&action=delete_form';
    url += '&frame=2';
    url += '&idart='
    url += idart;

    reloadRightBottom = 'main.php?area=user_forum';
    reloadRightBottom += '&action=empty';
    reloadRightBottom += '&frame=4';

    parent.parent.left.left_bottom.location.href = url;
    parent.parent.right.right_bottom.location.href = reloadRightBottom;
}

function deleteArticlesByIdRight(level, key, id, idcat, idart) {

    url = 'main.php?area=user_forum';
    url += '&action=deleteComment';
    url += '&frame=4';
    url += '&idart=';
    url += idart;
    url += '&key=';
    url += key;
    url += '&idcat=';
    url += idcat;
    url += '&level=';
    url += level;
    url += '&id_user_forum=';
    url += id;

    reloadLeftBottom = 'main.php?area=user_forum';
    // reloadLeftBottom += '&action=delete_form';
    reloadLeftBottom += '&frame=2';
    reloadLeftBottom += '&idart='
    reloadLeftBottom += idart;

    parent.parent.right.right_bottom.location.href = url;
    parent.parent.left.left_bottom.location.href = reloadLeftBottom;
}
