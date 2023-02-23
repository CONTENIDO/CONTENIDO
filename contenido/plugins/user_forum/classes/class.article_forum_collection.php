<?php
/**
 * This file contains the collection class for user_forum plugin.
 *
 * @package    Plugin
 * @subpackage UserForum
 * @author     Claus Schunk
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains functions for dB manipulations and for the interaction
 * between the frontend module
 * content_user_forum and the backend plugin.
 *
 * @package    Plugin
 * @subpackage UserForum
 * @method ArticleForum createNewItem
 * @method ArticleForum|bool next
 */
class ArticleForumCollection extends ItemCollection {

    /**
     * @var array
     */
    protected $cfg = null;

    /**
     * @var cDb
     */
    protected $db = null;

    /**
     * @var ArticleForumItem
     */
    protected $item = null;

    // contents array of translations from frontend module
    /**
     * @var array
     */
    protected $languageSync = null;

    /**
     * @var int
     */
    protected $idContentType = 0;

    /**
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        $this->db  = cRegistry::getDb();
        $this->cfg = cRegistry::getConfig();

        parent::__construct($this->cfg['tab']['user_forum'], 'id_user_forum');
        $this->_setItemClass('ArticleForum');
        $this->item          = new ArticleForumItem();
        $this->idContentType = $this->getIdUserForumContenType();
    }

    /**
     * @return array
     * @throws cDbException
     */
    public function getAllCommentedArticles() {
        $idclient = cRegistry::getClientId();

        $this->db->query("-- ArticleForumCollection->getAllCommentedArticles()
            SELECT DISTINCT
                art_lang.title
                , art_lang.idart
                , f.idcat
            FROM
                `{$this->cfg['tab']['art_lang']}` AS art_lang
                , `$this->table` AS  f
            WHERE
                art_lang.idart = f.idart
                AND art_lang.idlang = f.idlang
                AND idclient = " . $idclient . "
            ORDER BY
                id_user_forum ASC
            ;");

        $data = [];
        while ($this->db->nextRecord()) {
            $data[] = $this->db->toArray();
        }

        return $data;
    }

    /**
     * deletes comment with all sub-comments from this comment
     *
     * @param $keyPost
     * @param $level
     * @param $idArt
     * @param $idCat
     * @param $lang
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function deleteHierarchy($keyPost, $level, $idArt, $idCat, $lang) {
        $comments = $this->_getCommentHierarchy($idCat, $idArt, $lang);

        $arri = [];

        foreach ($comments as $key => $com) {
            $com['key'] = $key;
            $arri[]     = $com;
        }
        $idEntry     = 0;
        $idUserForum = [];
        for ($i = 0; $i < count($arri); $i++) {
            // select Entry
            if ($arri[$i]['key'] == $keyPost) {
                $idEntry = $arri[$i]['id_user_forum'];
                if ($arri[$i]['level'] < $arri[$i + 1]['level']) {
                    // check for more sub comments
                    for ($j = $i + 1; $j < $arri[$j]; $j++) {
                        if ($arri[$i]['level'] < $arri[$j]['level']) {
                            $idUserForum[] = $arri[$j]['id_user_forum'];
                        }
                    }
                }
            }
        }

        if (empty($idUserForum)) {
            $this->deleteBy('id_user_forum', $idEntry);
        } else {
            $this->deleteBy('id_user_forum', $idEntry);
            foreach ($idUserForum as $com) {
                $this->deleteBy('id_user_forum', $com);
            }
        }
    }

    /**
     * @param int $idCat
     * @param int $idArt
     * @param int $idLang
     *
     * @return array
     * @throws cDbException
     * @throws cException
     */
    protected function _getCommentHierarchy($idCat, $idArt, $idLang) {
        $this->query();
        while (false != $field = $this->next()) {
            $arrUsers[$field->get('userid')]['email']    = $field->get('email');
            $arrUsers[$field->get('userid')]['realname'] = $field->get('realname');
        }
        $arrForum = [];
        $this->getTreeLevel($idCat, $idArt, $idLang, $arrUsers, $arrForum);
        $result = [];
        $this->normalizeArray($arrForum, $result);

        return $result;
    }

    /**
     * @param array $arrForum
     * @param array $result
     * @param int   $level
     */
    public function normalizeArray($arrForum, &$result, $level = 0) {
        if (is_array($arrForum)) {
            foreach ($arrForum as $key => $value) {
                $value['level'] = $level;
                unset($value['children']);
                $result[$key] = $value;
                $this->normalizeArray($arrForum[$key]['children'], $result, $level + 1);
            }
        }
    }

    /**
     *
     * @param int   $idCat
     * @param int   $idArt
     * @param int   $idLang
     * @param array $arrUsers
     * @param array $arrForum
     * @param int   $parent
     * @param bool  $frontend
     *
     * @throws cDbException
     */
    public function getTreeLevel($idCat, $idArt, $idLang, &$arrUsers, &$arrForum, $parent = 0, $frontend = false) {
        $db = cRegistry::getDb();
        $idCat = cSecurity::toInteger($idCat);
        $idArt = cSecurity::toInteger($idArt);
        $idLang = cSecurity::toInteger($idLang);
        $parent = cSecurity::toInteger($parent);
        if ($frontend) {
            // select only comments that are marked visible in frontendmode.
            $db->query("-- ArticleForumCollection->getTreeLevel()
                SELECT
                    *
                FROM
                    `{$this->cfg['tab']['user_forum']}`
                WHERE
                    idart = $idArt
                    AND idcat = $idCat
                    AND idlang = $idLang
                    AND id_user_forum_parent = $parent
                    AND online = 1
                ORDER BY
                    timestamp DESC
                ;");
        } else {
            // select all comments -> used in backendmode.
            $db->query("-- ArticleForumCollection->getTreeLevel()
                SELECT
                    *
                FROM
                    `{$this->cfg['tab']['user_forum']}`
                WHERE
                    idart = $idArt
                    AND idcat = $idCat
                    AND idlang = $idLang
                    AND id_user_forum_parent = $parent
                ORDER BY
                    timestamp DESC
                ;");
        }

        while ($db->nextRecord()) {
            $record = $db->getRecord();
            $this->prepareRecord($record);

            if (array_key_exists($record['userid'], $arrUsers)) {
                $record['email']    = $arrUsers[$record['userid']]['email'];
                $record['realname'] = $arrUsers[$record['userid']]['realname'];
            }

            $arrForum[$db->f('id_user_forum')] = $record;

            $this->getTreeLevel($idCat, $idArt, $idLang, $arrUsers, $arrForum[$db->f('id_user_forum')]['children'], $db->f('id_user_forum'), $frontend);
        }
    }

    /**
     * @param int    $idUserForum
     * @param string $name
     * @param string $email
     * @param int    $like
     * @param int    $dislike
     * @param string $forum
     * @param int    $online
     *
     * @throws cDbException
     * @throws cException
     */
    public function updateValues($idUserForum, $name, $email, $like, $dislike, $forum, $online) {
        $uuid = cRegistry::getAuth()->isAuthenticated();

        $this->item->loadByPrimaryKey($idUserForum);

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
        // (!preg_match('/\D/', $like)) ? : $like =
        // $this->item->getField('like');
        // (!preg_match('/\D/', $dislike)) ? : $dislike =
        // $this->item->getField('dislike');

        $fields = [
            'realname'  => $name,
            'editedby'  => $uuid,
            'email'     => $email,
            'forum'     => $forum,
            'editedat'  => $timeStamp,
            'like'      => $like,
            'dislike'   => $dislike,
            'online'    => $online,
            // update moderated flag with update => comment is moderated now.
            'moderated' => 1
        ];

        $whereClauses = [
            'id_user_forum' => $idUserForum
        ];
        $statement = $this->db->buildUpdate($this->table, $fields, $whereClauses);
        $this->db->query($statement);
    }

    /**
     * toggles the given input with update in db.
     *
     * @param int $onlineState
     * @param int $idUserForum primary key
     * @param int|null $idArt article ID
     *
     * @throws cDbException
     */
    public function toggleOnlineState($onlineState, $idUserForum, $idArt = NULL) {
        // toggle state
        $onlineState = ($onlineState == 0) ? 1 : 0;

        if (isset($idArt)) {
            $fields = [
                'online'    => $onlineState,
                'moderated' => 1,
            ];
        } else {
            $fields = [
                'online' => $onlineState
            ];
        }

        $whereClauses = [
            'id_user_forum' => (int)$idUserForum
        ];
        $statement = $this->db->buildUpdate($this->table, $fields, $whereClauses);
        $this->db->query($statement);
    }

    /**
     * email notification for registered moderator.
     * before calling this function it is necessary to receive the converted
     * language string from frontend module.
     *
     * @param string $realName
     * @param string $email
     * @param string $forum
     * @param int    $idArt
     * @param int    $lang
     * @param int    $forumQuote
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function mailToModerator($realName, $email, $forum, $idArt, $lang, $forumQuote = 0) {
        // get article name
        $ar = $this->getArticleTitle($idArt, $lang);

        $mail = new cMailer();
        $mail->setCharset('UTF-8');

        // build message content
        $message = $this->languageSync['NEWENTRYTEXT'] . " " . $this->languageSync['ARTICLE'] . $ar[0]["title"] . "\n" . "\n";
        $message .= $this->languageSync['USER'] . ' : ' . $realName . "\n";
        $message .= $this->languageSync['EMAIL'] . ' : ' . $email . "\n" . "\n";
        $message .= $this->languageSync['COMMENT'] . ' : ' . "\n" . $forum . "\n";
        if ($forumQuote != 0) {
            $message .= UserForum::i18n('QUOTE') . ' : ' . $forumQuote . "\n";
        }

        // send mail only if modEmail is set -> minimize traffic.
        if ($this->getModEmail($idArt) != NULL) {
            $mail->sendMail(getEffectiveSetting("userforum", "mailfrom"), $this->getModEmail($idArt), $this->languageSync['NEWENTRY'], $message);
        }
    }

    /**
     * @param int $idArt
     * @param int $idLang
     *
     * @return array
     * @throws cDbException
     */
    public function getArticleTitle($idArt, $idLang) {
        $idArt = cSecurity::toInteger($idArt);
        $idLang = cSecurity::toInteger($idLang);
        $this->db->query("-- ArticleForumCollection->getArticleTitle()
            SELECT DISTINCT
                title
            FROM
                `{$this->cfg['tab']['art_lang']}` AS art_lang
            WHERE
                idart = $idArt
                AND idlang = $idLang
            ;");

        $data = [];
        while ($this->db->nextRecord()) {
            $data[] = $this->db->toArray();
        }

        return $data;
    }

    /**
     * @return array
     * @throws cDbException
     * @throws cException
     */
    public function getExistingforum() {
        $userColl = new cApiUserCollection();
        $userColl->query();

        $arrUsers = [];
        while (($field = $userColl->next()) != false) {
            $arrUsers[$field->get('user_id')]['email']    = $field->get('email');
            $arrUsers[$field->get('user_id')]['realname'] = $field->get('realname');
        }

        return $arrUsers;
    }

    /**
     * @param int $idUserForum
     *
     * @return array
     * @throws cDbException
     * @throws cException
     */
    public function selectNameAndNameByForumId($idUserForum) {
        $ar = [];
        $this->item->loadByPrimaryKey($this->db->escape($idUserForum));
        $ar[] = $this->item->get('realname');

        return $ar;
    }

    /**
     * @param $userId
     *
     * @return bool
     * @throws cDbException
     * @throws cException
     */
    public function selectUser($userId) {
        return $this->item->loadByPrimaryKey($this->db->escape($userId));
    }

    /**
     * this function increments the actual value of likes from a comment and
     * persists it.
     *
     * @param int $idUserForum identifies a comment
     *
     * @throws cDbException
     * @throws cException
     */
    public function incrementLike($idUserForum) {
        $db = cRegistry::getDb();
        // load actual value
        $this->item->loadByPrimaryKey($db->escape($idUserForum));
        $ar      = $this->item->toArray();
        $current = $ar['like'];
        // increment value
        $current += 1;

        $fields = [
            'like' => $current
        ];
        $whereClauses = [
            'id_user_forum' => $idUserForum
        ];
        // persist incremented value
        $statement = $this->db->buildUpdate($this->table, $fields, $whereClauses);
        $this->db->query($statement);
    }

    /**
     * this function increments the actual value of dislikes from a comment and
     * persists it.
     *
     * @param int $idUserForum identifies a comment
     *
     * @throws cDbException
     * @throws cException
     */
    public function incrementDislike($idUserForum) {
        $db = cRegistry::getDb();
        // load actual value
        $this->item->loadByPrimaryKey($db->escape($idUserForum));
        $ar      = $this->item->toArray();
        $current = $ar['dislike'];
        // increment value
        $current += 1;

        $fields = [
            'dislike' => $current
        ];
        $whereClauses = [
            'id_user_forum' => $idUserForum
        ];
        // persist incremented value
        $statement = $this->db->buildUpdate($this->table, $fields, $whereClauses);
        $this->db->query($statement);
    }

    /**
     * persists a new comment created at the frontend module.
     *
     * @param int    $parent
     * @param int    $idArt
     * @param int    $idCat
     * @param int    $lang
     * @param int    $userId
     * @param string $email
     * @param string $realName
     * @param string $forum
     * @param string $forumQuote
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function insertValues($parent, $idArt, $idCat, $lang, $userId, $email, $realName, $forum, $forumQuote) {
        $db = cRegistry::getDb();

        // comments are marked as offline if the moderator mode is turned on.
        $modCheck = $this->getModModeActive($idArt);
        $online   = $modCheck ? 0 : 1;

        // build array for sql statement
        $fields = [
            'id_user_forum'        => NULL,
            'id_user_forum_parent' => $db->escape($parent),
            'idart'                => $db->escape($idArt),
            'idcat'                => $db->escape($idCat),
            'idlang'               => $db->escape($lang),
            'userid'               => $db->escape($userId),
            'email'                => $db->escape($email),
            'realname'             => $db->escape($realName),
            'forum'                => ($forum),
            'forum_quote'          => ($forumQuote),
            'idclient'             => cRegistry::getClientId(),
            'like'                 => 0,
            'dislike'              => 0,
            'editedat'             => '',
            'editedby'             => '',
            'timestamp'            => date('Y-m-d H:i:s'),
            'online'               => $online
        ];

        $db->insert($this->table, $fields);

        // if moderator mode is turned on the moderator will receive an email
        // with the new comment and is able to
        // change the online state in the backend.
        if ($modCheck) {
            $this->mailToModerator($realName, $email, $forum, $idArt, $lang, $forumQuote = 0);
        }
    }

    /**
     * this function deletes all comments related to the same articleId
     *
     * @param int $idArt
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function deleteAllCommentsById($idArt) {
        $this->deleteBy('idart', (int)$idArt);
    }

    /**
     * @param int  $idCat
     * @param int  $idArt
     * @param int  $idLang
     * @param bool $frontend
     *
     * @return array
     * @throws cDbException
     * @throws cException
     */
    public function getExistingforumFrontend($idCat, $idArt, $idLang, $frontend) {
        $userColl = new cApiUserCollection();
        $userColl->query();

        while (($field = $userColl->next()) != false) {
            $arrUsers[$field->get('user_id')]['email']    = $field->get('email');
            $arrUsers[$field->get('user_id')]['realname'] = $field->get('realname');
        }

        $arrForum = [];
        $this->getTreeLevel($idCat, $idArt, $idLang, $arrUsers, $arrForum, 0, $frontend);

        $result = [];
        $this->normalizeArray($arrForum, $result);

        return $result;
    }

    /**
     * returns the email address from the moderator for this article
     *
     * @param int $idArt
     *
     * @return string|null
     */
    public function getModEmail($idArt) {
        $data = $this->readXML();
        for ($i = 0; $i < count($data); $i++) {
            if ($data[$i]['idart'] == $idArt) {
                return $data[$i]["email"];
            }
        }

        return NULL;
    }

    /**
     * returns if moderator mode is active for this article
     *
     * @param int $idArt
     *
     * @return bool
     */
    public function getModModeActive($idArt) {
        $data = $this->readXML();
        for ($i = 0; $i < count($data); $i++) {
            if ($data[$i]['idart'] == $idArt) {
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
     * @param int $idArt
     *
     * @return bool
     */
    public function getQuoteState($idArt) {
        // get content from con_type
        $data = $this->readXML();
        for ($i = 0; $i < count($data); $i++) {
            if ($data[$i]['idart'] == $idArt) {
                if ($data[$i]["subcomments"] === 'false') {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * This function loads and returns the xml content from the contentType
     * additionally the return array implies the articleId because of an easier
     * mapping in the frontend.
     *
     * @return array
     */
    public function readXML() {
        // get variables from global context
        $idtype = $this->idContentType;

        $array = [];

        try {
            $this->db->query("-- ArticleForumCollection->readXML()
                SELECT
                    art_lang.idart
                    , content.value
                FROM
                    `{$this->cfg['tab']['art_lang']}` AS art_lang
                    , `{$this->cfg['tab']['content']}` AS content
                WHERE
                    art_lang.idartlang = content.idartlang
                    AND content.idtype = $idtype
                ;");

            $data = [];
            while ($this->db->nextRecord()) {
                $data[] = $this->db->toArray();
            }

            $array = [];
            for ($i = 0; $i < count($data); $i++) {
                $array[$i] = cXmlBase::xmlStringToArray($data[$i]['value']);
                // add articleId
                $array[$i]['idart'] = $data[$i]['idart'];
            }
        } catch (Exception $e) {

        }

        return $array;
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

    /**
     * @return array
     */
    public function getLanguageSync() {
        if ($this->languageSync !== null) {
            return $this->languageSync;
        } else {
            return [];
        }
    }

    /**
     * @param int $idUserForum
     *
     * @return array
     * @throws cException
     */
    public function getCommentContent($idUserForum) {
        $item = $this->loadItem($idUserForum);

        return [
            'name'    => $item->get("realname") ?? '',
            'content' => $item->get("forum")
        ];
    }

    /**
     * @return int|boolean
     * @throws cDbException
     */
    protected function getIdUserForumContenType() {
        $this->db->query("-- ArticleForumCollection->getIdUserForumContenType()
            SELECT
                idtype
            FROM
                `{$this->cfg['tab']['type']}`
            WHERE
                type = 'CMS_USERFORUM'
            ;");
        if ($this->db->nextRecord()) {
            return $this->db->f('idtype');
        } else {
            return false;
        }
    }

    /**
     * @return array
     * @throws cDbException
     */
    public function getUnmoderatedComments() {
        $comments = [];

        $idLang   = cRegistry::getLanguageId();
        $idclient = cRegistry::getClientId();

        $db = cRegistry::getDb();
            $db->query("-- ArticleForumCollection->getUnmoderatedComments()
                SELECT
                    *
                FROM
                    `{$this->cfg['tab']['user_forum']}`
                WHERE
                    moderated = 0
                    AND idclient = $idclient
                    AND idlang = $idLang
                    AND online = 0
                ORDER BY
                    timestamp DESC
                ;");

        while ($db->nextRecord()) {
            // filter only mod mode active articles
            $modCheck = $this->getModModeActive($db->f('idart'));
            if (isset($modCheck)) {
                $record = $db->getRecord();
                $this->prepareRecord($record);
                $comments[] = $record;
            }
        }
        return $comments;
    }

    /**
     * Prepares the record, formats forum fields for output.
     * @param array $record
     */
    protected function prepareRecord(array &$record) {
        foreach (['forum', 'forum_quote'] as $field) {
            $record[$field] = nl2br($record[$field]);
        }
    }

}
