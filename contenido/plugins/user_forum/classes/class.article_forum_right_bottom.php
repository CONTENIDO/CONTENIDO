<?php
global $area;
class ArticleForumRightBottom extends cGuiPage {

    // von Timo ...
    // $form = new cGuiTableForm("lang_properties");
    // $form->setVar("idlang", $idlang);
    // $form->setVar("targetclient", $db->f("idclient"));
    // $form->setVar("action", "lang_edit");
    // $form->setVar("area", $area);
    // $form->setVar("frame", $frame);
    // [15:46:56] Timo Trautmann: $eselect = new
    // cHTMLSelectElement("sencoding");
    // $eselect->setStyle('width:255px');
    // $eselect->autoFill($charsets);
    // $eselect->setDefault($db->f("encoding"));
    // [15:47:07] Timo Trautmann: $form->add(i18n("Encoding"), $eselect);
    // [15:47:13] Timo Trautmann: $oTxtLang = new cHTMLTextBox("langname",
    // conHtmlSpecialChars($db->f("name")), 40, 255);
    // [15:47:21] Timo Trautmann: $page->setContent($form);
    // private $_res = array();
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

        $query = "SELECT * FROM con_pi_user_forum WHERE (idart = $id_art) AND (idcat = $id_cat) AND (idlang = $id_lang) AND (id_user_forum_parent = $parent) ORDER BY timestamp DESC";

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
            //
            $this->getTreeLevel($id_cat, $id_art, $id_lang, $arrUsers, $arrforum[$db->f('id_user_forum')]['children'], $db->f('id_user_forum'));
        }
    }

    function getMenu(&$result) {
        $maxWidth = $this->getMaxLevel($result);
        $maxHeight = count($result);

        $testet = new cHTMLContentElement();
        $testet->setID("Content");

        $table = new cHTMLTable();
        $table->setCellPadding("100px");
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
        $client = cRegistry::getClientId();
        $lang = cRegistry::getLanguageId();

        $nameTag = UserForum::i18n('USER');
        $emailTag = UserForum::i18n('EMAIL');
        $likeTag = UserForum::i18n('LIKE');
        $dislikeTag = UserForum::i18n('DISLIKE');
        $dateTag = UserForum::i18n('DATE');
        $CommentTag = UserForum::i18n('COMMENT');

        // table
        foreach ($result as $key => $cont) {
            $set = false;

            $like = $cont['like'];
            $dislike = $cont['dislike'];
            $date = $cont['timestamp'];

            // build Buttons
            $id = $cont['id_user_forum'];
            // echo ($cont['online']);

            $online = new cHTMLLink();
            if ($cont['online'] == 1) {
                $online->setImage($cfg['path']['images'] . 'online.gif');
                $online->setCustom('action', 'online_toggle');
            } else {
                $online->setImage($cfg['path']['images'] . 'offline.gif');
                $online->setCustom('action', 'offline_toggle');
            }
            $online->setCLink($area, 4, 'show_form');

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

            $delete = new cHTMLButton("save");
            $delete->setImageSource($cfg['path']['images'] . 'delete.gif');
            $id = $cont['id_user_forum'];
            $delete->setEvent('click', "$('form[name=$id]').submit()");
            $delete->setAlt(UserForum::i18n('DELETE'));
            $delete->setMode('image');

            // row
            $tr = new cHTMLTableRow();
            $form = new cHTMLForm($cont['id_user_forum']);
            $form->setAttribute('action', 'main.php?' . "area=" . $area . '&frame=4');

            $tdForm = new cHTMLTableData();

            $tdForm->setStyle('padding-left:' . $cont['level'] * $this->_indentFactor . 'px');
            $tdButtons = new cHTMLTableData();
            $tdButtons->setStyle('padding-bottom:130px');
            // $tdButtons->setStyle('padding-left:' . 5 . 'px',);
            $tdButtons->appendContent($online);
            $tdButtons->appendContent($edit);
            $tdButtons->appendContent($delete);

            $user = $cont['realname'];
            $email = $cont['email'];
            $text = nl2br($CommentTag . " : <br> " . $cont['forum']);

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
            $form->appendContent($nameTag . " : " . $user . " <br> " . $emailTag . " : " . $email);
            $form->appendContent("<br> " . $dateTag . ": " . $date . " <br> " . $likeTag . ": " . $like . " " . $dislikeTag . ": " . $dislike);
            $form->appendContent("<hr>");
            $form->appendContent($text);
            $form->appendContent("<hr>");
            $form->appendContent("<br><br>");

            $tdForm->setContent($form);
            $tdForm->setAttribute('valign', 'top');
            $tr->setContent($tdForm);
            $tr->appendContent($tdButtons);
            $table->appendContent($tr);
        }

        $this->appendContent($table);

        return $this;
    }

    function getEditModeMenu($post) {
        global $area;
        $cfg = cRegistry::getConfig();
        $client = cRegistry::getClientId();
        $lang = cRegistry::getLanguageId();

        $menu = new cGuiMenu();

        // $table = new cHTMLTable();

        $tr = new cHTMLTableRow();

        $th = new cHTMLTableHead();
        $th->setContent(UserForum::i18n("PARAMETER", "user_forum"));
        $tr->appendContent($th);

        $th = new cHTMLTableHead();
        $th->setContent(UserForum::i18n("CONTENT", "user_forum"));
        $th->setStyle('widht:50px');
        $th->setAttribute('valign', 'top');
        $tr->appendContent($th);

        $form1 = new cGuiTableForm("comment", "main.php?area=user_forum&frame=4", "post");
        $form1->addHeader($tr);
        // Dialog EDITMODE :
        $id = $post['id_user_forum'];

        $name = new cHTMLTextBox("realname", conHtmlSpecialChars($post['realname']), 40, 255);
        $email = new cHTMLTextBox("email", conHtmlSpecialChars($post['email']), 40, 255);
        $like = new cHTMLTextBox("like", conHtmlSpecialChars($post['like']), 40, 255);
        $dislike = new cHTMLTextBox("dislike", conHtmlSpecialChars($post['dislike']), 40, 255);
        $forum = new cHTMLTextArea("forum", conHtmlSpecialChars($post['forum']), 30, 10);
        $timestamp = new cHTMLTextBox("timestamp", conHtmlSpecialChars($post['timestamp']), 40, 255);
        $timestamp->setDisabled(true);
        $editedat = new cHTMLTextBox("editedat", conHtmlSpecialChars($post['editedat']), 40, 255);
        $editedat->setDisabled(true);
        $editedby = new cHTMLTextBox("editedby", conHtmlSpecialChars($post['editedby']), 40, 255);
        $editedby->setDisabled(true);

        if ($post['online'] == 1) {
            $onlineBox = new cHTMLCheckbox("onlineState", 'online');
            $onlineBox->setChecked(true);
        } else {
            $onlineBox = new cHTMLCheckbox("onlineState", 'offline');
            $onlineBox->setChecked(false);
        }

        // /////////////////////////////
        // $id = $post['id_user_forum'];
        // $online = new cHTMLLink();

        $hidden = new cHTMLHiddenField("test");
        $hidden->setValue("blabla");
        // $online->setCLink($area, 4, 'show_form');
        // $online->setTargetFrame('right_bottom');
        // $online->setTargetFrame('right_bottom');
        // $online->setCustom('action', 'online_toggle');
        // $online->setCustom('idart', $post['idart']);
        // $online->setCustom('id_user_forum', $post['id_user_forum']);
        // $online->setCustom('idcat', $post['idcat']);
        // $online->setCustom('online', $post['online']);
        // $online->setCustom('realname', $post['realname']);
        // $online->setAttribute('method', 'POST');
        // if ($post['online'] == 1) {
        // $online->setImage($cfg['path']['images'] . 'online.gif');
        // $online->setCustom('action', 'online_toggle');
        // // $form1->setActionButton("test", $cfg['path']['images'] .
        // // 'online.gif');
        // } else {
        // $online->setImage($cfg['path']['images'] . 'offline.gif');
        // $online->setCustom('action', 'online_toggle');
        // // $form1->setActionButton("test", $cfg['path']['images'] .
        // // 'offline.gif');
        // }

        // $td = new cHTMLTableData();
        // $td->appendContent($online);
        // $this->appendContent($td);

        // $idart = $post['idart'];

        // $idcat = $post['idart'];
        // $idart = $post['idcat'];

        // $hid = new cHTMLHiddenField('id_user_forum');
        // $hid->setValue($post['id_user_forum']);

        $form1->addCancel("main.php?area=user_forum&frame=4&action=show_form");
        // $form1->add($hid,'');
        $form1->add(UserForum::i18n("USER"), $name, '');
        $form1->add(UserForum::i18n("EMAIL"), $email, '');
        $form1->add(UserForum::i18n("LIKE"), $like, '');
        $form1->add(UserForum::i18n("DISLIKE"), $dislike, '');
        $form1->add(UserForum::i18n("TIME"), $timestamp, '');
        $form1->add(UserForum::i18n("EDITDAT"), $editedat, '');
        $form1->add(UserForum::i18n("EDITEDBY"), $editedby, '');
        $form1->add(UserForum::i18n("STATUS"), $onlineBox, '');
        // $form1->add(UserForum::i18n("IDCAT"), $idcat, '');
        // $form1->add(UserForum::i18n("IDART"), $idart, '');
        // $form1->add(UserForum::i18n("ID_USER_FORUM"), $id_user_forum, '');
        $form1->setVar("id_user_forum", $post['id_user_forum']);
        $form1->setVar("idart", $post['idart']);
        $form1->setVar("idcat", $post['idcat']);
        $form1->setVar("action", 'update');
        $form1->setVar("mode", "list");

        // $form1->setVar("onlineState", $post['online']);
        // $form1->add(UserForum::i18n("ID_USER_FORUM"), $hidden, '');

        $form1->add(UserForum::i18n("COMMENT"), $forum, '');

        // $this->appendContent($hid);
        $this->appendContent($form1);

        return $this;
    }

    /**
     *
     * @param unknown $id_cat
     * @param unknown $id_art
     * @param unknown $id_lang
     * @return ArticleForumRightBottom
     */
    function getExistingforum($id_cat, $id_art, $id_lang) {
        global $cfg;

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
        // $this->_res = $result;
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

    public function toggleOnlineState($onlineState, $id_user_forum) {
        global $cfg;

        ($onlineState == 0)? $onlineState = 1 : $onlineState = 0;

        $db = cRegistry::getDb();

        $query = "UPDATE con_pi_user_forum SET online = $onlineState WHERE id_user_forum = $id_user_forum;";

        $db->query($query);
    }

    public function updateValues($id_user_forum, $name, $email, $like, $dislike, $forum, $online) {
        ($online === 'online')? $online = 1 : $online = 0;
        $like = 5;
        $cfg = cRegistry::getConfig();
        $db = cRegistry::getDb();
        $sql = "UPDATE con_pi_user_forum SET `realname` = '$name' , `email` = '$email' , `forum` = '$forum' , `like` = $like , `dislike` = $dislike , `online` = $online  WHERE id_user_forum = 26;";
        //, email = $email, like = $like, dislike = $dislike forum = $forum online = $online
        $db->query($sql);
    }

    // getExistingforum($idcat, $idart, 1);
}
?>