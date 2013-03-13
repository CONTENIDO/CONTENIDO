<?php
defined('CON_FRAMEWORK') or die('Illegal call');
class ArticleForumCollection extends ItemCollection {

    function __construct() {
        $cfg = cRegistry::getConfig();

        parent::__construct($cfg['tab']['user_forum'], 'id_user_forum');

        $this->_setItemClass('ArticleForum');

        // if (false !== $where) {
        // $this->select($where);
    }

    public function getAllCommentedArticles() {
        $cfg = cRegistry::getConfig();
        $db = cRegistry::getDb();
        $sql = "SELECT DISTINCT t.title, t.idart FROM con_art_lang t," . $this->table . " f WHERE f.idart=t.idart AND t.idlang = f.idlang ORDER BY id_user_forum ASC ;";
        $db->query($sql);

        $data = array();
        while ($db->next_record()) {
            array_push($data, $db->toArray());
        }

        return $data;
    }

    function deleteComment($id_user_forum) {
        $cfg = cRegistry::getConfig();
        $db = cRegistry::getDb();
        $sql = "DELETE FROM " . $this->table . "WHERE id_user_forum = $id_user_forum;";
        $db->query($sql);
    }

    function deleteComments(array $id_user_forum) {
        $cfg = cRegistry::getConfig();
        $db = cRegistry::getDb();
        foreach ($id_user_forum as $key => $id) {
            $sql = "DELETE FROM " . $this->table . "WHERE id_user_forum = $id;";
            $db->query($sql);
        }
    }

    function updateOnlineState($id_user_forum, $onlineState) {
        $cfg = cRegistry::getConfig();
        $db = cRegistry::getDb();
        $sql = "UPDATE" . $this->table . "SET online = $onlineState WHERE id_user_forum = $id_user_forum;";
        $db->query($sql);
    }

    function updateAll($id_user_forum, $name, $email, $like, $dislike) {
        $cfg = cRegistry::getConfig();
        $db = cRegistry::getDb();
        $sql = "UPDATE" . $this->table . "SET name = $name, email =$email, like = $like, dislike = $dislike WHERE id_user_forum = $id_user_forum;";
        $db->query($sql);
    }

    public function getIdCat($idart) {
        $cfg = cRegistry::getConfig();
        $db = cRegistry::getDb();
        $sql = "SELECT DISTINCT idcat FROM " . $this->table . "  WHERE idart=" . $idart . ";";

        $db->query($sql);

        $data = array();
        while ($db->next_record()) {
            array_push($data, $db->toArray());
        }

        return $data;
    }

    public function getCommentTextByID($idart) {
        $cfg = cRegistry::getConfig();
        $db = cRegistry::getDb();
        $sql = "SELECT forum,id_user_forum, email, realname FROM " . $this->table . " WHERE idart = " . $idart . " AND id_user_forum_parent = 0 ORDER BY id_user_forum ASC ;";
        $db->query($sql);

        $data = array();
        while ($db->next_record()) {
            array_push($data, $db->toArray());
        }

        return $data;
    }

    public function deleteAllCommentsById($idart) {
        $cfg = cRegistry::getConfig();
        $db = cRegistry::getDb();
        $sql = "DELETE FROM " . $this->table . " WHERE idart = " . $idart . " ;";
        $db->query($sql);
    }

    public function deleteCommentById($idart) {
        $cfg = cRegistry::getConfig();
        $db = cRegistry::getDb();
        $sql = "DELETE FROM " . $this->table . " WHERE id_user_forum = " . $idart . " ;";
        $db->query($sql);
    }

    public function getAllChildrenComments($idart) {
        $cfg = cRegistry::getConfig();
        $db = cRegistry::getDb();
        $sql = "Select id_user_forum_parent, id_user_forum, email,realname, forum FROM " . $this->table . " WHERE id_user_forum_parent != 0 AND idart = " . $idart . " ORDER BY id_user_forum_parent ASC ;";
        $db->query($sql);
        $data = array();
        while ($db->next_record()) {
            array_push($data, $db->toArray());
        }

        return $data;
    }

}

?>