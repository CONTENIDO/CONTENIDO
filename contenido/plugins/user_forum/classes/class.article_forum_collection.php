<?php
defined('CON_FRAMEWORK') or die('Illegal call');
class ArticleForumCollection extends ItemCollection {

    protected $cfg;

    protected $db;

    protected $item;

    public function __construct() {
        $this->db = cRegistry::getDb();
        $this->cfg = cRegistry::getConfig();

        parent::__construct($this->cfg['tab']['user_forum'], 'id_user_forum');
        $this->_setItemClass('ArticleForum');
        $this->item = new ArticleForumItem();
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
        $this->query();
        while (false != $field = $this->next()) {
            $arrUsers[$field->get('userid')]['email'] = $field->get('email');
            $arrUsers[$field->get('userid')]['realname'] = $field->get('realname');
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

    /**
     *
     * @todo
     *
     *
     */
    public function getTreeLevel($id_cat, $id_art, $id_lang, &$arrUsers, &$arrforum, $parent = 0, $frontend = false) {
        $db = cRegistry::getDb();

        if ($frontend) {
            $query = "SELECT * FROM con_pi_user_forum WHERE (idart = $id_art) AND (idcat = $id_cat)
            AND (idlang = $id_lang) AND (id_user_forum_parent = $parent) AND (online = 1) ORDER BY timestamp DESC";
        } else {
            $query = "SELECT * FROM con_pi_user_forum WHERE (idart = $id_art) AND (idcat = $id_cat)
        AND (idlang = $id_lang) AND (id_user_forum_parent = $parent) ORDER BY timestamp DESC";
        }
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

            $this->getTreeLevel($id_cat, $id_art, $id_lang, $arrUsers, $arrforum[$db->f('id_user_forum')]['children'], $db->f('id_user_forum'), $frontend);
        }
    }

    public function updateValues($id_user_forum, $name, $email, $like, $dislike, $forum, $online, $checked) {
        // method receives checked as string, DB needs integer.
        if (isset($checked)) {
            ($checked === 'set_online')? $online = 1 : $online = 0;
        }

        $uuid = cRegistry::getAuth()->isAuthenticated();

        $this->item->loadByPrimaryKey($id_user_forum);

        if ($this->item->getField('realname') == $name && $this->item->getField('email') == $email && $this->item->getField('forum') == $forum) {

            if ($this->item->getField('editedat') === "0000-00-00 00:00:00") {
                $timeStamp = "0000-00-00 00:00:00";
            } else {
                $timeStamp = $this->item->getField('editedat');
            }
        } else {
            $timeStamp = date('Y-m-d H:i:s', time());
        }

        // check for negative inputs
        (!preg_match('/\D/', $like))?  : $like = $this->item->getField('like');
        (!preg_match('/\D/', $dislike))?  : $dislike = $this->item->getField('dislike');

        $fields = array(
            'realname' => mysql_real_escape_string($name),
            'editedby' => mysql_real_escape_string($uuid),
            'email' => mysql_real_escape_string($email),
            'forum' => mysql_real_escape_string($forum),
            'editedat' => mysql_real_escape_string($timeStamp),
            'like' => mysql_real_escape_string($like),
            'dislike' => mysql_real_escape_string($dislike),
            'online' => mysql_real_escape_string($online)
        );

        $whereClauses = array(
            'id_user_forum' => $id_user_forum
        );
        $statement = $this->db->buildUpdate($this->table, $fields, $whereClauses);
        $this->db->query($statement);
    }

    /**
     * toggles the given input with update in db.
     *
     * @param $onlineState
     * @param primary key $id_user_forum
     */
    public function toggleOnlineState($onlineState, $id_user_forum) {
        ($onlineState == 0)? $onlineState = 1 : $onlineState = 0;
        $fields = array(
            'online' => mysql_real_escape_string($onlineState)
        );
        $whereClauses = array(
            'id_user_forum' => mysql_real_escape_string($id_user_forum)
        );
        $statement = $this->db->buildUpdate($this->table, $fields, $whereClauses);
        $this->db->query($statement);
    }

    /**
     *
     * @param $id_cat
     * @param $id_art
     * @param $id_lang
     * @return ArticleForumRightBottom
     *
     */
    public function getExistingforum($id_cat, $id_art, $id_lang) {
        $userColl = new cApiUserCollection();
        $userColl->query();

        while (($field = $userColl->next()) != false) {

            $arrUsers[$field->get('user_id')]['email'] = $field->get('email');
            $arrUsers[$field->get('user_id')]['realname'] = $field->get('realname');
        }
        return $arrUsers;
    }

    function selectNameAndNameByForumId($idquote) {
        $ar = array();
        $this->item->loadByPrimaryKey(mysql_real_escape_string($idquote));
        $ar[] = $this->item->get('realname');
        return $ar;
    }

    public function selectUser($userid) {
        return $this->item->loadByPrimaryKey(mysql_real_escape_string($userid));
    }

    public function incrementLike($forum_user_id) {
        $db = cRegistry::getDb();
        $ar = array();
        $this->item->loadByPrimaryKey(mysql_real_escape_string($forum_user_id));
        $ar = $this->item->toArray();
        $current = $ar['like'];
        $current += 1;

        $fields = array(
            'like' => $current
        );
        $whereClauses = array(
            'id_user_forum' => $forum_user_id
        );

        $statement = $this->db->buildUpdate($this->table, $fields, $whereClauses);
        $this->db->query($statement);
    }

    public function incrementDislike($forum_user_id) {
        $db = cRegistry::getDb();
        $ar = array();
        $this->item->loadByPrimaryKey(mysql_real_escape_string($forum_user_id));
        $ar = $this->item->toArray();
        $current = $ar['dislike'];
        $current += 1;

        $fields = array(
            'dislike' => $current
        );
        $whereClauses = array(
            'id_user_forum' => $forum_user_id
        );

        $statement = $this->db->buildUpdate($this->table, $fields, $whereClauses);
        $this->db->query($statement);
    }

    public function insertValues($parent, $idart, $idcat, $lang, $userid, $email, $realname, $forum, $forum_quote) {
        $db = cRegistry::getDb();

        $fields = array(
            'id_user_forum' => NULL,
            'id_user_forum_parent' => mysql_real_escape_string($parent),
            'idart' => mysql_real_escape_string($idart),
            'idcat' => mysql_real_escape_string($idcat),
            'idlang' => mysql_real_escape_string($lang),
            'userid' => mysql_real_escape_string($userid),
            'email' => mysql_real_escape_string($email),
            'realname' => mysql_real_escape_string($realname),
            'forum' => mysql_real_escape_string($forum),
            'forum_quote' => mysql_real_escape_string($forum_quote),
            'like' => 0,
            'dislike' => 0,
            'editedat' => NULL,
            'editedby' => NULL,
            'timestamp' => date("Y-m-d H:i:s"),
            'online' => 1
        );

        $db->insert($this->table, $fields);
    }

    public function deleteAllCommentsById($idart) {
        $this->deleteBy('idart', mysql_real_escape_string(($idart)));
    }

    public function getExistingforumFrontend($id_cat, $id_art, $id_lang) {
        global $cfg;

        $db = cRegistry::getDb();

        $userColl = new cApiUserCollection();
        $userColl->query();

        while (($field = $userColl->next()) != false) {

            $arrUsers[$field->get('user_id')]['email'] = $field->get('email');
            $arrUsers[$field->get('user_id')]['realname'] = $field->get('realname');
        }

        $arrforum = array();

        $this->getTreeLevel($id_cat, $id_art, $id_lang, $arrUsers, $arrforum);

        $result = array();
        $this->normalizeArray($arrforum, $result);
        return $result;
    }

}
class ArticleForumItem extends Item {

    protected $cfg;

    protected $db;

    public function __construct() {
        $this->db = cRegistry::getDb();
        $this->cfg = cRegistry::getConfig();

        parent::__construct($this->cfg['tab']['user_forum'], 'id_user_forum');
    }

    public function getCfg() {
        return $this->cfg;
    }

}

?>