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
        $sql = "SELECT DISTINCT t.title, t.idart, f.idcat FROM con_art_lang t," . $this->table . " f WHERE f.idart=t.idart AND t.idlang = f.idlang ORDER BY id_user_forum ASC ;";
        $db->query($sql);

        $data = array();
        while ($db->next_record()) {
            array_push($data, $db->toArray());
        }

        return $data;
    }

    public function deleteHierarchie($keyPost, $level, $idart, $idcat, $lang) {
        // global $cfg;
        $db = cRegistry::getDb();

        $comments = $this->_getCommentHierachrie($idcat, $idart, $lang);
        $ar = array();

        echo $level . "<br>";
        $arri = array();

        foreach ($comments as $key => $com) {
            $com['key'] = $key;
            $arri[] = $com;
        }
        $idEntry = 0;
        $id_user_forum = array();
        $lastLevel = 0;
        for ($i = 0; $i < count($arri); $i++) {
            // select Entry
            if ($arri[$i]['key'] == $keyPost) {
                $idEntry = $arri[$i]['id_user_forum'];
                if ($arri[$i]['level'] < $arri[$i + 1]['level']) {
                    // $id_user_forum[] = $arri[$i + 1]['id_user_forum'];
                    echo "nextEntry is subComment" . "<br>";
                    // check for more subcomments
                    for ($j = $i + 1; $j < $arri[$j]; $j++) {
                        if ($arri[$i]['level'] < $arri[$j]['level']) {
                            $id_user_forum[] = $arri[$j]['id_user_forum'];
                        }
                    }
                }
            }
        }

        if (empty($id_user_forum)) {
            echo "DELETE $idEntry";
            $query = "DELETE FROM con_pi_user_forum WHERE id_user_forum = $idEntry";
            $db->query($query);
        } else {

            $query = "DELETE FROM con_pi_user_forum WHERE id_user_forum = $idEntry";
            $db->query($query);
            foreach ($id_user_forum as $com) {
                $query = "DELETE FROM con_pi_user_forum WHERE id_user_forum = $com";
                $db->query($query);
            }
        }
    }

    public function _getCommentHierachrie($id_cat, $id_art, $id_lang) {
        $cfg = cRegistry::getConfig();
        $db = cRegistry::getDb();

        $query = "SELECT * FROM " . $cfg['tab']['phplib_auth_user_md5'];

        $db->query($query);
        $arrUsers = array();

        while ($db->next_record()) {
            $arrUsers[$db->f('user_id')]['email'] = $db->f('email');
            $arrUsers[$db->f('user_id')]['realname'] = $db->f('realname');
        }
        $arrforum = array();
        $this->getTreeLevel($id_cat, $id_art, $id_lang, $arrUsers, $arrforum);
        $result = array();
        $this->normalizeArray($arrforum, $result);

        return $result;
    }

    function getTreeLevel($id_cat, $id_art, $id_lang, &$arrUsers, &$arrforum, $parent = 0) {
        $db = cRegistry::getDb();

        $query = "SELECT * FROM con_pi_user_forum WHERE (idart = $id_art) AND (idcat = $id_cat)
        AND (idlang = $id_lang) AND (id_user_forum_parent = $parent) ORDER BY timestamp DESC";

        $db->query($query);

        while ($db->next_record()) {
            $arrforum[$db->f('id_user_forum')]['userid'] = $db->f('userid');

            if (array_key_exists($db->f('userid'), $arrUsers)) {
                $arrforum[$db->f('id_user_forum')]['email'] = $arrUsers[$db->f('userid')]['email'];
                $arrforum[$db->f('id_user_forum')]['realname'] = $arrUsers[$db->f('userid')]['realname'];
            } else {
                $arrforum[$db->f('id_user_forum')]['email'] = $db->f('email');
                $arrforum[$db->f('id_user_forum')]['realname'] = $db->f('realname');
            }

            $arrforum[$db->f('id_user_forum')]['forum'] = str_replace(chr(13) . chr(10), '<br />', $db->f('forum'));
            $arrforum[$db->f('id_user_forum')]['forum_quote'] = str_replace(chr(13) . chr(10), '<br />', $db->f('forum_quote'));
            $arrforum[$db->f('id_user_forum')]['timestamp'] = $db->f('timestamp');
            $arrforum[$db->f('id_user_forum')]['like'] = $db->f('like');
            $arrforum[$db->f('id_user_forum')]['dislike'] = $db->f('dislike');

            $arrforum[$db->f('id_user_forum')]['editedat'] = $db->f('editedat');
            $arrforum[$db->f('id_user_forum')]['editedby'] = $db->f('editedby');

            // Added values to array for allocation
            $arrforum[$db->f('id_user_forum')]['idcat'] = $db->f('idcat');
            $arrforum[$db->f('id_user_forum')]['idart'] = $db->f('idart');
            $arrforum[$db->f('id_user_forum')]['id_user_forum'] = $db->f('id_user_forum');
            $arrforum[$db->f('id_user_forum')]['online'] = $db->f('online');
            $arrforum[$db->f('id_user_forum')]['editedat'] = $db->f('editedat');
            $arrforum[$db->f('id_user_forum')]['editedby'] = $db->f('editedby');

            $this->getTreeLevel($id_cat, $id_art, $id_lang, $arrUsers, $arrforum[$db->f('id_user_forum')]['children'], $db->f('id_user_forum'));
        }
    }

    function updateValues($id_user_forum, $name, $email, $like, $dislike, $forum, $online, $checked) {
        // method receives checked as string, DB needs integer.
        ($checked === 'set_online')? $online = 1 : $online = 0;
        // check for negative inputs
        ($like >= 0)?  : $like = 0;
        ($dislike >= 0)?  : $dislike = 0;
        // actual user

        $uuid = cRegistry::getAuth()->isAuthenticated();

        $timeStamp = date('Y-m-d H:i:s', time());

        $cfg = cRegistry::getConfig();
        $db = cRegistry::getDb();
        $sql = "UPDATE con_pi_user_forum SET `realname` = '$name' ,`editedby` = '$uuid'  , `email` = '$email' ,
        `forum` = '$forum' , `editedat` = '$timeStamp' , `like` = $like , `dislike` = $dislike ,
        `online` = $online  WHERE id_user_forum = $id_user_forum;";

        $db->query($sql);
    }

    /**
     * toggles the given input with update in db.
     *
     * @param $onlineState
     * @param primary key $id_user_forum
     */
    function toggleOnlineState($onlineState, $id_user_forum) {
        ($onlineState == 0)? $onlineState = 1 : $onlineState = 0;

        $db = cRegistry::getDb();
        $query = "UPDATE con_pi_user_forum SET online = $onlineState WHERE id_user_forum = $id_user_forum;";
        $db->query($query);
    }

    /**
     *
     * @param $id_cat
     * @param $id_art
     * @param $id_lang
     * @return ArticleForumRightBottom
     */
    function getExistingforum($id_cat, $id_art, $id_lang) {
        $cfg = cRegistry::getConfig();

        $db = cRegistry::getDb();

        $query = "SELECT * FROM " . $cfg['tab']['phplib_auth_user_md5'];

        $db->query($query);

        $arrUsers = array();

        while ($db->next_record()) {
            $arrUsers[$db->f('user_id')]['email'] = $db->f('email');
            $arrUsers[$db->f('user_id')]['realname'] = $db->f('realname');
        }

       // print_r($arrUsers);
        return $arrUsers;
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

}

// function deleteComment($id_user_forum) {
// $cfg = cRegistry::getConfig();
// $db = cRegistry::getDb();
// $sql = "DELETE FROM " . $this->table . " WHERE id_user_forum =
// $id_user_forum;";
// $db->query($sql);
// }

// function deleteComments(array $id_user_forum) {
// $cfg = cRegistry::getConfig();
// $db = cRegistry::getDb();
// foreach ($id_user_forum as $key => $id) {
// $sql = "DELETE FROM " . $this->table . "WHERE id_user_forum = $id;";
// $db->query($sql);
// }
// }

// function updateOnlineState($id_user_forum, $onlineState) {
// $cfg = cRegistry::getConfig();
// $db = cRegistry::getDb();
// $sql = "UPDATE" . $this->table . "SET online = $onlineState WHERE
// id_user_forum = $id_user_forum;";
// $db->query($sql);
// }

// function updateAll($id_user_forum, $name, $email, $like, $dislike) {
// $cfg = cRegistry::getConfig();
// $db = cRegistry::getDb();
// $sql = "UPDATE" . $this->table . "SET name = $name, email =$email, like =
// $like, dislike = $dislike WHERE id_user_forum = $id_user_forum;";
// $db->query($sql);
// }

// public function getIdCat($idart) {
// $cfg = cRegistry::getConfig();
// $db = cRegistry::getDb();
// $sql = "SELECT DISTINCT idcat FROM " . $this->table . " WHERE idart=" .
// $idart . ";";

// $db->query($sql);

// $data = array();
// while ($db->next_record()) {
// array_push($data, $db->toArray());
// }

// return $data;
// }

// public function getCommentTextByID($idart) {
// $cfg = cRegistry::getConfig();
// $db = cRegistry::getDb();
// $sql = "SELECT forum,id_user_forum, email, realname FROM " . $this->table . "
// WHERE idart = " . $idart . " AND id_user_forum_parent = 0 ORDER BY
// id_user_forum ASC ;";
// $db->query($sql);

// $data = array();
// while ($db->next_record()) {
// array_push($data, $db->toArray());
// }

// return $data;
// }

// public function getAllChildrenComments($idart) {
// $cfg = cRegistry::getConfig();
// $db = cRegistry::getDb();
// $sql = "Select id_user_forum_parent, id_user_forum, email,realname, forum
// FROM " . $this->table . " WHERE id_user_forum_parent != 0 AND idart = " .
// $idart . " ORDER BY id_user_forum_parent ASC ;";
// $db->query($sql);
// $data = array();
// while ($db->next_record()) {
// array_push($data, $db->toArray());
// }

// return $data;
// }

?>