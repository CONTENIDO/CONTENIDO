function deleteArticlesByIdLeft(idart) {
    var left_bottom = Con.getFrame('left_bottom'),
        right_bottom = Con.getFrame('right_bottom');

    if (left_bottom) {
        left_bottom.location.href = Con.UtilUrl.build('main.php', {
            area: 'user_forum',
            action: 'delete_form',
            frame: 2,
            idart: idart
        });
    }

    if (right_bottom) {
        right_bottom.location.href = Con.UtilUrl.build('main.php', {
            area: 'user_forum',
            action: 'empty',
            frame: 4,
            idart: idart
        });
    }
}

function deleteArticlesByIdRight(level, key, id, idcat, idart) {
    var right_bottom = Con.getFrame('right_bottom');
    if (right_bottom) {
        right_bottom.location.href = Con.UtilUrl.build('main.php', {
            area: 'user_forum',
            action: 'deleteComment',
            frame: 4,
            idart: idart,
            idcat: idcat,
            key: key,
            level: level,
            id_user_forum: id
        });
    }
}
