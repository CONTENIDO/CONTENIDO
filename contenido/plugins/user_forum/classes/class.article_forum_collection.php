<?php
/**
 * This file contains the collection class for userforum plugin.
 *
 * @package Plugin
 * @subpackage UserForum
 * @version SVN Revision $Rev:$
 *
 * @author Claus Schunk
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains functions for dB manipulations and for the interaction
 * between the frontend module
 * content_user_forum and the backend plugin.
 *
 * @package Plugin
 * @subpackage UserForum
 */
class ArticleForumCollection extends ItemCollection {

    protected $cfg = 0;

    protected $db = 0;

    protected $item = 0;
    // contents array of translations from frontend module
    protected $languageSync = 0;

    protected $idContentType = 0;

    public function __construct() {
        // sync time
        date_default_timezone_set('Europe/Berlin');
        $this->db = cRegistry::getDb();
        $this->cfg = cRegistry::getConfig();

        parent::__construct($this->cfg['tab']['user_forum'], 'id_user_forum');
        $this->_setItemClass('ArticleForum');
        $this->item = new ArticleForumItem();
        $this->idContentType = $this->getIdUserForumContenType();
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

    /**
     * deletes comment with all subcomments from this comment
     *
     * @param $keyPost
     * @param $level
     * @param $idart
     * @param $idcat
     * @param $lang
     */
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

    public function getTreeLevel($id_cat, $id_art, $id_lang, &$arrUsers, &$arrforum, $parent = 0, $frontend = false) {
        $db = cRegistry::getDb();

        if ($frontend) {
            // select only comments that are marked visible in frontendmode.
            $query = "SELECT * FROM con_pi_user_forum WHERE (idart = $id_art) AND (idcat = $id_cat)
            AND (idlang = $id_lang) AND (id_user_forum_parent = $parent) AND (online = 1) ORDER BY timestamp DESC";
        } else {
            // select all comments -> used in backendmode.
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
        $uuid = cRegistry::getAuth()->isAuthenticated();

        $this->item->loadByPrimaryKey($id_user_forum);

        if ($this->item->getField('realname') == $name && $this->item->getField('email') == $email && $this->item->getField('forum') == $forum) {

            // load timestamp from db to check if the article was already
            // edited.
            if ($this->item->getField('editedat') === "0000-00-00 00:00:00") {
                // case : never edited
                $timeStamp = "0000-00-00 00:00:00";
            } else {
                $timeStamp = $this->item->getField('editedat');
            }
        } else {
            // actual timestamp: Content was edited
            $timeStamp = date('Y-m-d H:i:s', time());
        }

        if (preg_match('/\D/', $like)) {
            $like = $this->item->getField('like');
        }

        if (preg_match('/\D/', $dislike)) {
            $dislike = $this->item->getField('dislike');
        }

        // check for negative inputs
        // does not work with php 5.2
        // (!preg_match('/\D/', $like))? : $like =
        // $this->item->getField('like');
        // (!preg_match('/\D/', $dislike))? : $dislike =
        // $this->item->getField('dislike');

        $fields = array(
            'realname' => cSecurity::escapeDB($name, $this->db),
            'editedby' => cSecurity::escapeDB($uuid, $this->db),
            'email' => cSecurity::escapeDB($email, $this->db),
            'forum' => $forum,
            'editedat' => cSecurity::escapeDB($timeStamp, $this->db),
            'like' => cSecurity::escapeDB($like, $this->db),
            'dislike' => cSecurity::escapeDB($dislike, $this->db),
            'online' => cSecurity::escapeDB($online, $this->db)
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
        // toggle state
        // ($onlineState == 0)? $onlineState = 1 : $onlineState = 0;
        if ($onlineState == 0) {
            $onlineState = 1;
        } else {
            $onlineState = 0;
        }

        $fields = array(
            'online' => cSecurity::escapeDB($onlineState, $this->db)
        );
        $whereClauses = array(
            'id_user_forum' => cSecurity::escapeDB($id_user_forum, $this->db)
        );
        $statement = $this->db->buildUpdate($this->table, $fields, $whereClauses);
        $this->db->query($statement);
    }

    /**
     * email notification for registred moderator.
     * before calling this function it is necessary to receive the converted
     * language string from frontend module.
     */
    public function mailToModerator($realname, $email, $forum, $idart, $lang, $forum_quote = 0) {

        // get article name
        $ar = $this->getArticleTitle($idart, $lang);

        $mail = new cMailer();
        $mail->setCharset('UTF-8');

        // build message content
        $message = $this->languageSync['NEWENTRYTEXT'] . " " . $this->languageSync['ARTICLE'] . $ar[0]["title"] . "\n" . "\n";
        $message .= $this->languageSync['USER'] . ' : ' . $realname . "\n";
        $message .= $this->languageSync['EMAIL'] . ' : ' . $email . "\n" . "\n";
        $message .= $this->languageSync['COMMENT'] . ' : ' . "\n" . $forum . "\n";
        if ($forum_quote != 0) {
            $message .= UserForum::i18n('QUOTE') . ' : ' . $forum_quote . "\n";
        }

        // send mail only if modEmail is set -> minimize traffic.
        if ($this->getModEmail($idart) != NULL) {
            $mail->sendMail(getEffectiveSetting("userforum", "mailfrom"), $this->getModEmail($idart), $this->languageSync['NEWENTRY'], $message);
        }
    }

    public function getArticleTitle($idart, $idlang) {
        $sql = "SELECT DISTINCT t.title FROM con_art_lang t WHERE t.idart=$idart AND t.idlang=$idlang;";
        $this->db->query($sql);
        $data = array();
        while ($this->db->next_record()) {
            array_push($data, $this->db->toArray());
        }
        return $data;
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
        $this->item->loadByPrimaryKey(cSecurity::escapeDB($idquote, $this->db));
        $ar[] = $this->item->get('realname');
        return $ar;
    }

    public function selectUser($userid) {
        return $this->item->loadByPrimaryKey(cSecurity::escapeDB($userid, $this->db));
    }

    /**
     * this function inkrements the actual value of likes from a comment and
     * persists it.
     *
     * @param $forum_user_id identifies a comment.
     */
    public function incrementLike($forum_user_id) {
        $db = cRegistry::getDb();
        $ar = array();
        // load actual value
        $this->item->loadByPrimaryKey(cSecurity::escapeDB($forum_user_id, $db));
        $ar = $this->item->toArray();
        $current = $ar['like'];
        // increment value
        $current += 1;

        $fields = array(
            'like' => $current
        );
        $whereClauses = array(
            'id_user_forum' => $forum_user_id
        );
        // persist inkremented value
        $statement = $this->db->buildUpdate($this->table, $fields, $whereClauses);
        $this->db->query($statement);
    }

    /**
     * this function inkrements the actual value of dislikes from a comment and
     * persists it.
     *
     * @param $forum_user_id identifies a comment.
     */
    public function incrementDislike($forum_user_id) {
        $db = cRegistry::getDb();
        $ar = array();
        // load actual value
        $this->item->loadByPrimaryKey(cSecurity::escapeDB($forum_user_id, $db));
        $ar = $this->item->toArray();
        $current = $ar['dislike'];
        // increment value
        $current += 1;

        $fields = array(
            'dislike' => $current
        );
        $whereClauses = array(
            'id_user_forum' => $forum_user_id
        );
        // persist inkremented value
        $statement = $this->db->buildUpdate($this->table, $fields, $whereClauses);
        $this->db->query($statement);
    }

    /**
     * persists a new comment created at the frontend module.
     *
     * @param $parent
     * @param $idart
     * @param $idcat
     * @param $lang
     * @param $userid
     * @param $email
     * @param $realname
     * @param $forum
     * @param $forum_quote
     */
    public function insertValues($parent, $idart, $idcat, $lang, $userid, $email, $realname, $forum, $forum_quote) {
        $db = cRegistry::getDb();

        // comments are marked as offline if the moderator mode is turned on.
        // ($modCheck = $this->getModeModeActive($idart))? $online = 0 : $online
        // = 1;

        if ($modCheck = $this->getModeModeActive($idart)) {
            $online = 0;
        } else {
            $online = 1;
        }
        // build array for sql statemant
        $fields = array(
            'id_user_forum' => NULL,
            'id_user_forum_parent' => cSecurity::escapeDB($parent, $db),
            'idart' => cSecurity::escapeDB($idart, $db),
            'idcat' => cSecurity::escapeDB($idcat, $db),
            'idlang' => cSecurity::escapeDB($lang, $db),
            'userid' => cSecurity::escapeDB($userid, $db),
            'email' => cSecurity::escapeDB($email, $db),
            'realname' => cSecurity::escapeDB($realname, $db),
            'forum' => ($forum),
            'forum_quote' => ($forum_quote),
            'like' => 0,
            'dislike' => 0,
            'editedat' => NULL,
            'editedby' => NULL,
            'timestamp' => date('Y-m-d H:i:s'),
            'online' => $online
        );

        $db->insert($this->table, $fields);

        // if moderator mode is turned on the moderator will receive an email
        // with the new comment and is able to
        // change the online state in the backend.
        if ($modCheck) {
            $this->mailToModerator($realname, $email, $forum, $idart, $lang, $forum_quote = 0);
        }
    }

    /**
     * this function deletes all comments related to the same articleId
     *
     * @param articleId $idart
     */
    public function deleteAllCommentsById($idart) {
        // var_dump($idart);
        $this->deleteBy('idart', cSecurity::escapeDB(($idart), $this->db));
    }

    public function getExistingforumFrontend($id_cat, $id_art, $id_lang, $frontend) {
        global $cfg;

        $db = cRegistry::getDb();

        $userColl = new cApiUserCollection();
        $userColl->query();

        while (($field = $userColl->next()) != false) {
            $arrUsers[$field->get('user_id')]['email'] = $field->get('email');
            $arrUsers[$field->get('user_id')]['realname'] = $field->get('realname');
        }

        $arrforum = array();
        $this->getTreeLevel($id_cat, $id_art, $id_lang, $arrUsers, $arrforum, 0, $frontend);

        $result = array();
        $this->normalizeArray($arrforum, $result);
        return $result;
    }

    /**
     * returns the emailadress from the moderator for this article
     *
     * @param articleid $idart
     * @return string
     */
    public function getModEmail($idart) {
        $data = $this->readXML();
        for ($i = 0; $i < count($data); $i++) {
            if ($data[$i]['idart'] == $idart) {
                return $data[$i]["email"];
            }
        }
        return NULL;
    }

    /**
     * returns if moderator mode is actice for this article
     *
     * @param articleid $idart
     * @return bool
     */
    public function getModeModeActive($idart) {
        $data = $this->readXML();
        for ($i = 0; $i < count($data); $i++) {
            if ($data[$i]['idart'] == $idart) {
                if ($data[$i]["modactive"] === 'false') {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * returns if quotes for comments are allowed in this article
     *
     * @param articleid $idart
     * @return bool
     */
    public function getQuoteState($idart) {
        // get content from con_type
        $data = $this->readXML();
        for ($i = 0; $i < count($data); $i++) {
            if ($data[$i]['idart'] == $idart) {
                if ($data[$i]["subcomments"] === 'false') {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * This function loads and returns the xml content from the contentType
     * aditionally the return array implies the articleId because of an easier
     * mapping in the frontend.
     *
     * @return array
     */
    public function readXML() {
        // get variables from global context
        $catId = cRegistry::getCategoryId();
        $idclient = cRegistry::getClientId();
        $cfgClient = cRegistry::getClientConfig();
        $idtype = $this->idContentType;

        $sql = "SELECT t.value,f.idart FROM con_art_lang f , con_content t WHERE idtype=$idtype AND t.idartlang=f.idartlang;";
        try {
            $this->db->query($sql);
            $data = array();
            $ar = array();
            while ($this->db->next_record()) {
                array_push($data, $this->db->toArray());
            }

            for ($i = 0; $i < count($data); $i++) {
                $ar[$i] = cXmlBase::xmlStringToArray($data[$i]['value']);
                // add articleId
                $ar[$i]['idart'] = $data[$i]['idart'];
            }
        } catch (Exception $e) {
            // var_dump(e);
        }
        return $ar;
    }

    /**
     * this function is used to get translations from the language of the
     * frontend module for example to generate
     * the e-mail text with correct language settings.
     *
     * @param array $str
     */
    public function languageSync(array &$str) {
        $this->languageSync = $str;
    }

    public function getlanguageSync() {
        if ($this->languageSync != 0) {
            return $this->languageSync;
        } else {
            return array();
        }
    }

    public function getCommentContent($id_user_forum) {
        $ar = array();
        $item = $this->loadItem($id_user_forum);
        $ar['name'] = $item->get("realname");
        $ar['content'] = $item->get("forum");

        return $ar;
    }

    protected function getIdUserForumContenType() {
        $sql = "SELECT idtype from con_type WHERE type='CMS_USERFORUM';";
        $result = $this->db->query($sql);
        if ($this->db->next_record()) {
            return $this->db->f('idtype');
        } else {
            return false;
        }
    }

}

?>