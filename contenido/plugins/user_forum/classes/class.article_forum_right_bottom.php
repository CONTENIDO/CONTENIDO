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
    private $_res = array();

    function getResult() {
        return $this->_res;
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
        $th->setContent(i18n("FORUM_ACTIONS", "user_forum"));
        $th->setStyle('widht:50px');
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

            // echo ($cont['online']);
            $online = new cHTMLButton("online");
            if ($cont['online'] == 1) {
                $online->setImageSource($cfg['path']['images'] . 'online.gif');
                $online->setAlt('online');
            } else {
                $online->setImageSource($cfg['path']['images'] . 'offline.gif');
                $online->setAlt('offline');
            }
            $online->setMode('image');
            $edit = new cHTMLButton("edit");
            $edit->setImageSource($cfg['path']['images'] . 'but_back.gif');
            $id = $cont['id_user_forum'];
            $edit->setEvent('click', "$('form[name=$id]').submit()");
            $edit->setMode('image');
            $edit->setAlt(UserForum::i18n('EDIT'));

  //          $save = new cHTMLButton("save");
  //          $save->setImageSource($cfg['path']['images'] . 'but_ok.gif');
  //          $id = $cont['id_user_forum'];
  //          $save->setEvent('click', "$('form[name=$id]').submit()");
  //          $save->setAlt(UserForum::i18n('SAVE'));
  //          $save->setMode('image');

            $delete = new cHTMLButton("save");
            $delete->setImageSource($cfg['path']['images'] . 'delete.gif');
            $id = $cont['id_user_forum'];
            $delete->setEvent('click', "$('form[name=$id]').submit()");
            $delete->setAlt(UserForum::i18n('DELETE'));
            $delete->setMode('image');

            // row
            $tr = new cHTMLTableRow();
            $form = new cHTMLForm($cont['id_user_forum']);
            $form->setAttribute('action', 'main.php?' . $area . '&frame=4');

            $tdForm = new cHTMLTableData();
            // $tdForm->setWidth(80);

            $tdForm->setStyle('padding-left:' . $cont['level'] * 20 . 'px');
            $tdButtons = new cHTMLTableData();
            $tdButtons->setStyle('padding-left:' . 400 . 'px');
            $tdButtons->appendContent($online);
            $tdButtons->appendContent($edit);
            $tdButtons->appendContent($delete);

            // TextFields
            // $user = new cHTMLTextbox('input' . $cont['id_user_forum']);
            // $user->setValue($cont['realname']);
            // $user->setWidth(30);
            // $user->setDisabled(true);
            $user = $cont['realname'];
            // $email = new cHTMLTextbox('input' . $cont['id_user_forum']);
            // $email->setValue($cont['email']);
            // $email->setWidth(30);
            // $email->setDisabled(true);
            $email = $cont['email'];
            // $text = new cHTMLTextarea('input' . $cont['id_user_forum']);
            // $text->setValue($cont['forum']);
            // $text->setWidth(40);
            // $text->setDisabled(true);
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
            // Hidden Fields
            // $form->appendContent("<hr>");
            $form->appendContent($nameTag . " : " . $user . " <br> " . $emailTag . " : " . $email);
            // $form->appendContent("<hr>");
            // $form->appendContent("email : " . $email);
            // $form->appendContent("<hr>");
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
        $menu = new cGuiMenu();
        $form1 = new cGuiTableForm("lang_properties");
        $form1->setVar("idlang", "sadhfs");
        // $form1->setVar("targetclient", $db->f("idclient"));
        $form1->setVar("action", "lang_edit");
        $form1->setVar("area", $area);

        $eselect = new cHTMLSelectElement("sencoding");
        $eselect->setStyle('width:255px');

        // Dialog EDITMODE :
        $id = $post['id_user_forum'];

        $name = new cHTMLTextBox("name.".$id, conHtmlSpecialChars($post['realname']), 40, 255);
        $email = new cHTMLTextBox("email.".$id, conHtmlSpecialChars($post['email']), 40, 255);
        $like = new cHTMLTextBox("like.".$id, conHtmlSpecialChars($post['like']), 40, 255);
        $dislike = new cHTMLTextBox("dislike.".$id, conHtmlSpecialChars($post['dislike']), 40, 255);
        $forum = new cHTMLTextArea("forum.".$id, conHtmlSpecialChars($post['forum']), 30, 10);
        $timestamp = new cHTMLTextBox("timestamp.".$id, conHtmlSpecialChars($post['timestamp']), 40, 255);
        $timestamp->setDisabled(true);
        $editedat = new cHTMLTextBox("editedat.".$id, conHtmlSpecialChars($post['editedat']), 40, 255);
        $editedat->setDisabled(true);
        $editedby = new cHTMLTextBox("editedby.".$id, conHtmlSpecialChars($post['editedby']), 40, 255);
        $editedby->setDisabled(true);

        $form1->add($name, UserForum::i18n("USER"));
        $form1->add($email, UserForum::i18n("EMAIL"));
        $form1->add($like, UserForum::i18n("LIKE"));
        $form1->add($dislike, UserForum::i18n("DISLIKE"));
        $form1->add($timestamp, UserForum::i18n("TIME"));
        $form1->add($editedat, UserForum::i18n("EDITDAT"));
        $form1->add($editedby, UserForum::i18n("EDITEDBY"));
        $form1->add($forum, UserForum::i18n("COMMENT"));
     //   echo $form1->render();
     //   return $form1;
        //$form1->add($eselect, "content");
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

    // getExistingforum($idcat, $idart, 1);
}
?>