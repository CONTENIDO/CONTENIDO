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
 * @extends ItemCollection<ArticleForum>
 */
class ArticleForumCollection extends ItemCollection
{

    use cItemCollectionIdsByClientIdAndLanguageIdTrait;

    /**
     * @var string Client id foreign key field name
     * @since CONTENIDO 4.10.2
     */
    private $fkClientIdName = 'idclient';

    /**
     * @var string Language id foreign key field name
     * @since CONTENIDO 4.10.2
     */
    private $fkLanguageIdName = 'idlang';

    /**
     * @var array
     */
    protected array $cfg;

    /**
     * @var cDb
     */
    protected $db;

    /**
     * @var ArticleForumItem
     */
    protected ArticleForumItem $item;

    /**
     * array of translations from frontend module
     *
     * @var ?array
     */
    protected ?array $languageSync = null;

    /**
     * @var int
     */
    protected int $idContentType = 0;

    /**
     * @throws cDbException|cInvalidArgumentException|cException
     */
    public function __construct()
    {
        $this->db = cRegistry::getDb();
        $this->cfg = cRegistry::getConfig();

        parent::__construct(cDb::getTableName('user_forum'), 'id_user_forum');
        $this->_setItemClass('ArticleForum');
        $this->item = new ArticleForumItem();
        $this->idContentType = $this->getIdUserForumContentType();
    }

    /**
     * @throws cDbException
     */
    public function getAllCommentedArticles(): array
    {
        $clientId = cRegistry::getClientId();

        $tabArtLang = cDb::getTableName('art_lang');

        $this->db->query("-- ArticleForumCollection->getAllCommentedArticles()
            SELECT DISTINCT
                art_lang.title
                , art_lang.idart
                , f.idcat
            FROM
                `{$tabArtLang}` AS art_lang
                , `$this->table` AS  f
            WHERE
                art_lang.idart = f.idart
                AND art_lang.idlang = f.idlang
                AND idclient = $clientId
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
     * @param $articleId
     * @param $categoryId
     * @param $languageId
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function deleteHierarchy($keyPost, $level, $articleId, $categoryId, $languageId): void
    {
        $categoryId = cSecurity::toInteger($categoryId);
        $articleId = cSecurity::toInteger($articleId);
        $languageId = cSecurity::toInteger($languageId);

        $comments = $this->_getCommentHierarchy($categoryId, $articleId, $languageId);

        $arri = [];

        foreach ($comments as $key => $com) {
            $com['key'] = $key;
            $arri[] = $com;
        }
        $idEntry = 0;
        $userForumIds = [];
        for ($i = 0; $i < count($arri); $i++) {
            // select Entry
            if ($arri[$i]['key'] == $keyPost) {
                $idEntry = $arri[$i]['id_user_forum'];
                if ($arri[$i]['level'] < $arri[$i + 1]['level']) {
                    // check for more sub comments
                    for ($j = $i + 1; $j < $arri[$j]; $j++) {
                        if ($arri[$i]['level'] < $arri[$j]['level']) {
                            $userForumIds[] = $arri[$j]['id_user_forum'];
                        }
                    }
                }
            }
        }

        $this->deleteBy('id_user_forum', $idEntry);
        if (!empty($userForumIds)) {
            foreach ($userForumIds as $com) {
                $this->deleteBy('id_user_forum', $com);
            }
        }
    }

    /**
     * @throws cDbException|cException
     */
    protected function _getCommentHierarchy(int $categoryId, int $articleId, int $languageId): array
    {
        $this->query();
        while ($field = $this->next()) {
            $users[$field->get('userid')]['email'] = $field->get('email');
            $users[$field->get('userid')]['realname'] = $field->get('realname');
        }
        $forumStruct = [];
        $this->getTreeLevel($categoryId, $articleId, $languageId, $users, $forumStruct);
        $result = [];
        UserForum::normalizeArray($forumStruct, $result);

        return $result;
    }

    /**
     * @param mixed $forumStruct
     * @param array $result
     * @param int $level
     *
     * @deprecated [2026/05/12] since CONTENIDO 4.11.0, use {@see UserForum::normalizeArray()} instead!
     */
    public function normalizeArray(mixed $forumStruct, array &$result, int $level = 0): void
    {
        if (is_array($forumStruct)) {
            foreach ($forumStruct as $key => $value) {
                $value['level'] = $level;
                unset($value['children']);
                $result[$key] = $value;
                UserForum::normalizeArray($value['children'], $result, $level + 1);
            }
        }
    }

    /**
     * @param int $categoryId
     * @param int $articleId
     * @param int $languageId
     * @param array $users
     * @param array $forumStruct
     * @param int $parentUserForumId
     * @param bool $frontend
     * @throws cDbException
     */
    public function getTreeLevel(
        int   $categoryId,
        int   $articleId,
        int   $languageId,
        array &$users,
        array &$forumStruct,
        int   $parentUserForumId = 0,
        bool $frontend = false
    ): void
    {
        $db = cRegistry::getDb();
        $categoryId = cSecurity::toInteger($categoryId);
        $articleId = cSecurity::toInteger($articleId);
        $languageId = cSecurity::toInteger($languageId);
        $parentUserForumId = cSecurity::toInteger($parentUserForumId);
        if ($frontend) {
            // select only comments that are marked visible in frontendmode.
            $db->query("-- ArticleForumCollection->getTreeLevel()
                SELECT
                    *
                FROM
                    `{$this->table}`
                WHERE
                    idart = $articleId
                    AND idcat = $categoryId
                    AND idlang = $languageId
                    AND id_user_forum_parent = $parentUserForumId
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
                    `{$this->table}`
                WHERE
                    idart = $articleId
                    AND idcat = $categoryId
                    AND idlang = $languageId
                    AND id_user_forum_parent = $parentUserForumId
                ORDER BY
                    timestamp DESC
                ;");
        }

        while ($db->nextRecord()) {
            $record = $db->getRecord();
            $this->prepareRecord($record);

            if (array_key_exists($record['userid'], $users)) {
                $record['email'] = $users[$record['userid']]['email'];
                $record['realname'] = $users[$record['userid']]['realname'];
            }

            $forumStruct[$db->f('id_user_forum')] = $record;

            $this->getTreeLevel(
                $categoryId,
                $articleId,
                $languageId,
                $users,
                $forumStruct[$db->f('id_user_forum')]['children'],
                $db->f('id_user_forum'),
                $frontend
            );
        }
    }

    /**
     * @param int $userForumId
     * @param string $name
     * @param string $email
     * @param int $like
     * @param int $dislike
     * @param string $forum
     * @param int $online
     * @throws cDbException|cException
     */
    public function updateValues(
        int $userForumId,
        string $name,
        string $email,
        int $like,
        int $dislike,
        string $forum,
        int $online
    ): void
    {
        $uuid = cRegistry::getAuth()->isAuthenticated();

        $this->item->loadByPrimaryKey($userForumId);

        if (
            $this->item->getField('realname') == $name
            && $this->item->getField('email') == $email
            && $this->item->getField('forum') == $forum
        ) {
            // load timestamp from db to check if the article was already
            // edited.
            if ($this->item->getField('editedat') === '0000-00-00 00:00:00' || $this->item->getField('editedat') === NULL) {
                // case : never edited
                $timeStamp = date('Y-m-d H:i:s', time());
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
            'realname' => $name,
            'editedby' => $uuid,
            'email' => $email,
            'forum' => $forum,
            'editedat' => $timeStamp,
            'like' => $like,
            'dislike' => $dislike,
            'online' => $online,
            // update moderated flag with update => comment is moderated now.
            'moderated' => 1
        ];

        $whereClauses = [
            'id_user_forum' => $userForumId
        ];
        $statement = $this->db->buildUpdate($this->table, $fields, $whereClauses);
        $this->db->query($statement);
    }

    /**
     * toggles the given input with update in db.
     *
     * @param int $onlineState
     * @param int $userForumId primary key
     * @param int $articleId article ID
     * @throws cDbException
     */
    public function toggleOnlineState(int $onlineState, int $userForumId, int $articleId = 0): void
    {
        // toggle state
        $onlineState = $onlineState == 0 ? 1 : 0;

        if ($articleId > 0) {
            $fields = [
                'online' => $onlineState,
                'moderated' => 1,
            ];
        } else {
            $fields = [
                'online' => $onlineState
            ];
        }

        $whereClauses = [
            'id_user_forum' => $userForumId
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
     * @param int $articleId
     * @param int $languageId
     * @param int $forumQuote
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function mailToModerator(
        string $realName,
        string $email,
        string $forum,
        int $articleId,
        int $languageId,
        int $forumQuote = 0
    ): void
    {
        // get article name
        $ar = $this->getArticleTitle($articleId, $languageId);

        $mail = new cMailer();
        $mail->setCharset('UTF-8');

        // build message content
        $message = $this->languageSync['NEWENTRYTEXT'] . " " . $this->languageSync['ARTICLE'] . $ar[0]['title'] . "\n" . "\n";
        $message .= $this->languageSync['USER'] . ' : ' . $realName . "\n";
        $message .= $this->languageSync['EMAIL'] . ' : ' . $email . "\n" . "\n";
        $message .= $this->languageSync['COMMENT'] . ' : ' . "\n" . $forum . "\n";
        if ($forumQuote != 0) {
            $message .= UserForum::i18n('QUOTE') . ' : ' . $forumQuote . "\n";
        }

        // send mail only if modEmail is set -> minimize traffic.
        if ($this->getModEmail($articleId) != NULL) {
            $mail->sendMail(
                getEffectiveSetting('userforum', 'mailfrom'),
                $this->getModEmail($articleId), $this->languageSync['NEWENTRY'],
                $message
            );
        }
    }

    /**
     * @param int $articleId
     * @param int $languageId
     * @return array
     * @throws cDbException
     */
    public function getArticleTitle(int $articleId, int $languageId): array
    {
        $articleId = cSecurity::toInteger($articleId);
        $languageId = cSecurity::toInteger($languageId);
        $tabArtLang = cDb::getTableName('art_lang');

        $this->db->query("-- ArticleForumCollection->getArticleTitle()
            SELECT DISTINCT
                title
            FROM
                `{$tabArtLang}` AS art_lang
            WHERE
                idart = $articleId
                AND idlang = $languageId
            ;");

        $data = [];
        while ($this->db->nextRecord()) {
            $data[] = $this->db->toArray();
        }

        return $data;
    }

    /**
     * @throws cDbException|cException
     */
    public function getExistingForum(): array
    {
        $userColl = new cApiUserCollection();
        $userColl->query();

        $users = [];
        while ($field = $userColl->next()) {
            $users[$field->get('user_id')]['email'] = $field->get('email');
            $users[$field->get('user_id')]['realname'] = $field->get('realname');
        }

        return $users;
    }

    /**
     * @param int $userForumId
     * @throws cDbException|cException
     */
    public function selectNameAndNameByForumId(int $userForumId): array
    {
        $ar = [];
        $this->item->loadByPrimaryKey($this->db->escape($userForumId));
        $ar[] = $this->item->get('realname');

        return $ar;
    }

    /**
     * @param $userId
     * @throws cDbException|cException
     */
    public function selectUser($userId): bool
    {
        return $this->item->loadByPrimaryKey($this->db->escape($userId));
    }

    /**
     * this function increments the actual value of likes from a comment and persists it.
     *
     * @param int $userForumId identifies a comment
     * @throws cDbException|cException
     */
    public function incrementLike(int $userForumId): void
    {
        $db = cRegistry::getDb();
        // load actual value
        $this->item->loadByPrimaryKey($db->escape($userForumId));
        $ar = $this->item->toArray();
        $current = $ar['like'];
        // increment value
        $current += 1;

        $fields = [
            'like' => $current
        ];
        $whereClauses = [
            'id_user_forum' => $userForumId
        ];
        // persist incremented value
        $statement = $this->db->buildUpdate($this->table, $fields, $whereClauses);
        $this->db->query($statement);
    }

    /**
     * this function increments the actual value of dislikes from a comment and persists it.
     *
     * @param int $userForumId identifies a comment
     * @throws cDbException|cException
     */
    public function incrementDislike(int $userForumId): void
    {
        $db = cRegistry::getDb();
        // load actual value
        $this->item->loadByPrimaryKey($db->escape($userForumId));
        $ar = $this->item->toArray();
        $current = $ar['dislike'];
        // increment value
        $current += 1;

        $fields = [
            'dislike' => $current
        ];
        $whereClauses = [
            'id_user_forum' => $userForumId
        ];
        // persist incremented value
        $statement = $this->db->buildUpdate($this->table, $fields, $whereClauses);
        $this->db->query($statement);
    }

    /**
     * persists a new comment created at the frontend module.
     *
     * @param int $parentUserForumId
     * @param int $articleId
     * @param int $categoryId
     * @param int $languageId
     * @param int $userId
     * @param string $email
     * @param string $realName
     * @param string $forum
     * @param string $forumQuote
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function insertValues(
        int    $parentUserForumId,
        int    $articleId,
        int    $categoryId,
        int    $languageId,
        int    $userId,
        string $email,
        string $realName,
        string $forum,
        string $forumQuote
    ): void
    {
        $db = cRegistry::getDb();

        // comments are marked as offline if the moderator mode is turned on.
        $modCheck = $this->getModModeActive($articleId);
        $online = $modCheck ? 0 : 1;

        // build array for sql statement
        $fields = [
            'id_user_forum' => NULL,
            'id_user_forum_parent' => $db->escape($parentUserForumId),
            'idart' => $db->escape($articleId),
            'idcat' => $db->escape($categoryId),
            'idlang' => $db->escape($languageId),
            'userid' => $db->escape($userId),
            'email' => $db->escape($email),
            'realname' => $db->escape($realName),
            'forum' => ($forum),
            'forum_quote' => ($forumQuote),
            'idclient' => cRegistry::getClientId(),
            'like' => 0,
            'dislike' => 0,
            'editedat' => '',
            'editedby' => '',
            'timestamp' => date('Y-m-d H:i:s'),
            'online' => $online
        ];

        $db->insert($this->table, $fields);

        // if moderator mode is turned on the moderator will receive an email
        // with the new comment and is able to
        // change the online state in the backend.
        if ($modCheck) {
            $this->mailToModerator($realName, $email, $forum, $articleId, $languageId, $forumQuote = 0);
        }
    }

    /**
     * this function deletes all comments related to the same articleId
     *
     * @param int $articleId
     * @throws cDbException|cInvalidArgumentException
     */
    public function deleteAllCommentsById(int $articleId): void
    {
        $this->deleteBy('idart', cSecurity::toInteger($articleId));
    }

    /**
     * @param int $categoryId
     * @param int $articleId
     * @param int $languageId
     * @param bool $frontend
     * @throws cDbException|cException
     */
    public function getExistingForumFrontend(int $categoryId, int $articleId, int $languageId, bool $frontend): array
    {
        $userColl = new cApiUserCollection();
        $userColl->query();

        while ($field = $userColl->next()) {
            $users[$field->get('user_id')]['email'] = $field->get('email');
            $users[$field->get('user_id')]['realname'] = $field->get('realname');
        }

        $forumStruct = [];
        $this->getTreeLevel($categoryId, $articleId, $languageId, $users, $forumStruct, 0, $frontend);

        $result = [];
        UserForum::normalizeArray($forumStruct, $result);

        return $result;
    }

    /**
     * returns the email address from the moderator for this article
     *
     * @param int $articleId
     */
    public function getModEmail(int $articleId): ?string
    {
        $data = $this->readXML();
        for ($i = 0; $i < count($data); $i++) {
            if ($data[$i]['idart'] == $articleId) {
                return $data[$i]['email'];
            }
        }

        return null;
    }

    /**
     * returns if moderator mode is active for this article
     *
     * @param int $articleId
     */
    public function getModModeActive(int $articleId): bool
    {
        $data = $this->readXML();
        for ($i = 0; $i < count($data); $i++) {
            if ($data[$i]['idart'] == $articleId) {
                if ($data[$i]['modactive'] === 'false') {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * returns if quotes for comments are allowed in this article
     *
     * @param int $articleId
     */
    public function getQuoteState(int $articleId): bool
    {
        // get content from con_type
        $data = $this->readXML();
        for ($i = 0; $i < count($data); $i++) {
            if ($data[$i]['idart'] == $articleId) {
                if ($data[$i]['subcomments'] === 'false') {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * This function loads and returns the xml content from the contentType additionally the
     * return array implies the articleId because of an easier mapping in the frontend.
     *
     * @return string[]
     */
    public function readXML(): array
    {
        // get variables from global context
        $idtype = $this->idContentType;

        $array = [];

        $tabArtLang = cDb::getTableName('art_lang');
        $tabContent = cDb::getTableName('content');

        try {
            $this->db->query("-- ArticleForumCollection->readXML()
                SELECT
                    art_lang.idart
                    , content.value
                FROM
                    `{$tabArtLang}` AS art_lang
                    , `{$tabContent}` AS content
                WHERE
                    art_lang.idartlang = content.idartlang
                    AND content.idtype = $idtype
                ;");

            $data = [];
            while ($this->db->nextRecord()) {
                $data[] = $this->db->toArray();
            }

            for ($i = 0; $i < count($data); $i++) {
                $array[$i] = cXmlBase::xmlStringToArray($data[$i]['value']);
                // add articleId
                $array[$i]['idart'] = $data[$i]['idart'];
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
        }

        return $array;
    }

    /**
     * this function is used to get translations from the language of the frontend module
     * for example to generate the e-mail text with correct language settings.
     */
    public function languageSync(array &$str): void
    {
        $this->languageSync = $str;
    }

    public function getLanguageSync(): ?array
    {
        return $this->languageSync !== null ? $this->languageSync : [];
    }

    /**
     * @param int $userForumId
     * @return array{name: string, content: string}
     * @throws cException
     */
    public function getCommentContent(int $userForumId): array
    {
        $item = $this->loadItem($userForumId);

        return [
            'name' => $item->get('realname') ?? '',
            'content' => $item->get('forum')
        ];
    }

    /**
     * @return int|bool
     * @throws cDbException|cException
     */
    protected function getIdUserForumContentType(): bool|int
    {
        $type = new cApiType();
        if ($type->loadByType('CMS_USERFORUM')) {
            return cSecurity::toInteger($type->getId());
        } else {
            return false;
        }
    }

    /**
     * @throws cDbException
     */
    public function getUnmoderatedComments(): array
    {
        $comments = [];

        $languageId = cRegistry::getLanguageId();
        $clientId = cRegistry::getClientId();

        $db = cRegistry::getDb();
        $db->query("-- ArticleForumCollection->getUnmoderatedComments()
                SELECT
                    *
                FROM
                    `{$this->table}`
                WHERE
                    moderated = 0
                    AND idclient = $clientId
                    AND idlang = $languageId
                    AND online = 0
                ORDER BY
                    timestamp DESC
                ;");

        while ($db->nextRecord()) {
            // filter only mod mode active articles
            $modCheck = $this->getModModeActive($db->f('idart'));
            if ($modCheck) {
                $record = $db->getRecord();
                $this->prepareRecord($record);
                $comments[] = $record;
            }
        }
        return $comments;
    }

    /**
     * Prepares the record, formats forum fields for output.
     */
    protected function prepareRecord(array &$record): void
    {
        foreach (['forum', 'forum_quote'] as $field) {
            $record[$field] = nl2br($record[$field]);
        }
    }

}
