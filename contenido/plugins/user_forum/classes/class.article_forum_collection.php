<?php
defined('CON_FRAMEWORK') or die('Illegal call');
class ArticleForumCollection extends ItemCollection {

    protected $cfg;

    protected $db;

    public function __construct() {
        $this->db = cRegistry::getDb();
        $this->cfg = cRegistry::getConfig();
        parent::__construct($this->cfg['tab']['user_forum'], 'id_user_forum');
        $this->_setItemClass('ArticleForum');
    }

    public function getAllCommentedArticles() {
        $sql = "SELECT DISTINCT t.title, t.idart, f.idcat FROM con_art_lang t," . $this->table . " f WHERE f.idart=t.idart AND t.idlang = f.idlang ORDER BY id_user_forum ASC ;";
        $this->db->query($sql);

        $data = array();
        while ($this->db->next_record()) {
            array_push($data, $this->db->toArray());
        }

        return $data;
    }

    public function deleteHierarchie($keyPost, $level, $idart, $idcat, $lang) {
        $comments = $this->_getCommentHierachrie($idcat, $idart, $lang);

        $ar = array();

        // echo $level . "<br>";
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
            $this->deleteBy('id_user_forum', $idEntry);
        } else {
            $this->deleteBy('id_user_forum', $idEntry);
            foreach ($id_user_forum as $com) {
                $this->deleteBy('id_user_forum', $com);
            }
        }
    }

    protected function _getCommentHierachrie($id_cat, $id_art, $id_lang) {
        $query = "SELECT * FROM " . $this->cfg['tab']['phplib_auth_user_md5'];

        $this->db->query($query);
        $arrUsers = array();

        while ($this->db->next_record()) {
            $arrUsers[$this->db->f('user_id')]['email'] = $this->db->f('email');
            $arrUsers[$this->db->f('user_id')]['realname'] = $this->db->f('realname');
        }
        $arrforum = array();
        $this->getTreeLevel($id_cat, $id_art, $id_lang, $arrUsers, $arrforum);
        $result = array();
        $this->normalizeArray($arrforum, $result);

        return $result;
    }

    public function normalizeArray($arrforum, &$result, $level = 0) {
        if (is_array($arrforum)) {
            foreach ($arrforum as $key => $value) {
                $value['level'] = $level;
                unset($value['children']);
                $result[$key] = $value;
                $this->normalizeArray($arrforum[$key]['children'], $result, $level + 1);
            }
        }
    }

    public function getTreeLevel($id_cat, $id_art, $id_lang, &$arrUsers, &$arrforum, $parent = 0) {
        $query = "SELECT * FROM con_pi_user_forum WHERE (idart = $id_art) AND (idcat = $id_cat)
        AND (idlang = $id_lang) AND (id_user_forum_parent = $parent) ORDER BY timestamp DESC";

        $this->db->query($query);

        while ($this->db->next_record()) {
            $arrforum[$this->db->f('id_user_forum')]['userid'] = $this->db->f('userid');

            if (array_key_exists($this->db->f('userid'), $arrUsers)) {
                $arrforum[$this->db->f('id_user_forum')]['email'] = $arrUsers[$this->db->f('userid')]['email'];
                $arrforum[$this->db->f('id_user_forum')]['realname'] = $arrUsers[$this->db->f('userid')]['realname'];
            } else {
                $arrforum[$this->db->f('id_user_forum')]['email'] = $this->db->f('email');
                $arrforum[$this->db->f('id_user_forum')]['realname'] = $this->db->f('realname');
            }

            $arrforum[$this->db->f('id_user_forum')]['forum'] = str_replace(chr(13) . chr(10), '<br />', $this->db->f('forum'));
            $arrforum[$this->db->f('id_user_forum')]['forum_quote'] = str_replace(chr(13) . chr(10), '<br />', $this->db->f('forum_quote'));
            $arrforum[$this->db->f('id_user_forum')]['timestamp'] = $this->db->f('timestamp');
            $arrforum[$this->db->f('id_user_forum')]['like'] = $this->db->f('like');
            $arrforum[$this->db->f('id_user_forum')]['dislike'] = $this->db->f('dislike');

            $arrforum[$this->db->f('id_user_forum')]['editedat'] = $this->db->f('editedat');
            $arrforum[$this->db->f('id_user_forum')]['editedby'] = $this->db->f('editedby');

            // Added values to array for allocation
            $arrforum[$this->db->f('id_user_forum')]['idcat'] = $this->db->f('idcat');
            $arrforum[$this->db->f('id_user_forum')]['idart'] = $this->db->f('idart');
            $arrforum[$this->db->f('id_user_forum')]['id_user_forum'] = $this->db->f('id_user_forum');
            $arrforum[$this->db->f('id_user_forum')]['online'] = $this->db->f('online');
            $arrforum[$this->db->f('id_user_forum')]['editedat'] = $this->db->f('editedat');
            $arrforum[$this->db->f('id_user_forum')]['editedby'] = $this->db->f('editedby');

            $this->getTreeLevel($id_cat, $id_art, $id_lang, $arrUsers, $arrforum[$this->db->f('id_user_forum')]['children'], $this->db->f('id_user_forum'));
        }
    }

    public function updateValues($id_user_forum, $name, $email, $like, $dislike, $forum, $online, $checked) {
        // method receives checked as string, DB needs integer.
        ($checked === 'set_online')? $online = 1 : $online = 0;
        // check for negative inputs
        ($like >= 0)?  : $like = 0;
        ($dislike >= 0)?  : $dislike = 0;
        // actual user

        $uuid = cRegistry::getAuth()->isAuthenticated();
        $timeStamp = date('Y-m-d H:i:s', time());
        $sql = "UPDATE con_pi_user_forum SET `realname` = '$name' ,`editedby` = '$uuid'  , `email` = '$email' ,
            `forum` = '$forum' , `editedat` = '$timeStamp' , `like` = $like , `dislike` = $dislike ,
            `online` = $online  WHERE id_user_forum = $id_user_forum;";

        $this->db->query($sql);
    }

    /**
     * toggles the given input with update in db.
     *
     * @param $onlineState
     * @param primary key $id_user_forum
     */
    public function toggleOnlineState($onlineState, $id_user_forum) {
        ($onlineState == 0)? $onlineState = 1 : $onlineState = 0;
        $query = "UPDATE con_pi_user_forum SET online = $onlineState WHERE id_user_forum = $id_user_forum;";
        $this->db->query($query);
    }

    /**
     *
     * @param $id_cat
     * @param $id_art
     * @param $id_lang
     * @return ArticleForumRightBottom
     */
    public function getExistingforum($id_cat, $id_art, $id_lang) {
        $query = "SELECT * FROM " . $this->cfg['tab']['phplib_auth_user_md5'];
        $this->db->query($query);
        $arrUsers = array();

        while ($this->db->next_record()) {
            $arrUsers[$this->db->f('user_id')]['email'] = $this->db->f('email');
            $arrUsers[$this->db->f('user_id')]['realname'] = $this->db->f('realname');
        }

        return $arrUsers;
    }

    public function deleteAllCommentsById($idart) {
        $this->deleteBy('idart', $idart);
    }

}

?>