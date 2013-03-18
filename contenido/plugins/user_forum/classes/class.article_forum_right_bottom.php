<?php
global $area;
class ArticleForumRightBottom extends cGuiPage {

    private $_indentFactor = 20;

    function getResult() {
        return $this->_res;
    }

    function setInfentFactor($indentFactor) {
        $this->_indentFactor = $indentFactor;
    }

    function getIndentFactor() {
        return $this->_indentFactor;
    }

    function __construct() {
        parent::__construct('right_bottom', 'forumlist');
    }

    function getMaxLevel(&$forum_content) {
        $max = 0;

        foreach ($forum_content as $key => $content) {
            if ($content['level'] > $max) {
                $max = $content['level'];
            }
        }
        return $max;
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

    /**
     * form foreach
     *
     * @param $key
     * @param $cont
     *
     *
     * @param $cfg
     * @return array with buttons
     */
    function buildOnlineButtonBackendListMode(&$key, &$cont, &$cfg) {
        global $area;

        $buttons = array();

        $id = $cont['id_user_forum'];
        $online = new cHTMLLink();
        if ($cont['online'] == 1) {
            $online->setImage($cfg['path']['images'] . 'online.gif');
            $online->setCustom('action', 'online_toggle');
            $online->setAlt(UserForum::i18n('SETOFFLINE'));
        } else {
            $online->setImage($cfg['path']['images'] . 'offline.gif');
            $online->setCustom('action', 'offline_toggle');
            $online->setAlt(UserForum::i18n('SETONLINE'));
        }

        $online->setCLink($area, 4, 'show_form');
        $online->setStyle('margin-top:0px; margin-left:8px;');
        $online->setTargetFrame('right_bottom');
        $online->setCustom('action', 'online_toggle');
        $online->setCustom('idart', $cont['idart']);
        $online->setCustom('id_user_forum', $cont['id_user_forum']);
        $online->setCustom('idcat', $cont['idcat']);
        $online->setCustom('online', $cont['online']);
        $online->setAttribute('method', 'get');

        $edit = new cHTMLButton("edit");
        $edit->setImageSource($cfg['path']['images'] . 'but_todo.gif');
        $edit->setEvent('click', "$('form[name=$id]').submit()");
        $edit->setMode('image');
        $edit->setAlt(UserForum::i18n('EDIT'));

        $delete = new cHTMLLink();
        // $delete->setStyle('margin-right:10px;');
        $delete->setImage($cfg['path']['images'] . 'delete.gif');
        $delete->setAlt(UserForum::i18n('DELETE'));

        $delete->setCLink($area, 4, 'show_form');
        $delete->setTargetFrame('right_bottom');
        $delete->setCustom('action', 'deleteComment');
        $delete->setCustom('level', $cont['level']);
        $delete->setCustom('key', $key);
        $delete->setCustom('id_user_forum', $cont['id_user_forum']);
        $delete->setCustom('idcat', $cont['idcat']);
        $delete->setCustom('idart', $cont['idart']);

        $buttons['online'] = $online;
        $buttons['edit'] = $edit;
        $buttons['delete'] = $delete;

        return $buttons;
    }

    /**
     * generate main menu
     *
     * @param $result array with comments
     * @return ArticleForumRightBottom
     */
    function getMenu(&$result) {
        $table = new cHTMLTable();
        $table->setCellPadding('100px');
        global $area;
        $table->updateAttributes(array(
            "class" => "generic",
            "cellspacing" => "0",
            "cellpadding" => "2"
        ));

        $tr = new cHTMLTableRow();
        $th = new cHTMLTableHead();
        $th->setContent(i18n("FORUM_POST", "user_forum"));
        $tr->appendContent($th);

        $th = new cHTMLTableHead();
        $th->setContent(i18n("ACTIONS", "user_forum"));
        $th->setStyle('widht:20px');
        $th->setAttribute('valign', 'top');
        $tr->appendContent($th);

        $table->appendContent($tr);

        $menu = new cGuiMenu();
        $cfg = cRegistry::getConfig();

        /**
         *
         * @todo check if needed
         */
        // $testet = new cHTMLContentElement();
        // $testet->setID('Content');
        // $client = cRegistry::getClientId();
        // $lang = cRegistry::getLanguageId();
        // $nameTag = UserForum::i18n('USER');
        // $emailTag = UserForum::i18n('EMAIL');
        // $dateTag = UserForum::i18n('DATE');
        // $CommentTag = UserForum::i18n('COMMENT');

        // $likeTag = UserForum::i18n('LIKE');
        // $dislikeTag = UserForum::i18n('DISLIKE');

        foreach ($result as $key => $cont) {

            $set = false;
            $like = $cont['like'];
            $dislike = $cont['dislike'];
            $date = $cont['timestamp'];

            // build Buttons
            $id = $cont['id_user_forum'];
            $buttons = array();
            $buttons = $this->buildOnlineButtonBackendListMode($key, $cont, $cfg);

            $online = $buttons['online'];
            $edit = $buttons['edit'];
            $delete = $buttons['delete'];

            // row
            $tr = new cHTMLTableRow();

            $trLike = new cHTMLTableRow();

            $tdEmpty = new cHTMLTableData();
            $tdEmpty->appendContent("<br>");
            $tdLike = new cHTMLTableData();
            $tdEmpty->setAttribute('valign', 'top');
            $tdLike->setAttribute('valign', 'top');

            $likeButton = new cHTMLImage($cfg['path']['images'] . 'like.png');
            $dislikeButton = new cHTMLImage($cfg['path']['images'] . 'dislike.png');

            $tdLike->appendContent($likeButton);
            $tdLike->appendContent(" $like ");
            $tdLike->appendContent($dislikeButton);
            $tdLike->appendContent(" $dislike");

            // $tdLike->appendContent($likeTag . ": " . $like . "<br>");
            // $tdLike->appendContent($dislikeTag . ": " . $dislike);
            $trLike->appendContent($tdEmpty);
            $trLike->appendContent($tdLike);

            $form = new cHTMLForm($cont['id_user_forum']);
            $form->setAttribute('action', 'main.php?' . "area=" . $area . '&frame=4');

            $tdForm = new cHTMLTableData();
            $tdForm->setStyle('padding-left:' . $cont['level'] * $this->_indentFactor . 'px');

            $tdButtons = new cHTMLTableData();
            $tdButtons->setAttribute('valign', 'top');
            $tdButtons->appendContent($online);
            $tdButtons->appendContent($edit);
            $tdButtons->appendContent($delete);

            $maili = new cHTMLLink();
            $maili->setLink("mailto:" . $cont['email']);
            $maili->setContent($cont['realname']);

            $text = $cont['forum']; // n2bl

            // hidden-fields
            $hiddenIdart = new cHTMLHiddenField('idart');
            $hiddenIdart->setValue($cont['idart']);
            $hiddenIdcat = new cHTMLHiddenField('idcat');
            $hiddenIdcat->setValue($cont['idcat']);
            $hiddenId_user_forum = new cHTMLHiddenField('id_user_forum');
            $hiddenId_user_forum->setValue($cont['id_user_forum']);
            $hiddenLike = new cHTMLHiddenField('like');
            $hiddenLike->setValue($cont['like']);
            $hiddenDislike = new cHTMLHiddenField('dislike');
            $hiddenDislike->setValue($cont['dislike']);
            $hiddenName = new cHTMLHiddenField('realname');
            $hiddenName->setValue($cont['realname']);
            $hiddenEmail = new cHTMLHiddenField('email');
            $hiddenEmail->setValue($cont['email']);
            $hiddenLevel = new cHTMLHiddenField('level');
            $hiddenLevel->setValue($cont['level']);
            $hiddenEditdat = new cHTMLHiddenField('editedat');
            $hiddenEditdat->setValue($cont['editedat']);
            $hiddenEditedby = new cHTMLHiddenField('editedby');
            $hiddenEditedby->setValue($cont['editedby']);
            $hiddenTimestamp = new cHTMLHiddenField('timestamp');
            $hiddenTimestamp->setValue($cont['timestamp']);
            $hiddenForum = new cHTMLHiddenField('forum');
            $hiddenForum->setValue($cont['forum']);
            $hiddenOnline = new cHTMLHiddenField('online');
            $hiddenOnline->setValue($cont['online']);
            $hiddenMode = new cHTMLHiddenField('mode');
            $hiddenMode->setValue('edit');
            $hiddenKey = new cHTMLHiddenField('key');
            $hiddenKey->setValue($key);

            $form->appendContent($hiddenIdart);
            $form->appendContent($hiddenIdcat);
            $form->appendContent($hiddenId_user_forum);
            $form->appendContent($hiddenLike);
            $form->appendContent($hiddenDislike);
            $form->appendContent($hiddenName);
            $form->appendContent($hiddenEmail);
            $form->appendContent($hiddenLevel);
            $form->appendContent($hiddenForum);
            $form->appendContent($hiddenEditdat);
            $form->appendContent($hiddenEditedby);
            $form->appendContent($hiddenTimestamp);
            $form->appendContent($hiddenMode);
            $form->appendContent($hiddenOnline);
            $form->appendContent($hiddenKey);

            // generate output text
            $form->appendContent($maili . " schrieb am : " . $date . "<br><br>");
            $form->appendContent($text . "<br>");
            $tdForm->setContent($form);
            $tdForm->setAttribute('valign', 'top');
            $tr->setContent($tdForm);
            $tr->appendContent($tdButtons);
            $tr->appendContent($trLike);
            $table->appendContent($tr);
        }
        $this->appendContent($table);

        return $this;
    }

    /**
     * generate dialog for editmode
     *
     * @param unknown $post
     * @return ArticleForumRightBottom
     */
    function getEditModeMenu($post) {
        global $area;
        /**
         *
         * @todo check if needed
         */
        // $cfg = cRegistry::getConfig();
        // $client = cRegistry::getClientId();
        // $lang = cRegistry::getLanguageId();

        $menu = new cGuiMenu();
        $tr = new cHTMLTableRow();

        $th = new cHTMLTableHead();
        $th->setContent(UserForum::i18n("PARAMETER", "user_forum"));

        $th2 = new cHTMLTableHead();
        $th2->setContent(UserForum::i18n("CONTENT", "user_forum"));
        $th2->setStyle('widht:50px');
        $th2->setAttribute('valign', 'top');
        $tr->appendContent($th);
        $tr->appendContent($th2);

        $form1 = new cGuiTableForm("comment", "main.php?area=user_forum&frame=4", "post");
        $form1->addHeader($tr);

        // get User information
        $user = new cApiUser();
        $user->loadByPrimaryKey($post['editedby']);
        $username = $user->getField('username');

        // Dialog EDITMODE :
        $id = $post['id_user_forum'];

        $name = new cHTMLTextBox("realname", conHtmlSpecialChars($post['realname']), 40, 255);
        $email = new cHTMLTextBox("email", conHtmlSpecialChars($post['email']), 40, 255);
        $like = new cHTMLTextBox("like", conHtmlSpecialChars($post['like']), 40, 255);
        $dislike = new cHTMLTextBox("dislike", conHtmlSpecialChars($post['dislike']), 40, 255);
        $forum = new cHTMLTextArea("forum", conHtmlSpecialChars($post['forum']), 30, 10);
        $timestamp = new cHTMLTextBox("timestamp", conHtmlSpecialChars($post['timestamp']), 40, 255);
        $editedat = new cHTMLTextBox("editedat", conHtmlSpecialChars($post['editedat']), 40, 255);
        $editedby = new cHTMLTextBox("editedby", conHtmlSpecialChars($username), 40, 255);

        $editedat->setDisabled(true);
        $timestamp->setDisabled(true);
        $editedby->setDisabled(true);

        if ($post['online'] == 1) {
            $onlineBox = new cHTMLCheckbox("onlineState", 'set_offline');
            $onlineBox->setChecked(false);
            $form1->setVar("checked", "1");
        } else {
            $onlineBox = new cHTMLCheckbox("onlineState", 'set_online');
            $onlineBox->setChecked(false);
            $form1->setVar("checked", "0");
        }

        $idart = $post['idart'];
        $idcat = $post['idcat'];

        $form1->addCancel("main.php?area=user_forum&frame=4&action=back&idart=$idart&idcat=$idcat");
        $form1->add(UserForum::i18n("USER"), $name, '');
        $form1->add(UserForum::i18n("EMAIL"), $email, '');
        $form1->add(UserForum::i18n("LIKE"), $like, '');
        $form1->add(UserForum::i18n("DISLIKE"), $dislike, '');
        $form1->add(UserForum::i18n("TIME"), $timestamp, '');
        $form1->add(UserForum::i18n("EDITDAT"), $editedat, '');
        $form1->add(UserForum::i18n("EDITEDBY"), $editedby, '');
        $form1->add(UserForum::i18n("STATUS"), $onlineBox, '');
        $form1->add(UserForum::i18n("COMMENT"), $forum, '');
        // set hidden fields
        $form1->setVar("id_user_forum", $post['id_user_forum']);
        $form1->setVar("idart", $post['idart']);
        $form1->setVar("idcat", $post['idcat']);
        $form1->setVar("action", 'update');
        $form1->setVar("mode", "list");

        $this->appendContent($form1);

        return $this;
    }

    function deleteHierarchie($keyPost, $level, $idart, $idcat, $lang) {
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
        echo $idEntry . "<br>";
        print_r($id_user_forum);

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
            echo "DELETE ARRAY";
        }
    }

    function _getCommentHierachrie($id_cat, $id_art, $id_lang) {
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

        $arrforum = array();

        $this->getTreeLevel($id_cat, $id_art, $id_lang, $arrUsers, $arrforum);

        $result = array();
        $this->normalizeArray($arrforum, $result);
        $ret = $this->getMenu($result);

        return $ret;
    }

    function normalizeArray($arrforum, &$result, $level = 0) {
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
        // sleep(1);
    }

    /**
     * set updates to db.
     *
     * parameters from user editdialog:
     *
     * @param primary key $id_user_forum
     * @param $name
     * @param $email
     * @param $like
     * @param $dislike
     * @param $forum
     * @param $online
     * @param $checked
     */
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

    function receiveData() {
    }

}

?>