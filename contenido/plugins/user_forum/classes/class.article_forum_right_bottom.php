<?php
/**
 * This file contains the class for visualisation and interactions in the right frame.
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
 * This class contains builds the content of the right frame.
 *
 *
 * @package Plugin
 * @subpackage UserForum
 */

global $area;
class ArticleForumRightBottom extends cGuiPage {

    private $_indentFactor = 20;

    protected $_collection;

    function __construct() {

//reload left bottom in this code because of irregular update problems in the location file
echo <<<EOF
<script type="text/javascript">
    reloadLeftBottom = 'main.php?area=user_forum';
    reloadLeftBottom += '&frame=2';
    parent.parent.left.left_bottom.location.href = reloadLeftBottom;
</script>
EOF;
        $this->_collection = new ArticleForumCollection();
        parent::__construct('right_bottom', 'forumlist');
        $this->addStyle('right_bottom.css');
        $this->addScript('location.js');
    }

    protected function formatTimeString($timeStamp) {
        $nullstring = '0';
        if ($timeStamp == "0000-00-00 00:00:00") {
            return array();
        } else {
            $ar = (date_parse($timeStamp));
            // if elements are smaller than 2 digits add a '0' at front. e.g
            // 2:10 -> 02:10
            (strlen($ar['day']) < 2)? $ar['day'] = $nullstring . $ar['day'] : '';
            (strlen($ar['month']) < 2)? $ar['month'] = $nullstring . $ar['month'] : '';
            (strlen($ar['minute']) < 2)? $ar['minute'] = $nullstring . $ar['minute'] : '';
            (strlen($ar['hour']) < 2)? $ar['hour'] = $nullstring . $ar['hour'] : '';
        }

        return $ar;
    }

    /**
     * this function returns an deactive link or an link with mail-to directive
     * if the given mail adress is valid
     *
     * @param string $emailadr
     * @param string $realname
     * @return cHTMLLink
     */
    protected function checkValidEmail($emailadr, $realname) {
        $regex = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/';
        // Run the preg_match() function on regex against the email address
        if (preg_match($regex, $emailadr)) {
            $mail = new cHTMLLink();
            $mail->setClass('emailactive');
            $mail->setLink("mailto:" . $emailadr);
            $mail->setContent($realname);
        } else {
            $mail = new cHTMLLink();
            $mail->setLink('#');
            $mail->setClass('emaildeactive');
            $mail->setContent($realname);
        }
        return $mail;
    }

    /**
     * this function builds buttons for user interactions
     *
     * @param $key
     * @param $cont
     *
     *
     * @param $cfg
     * @return array with buttons
     */
    protected function buildOnlineButtonBackendListMode(&$key, &$cont, &$cfg) {
        global $area;
        $buttons = array();

        $id = $cont['id_user_forum'];

        // shows onlineState
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
        $online->setTargetFrame('right_bottom');
        $online->setStyle('margin-right:10px;');

        $online->setCustom('action', 'online_toggle');
        $online->setCustom('idart', $cont['idart']);
        $online->setCustom('id_user_forum', $cont['id_user_forum']);
        $online->setCustom('idcat', $cont['idcat']);
        $online->setCustom('online', $cont['online']);
        $online->setAttribute('method', 'get');

        // link to edit mode
        $edit = new cHTMLButton("edit");
        $edit->setImageSource($cfg['path']['images'] . 'but_todo.gif');
        $edit->setEvent('click', "$('form[name=$id]').submit()");
        $edit->setStyle('margin-right:10px; ');
        $edit->setMode('image');
        $edit->setAlt(UserForum::i18n('EDIT'));

        $message = UserForum::i18n('ALLDELETEFROMCATHIER');
        $level = $cont['level'];
        $keyy = $key;
        $id = $cont['id_user_forum'];
        $idacat = $cont['idcat'];
        $idaart = $cont['idart'];

        // button with delete action
        $deleteButton = '<a title="' . $cont['title'] . '" href="javascript:void(0)" onclick="showConfirmation(&quot;' . $message . '&quot;, function(){deleteArticlesByIdRight(' . $level . ',' . $keyy . ',' . $id . ',' . $idacat . ',' . $idaart . ');});return false;"><img src="' . $cfg['path']['images'] . 'delete.gif" border="0" title="' . $message . '" alt="' . $message . '"></a>';

        // insert buttons to array for return
        $buttons['online'] = $online;
        $buttons['edit'] = $edit;
        $buttons['delete'] = $deleteButton;

        return $buttons;
    }

    /**
     * generate main menu
     *
     * @param $result array with comments
     * @return ArticleForumRightBottom
     */
    protected function getMenu(&$result) {
        $table = new cHTMLTable();
        if (count($result) < 1) {
            return new cHTMLTable();
        }
        $table->setCellPadding('100px');
        global $area;
        $table->updateAttributes(array(
            "class" => "generic",
            "cellspacing" => "0",
            "cellpadding" => "2"
        ));

        if (count($result > 0)) {
            $tr = new cHTMLTableRow();
            $th = new cHTMLTableHead();
            $th->setContent(i18n("FORUM_POST", "user_forum"));
            $th->setStyle('text-align: center');
            $tr->appendContent($th);

            $th = new cHTMLTableHead();
            $th->setContent(i18n("ACTIONS", "user_forum"));
            $th->setStyle('widht:20px');
            $th->setStyle('text-align: center');
            $th->setAttribute('valign', 'top');
            $tr->appendContent($th);

            $table->appendContent($tr);
        }

        $menu = new cGuiMenu();
        $cfg = cRegistry::getConfig();

        foreach ($result as $key => $cont) {

            $set = false;
            $like = $cont['like'];
            $dislike = $cont['dislike'];
            $timestamp = $cont['timestamp'];

            $datearray = $this->formatTimeString($cont['timestamp']);
            (empty($datearray))? $date = '' : $date = $datearray['day'] . '.' . $datearray['month'] . '.' . $datearray['year'] . ' ' . UserForum::i18n("AT") . ' ' . $datearray['hour'] . ':' . $datearray['minute'] . ' ' . UserForum::i18n("CLOCK");

            $buttons = array();
            $buttons = $this->buildOnlineButtonBackendListMode($key, $cont, $cfg);

            $online = $buttons['online'];
            $edit = $buttons['edit'];
            $delete = $buttons['delete'];

            // row
            $tr = new cHTMLTableRow();
            $trLike = new cHTMLTableRow();

            $likeButton = new cHTMLImage($cfg['path']['images'] . 'like.png');
            // $likeButton->setAttribute('valign','bottom');
            $dislikeButton = new cHTMLImage($cfg['path']['images'] . 'dislike.png');

            // valid email
            $maili = $this->checkValidEmail($cont['email'], $cont['realname']);
            $text = $cont['forum'];

            $timestamp = $cont['editedat'];

            $editarray = $this->formatTimeString($cont['editedat']);
            (empty($editarray))? $editdate = '' : $editdate = $editarray['day'] . '.' . $editarray['month'] . '.' . $editarray['year'] . ' ' . UserForum::i18n("AT") . ' ' . $editarray['hour'] . ':' . $editarray['minute'] . ' ' . UserForum::i18n("CLOCK");

            $userColl = new cApiUserCollection();
            $user = $userColl->loadItem($cont['editedby'])->get('username');

            if (($cont['editedby'] != '') && ($cont['editedat'] != '') && $cont['editedat'] != "0000-00-00 00:00:00") {
                $edit_information = (UserForum::i18n("EDITED") . $editdate . ' ' . UserForum::i18n("FROM") . $user);
                $edit_information = "<em>$edit_information</em>";
            } else {
                $edit_information = "<br>";
            }

            $tdEmpty = new cHTMLTableData();
            $tdEmpty->appendContent($edit_information);
            $tdLike = new cHTMLTableData();
            $tdEmpty->setAttribute('valign', 'top');
            $tdLike->setAttribute('valign', 'top');
            $tdLike->setStyle('text-align: center');

            // add like/dislike functionality to table
            $tdLike->appendContent($likeButton);
            $tdLike->appendContent(" $like ");
            $tdLike->appendContent($dislikeButton);
            $tdLike->appendContent(" $dislike");

            // in new row
            $trLike->appendContent($tdEmpty);
            $trLike->appendContent($tdLike);

            // build form element
            $form = new cHTMLForm($cont['id_user_forum']);
            $form->setAttribute('action', 'main.php?' . "area=" . $area . '&frame=4');

            $tdForm = new cHTMLTableData();
            $tdForm->setStyle('padding-left:' . $cont['level'] * $this->_indentFactor . 'px');

            // build buttons
            $tdButtons = new cHTMLTableData();
            $tdButtons->setAttribute('valign', 'top');
            $tdButtons->setStyle(' text-align: center');

            $tdButtons->appendContent($online);
            $tdButtons->appendContent($edit);
            $tdButtons->appendContent($delete);
            $tdButtons->appendContent('<br>');
            $tdButtons->appendContent('<br>');

            // create hidden-fields
            $hiddenIdart = new cHTMLHiddenField('idart');
            $hiddenIdcat = new cHTMLHiddenField('idcat');
            $hiddenId_user_forum = new cHTMLHiddenField('id_user_forum');
            $hiddenLike = new cHTMLHiddenField('like');
            $hiddenDislike = new cHTMLHiddenField('dislike');
            $hiddenName = new cHTMLHiddenField('realname');
            $hiddenEmail = new cHTMLHiddenField('email');
            $hiddenLevel = new cHTMLHiddenField('level');
            $hiddenEditdat = new cHTMLHiddenField('editedat');
            $hiddenEditedby = new cHTMLHiddenField('editedby');
            $hiddenTimestamp = new cHTMLHiddenField('timestamp');
            $hiddenForum = new cHTMLHiddenField('forum');
            $hiddenOnline = new cHTMLHiddenField('online');
            $hiddenMode = new cHTMLHiddenField('mode');
            $hiddenKey = new cHTMLHiddenField('key');
            $hiddenaction = new cHTMLHiddenField('action');

            // set values
            $hiddenIdart->setValue($cont['idart']);
            $hiddenIdcat->setValue($cont['idcat']);
            $hiddenId_user_forum->setValue($cont['id_user_forum']);
            $hiddenLike->setValue($cont['like']);
            $hiddenDislike->setValue($cont['dislike']);
            $hiddenName->setValue(str_replace('\\','',conHtmlSpecialChars($cont['realname'])));
            $hiddenEmail->setValue(str_replace('\\','',conHtmlSpecialChars($cont['email'])));
            $hiddenLevel->setValue($cont['level']);
            $hiddenEditdat->setValue($cont['editedat']);
            $hiddenEditedby->setValue($cont['editedby']);
            $hiddenTimestamp->setValue($date);
            $hiddenForum->setValue(str_replace('\\','',conHtmlSpecialChars($cont['forum'])));
            $hiddenOnline->setValue($cont['online']);
            $hiddenMode->setValue('edit');
            $hiddenKey->setValue($key);
            $hiddenaction->setValue('edit');

            // append to hidden-fields to form
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
            $form->appendContent($hiddenaction);

            // generate output text
            $form->appendContent($date . " von " . str_replace('\\','',$maili) . " <br><br>");
            $form->appendContent((str_replace('\\','',$text)) . "<br><br>");
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
    protected function getEditModeMenu($post) {

        global $area;
        $changes = 0;
        $cfg = cRegistry::getConfig();
        $idart = cRegistry::getArticleId();
        $idcat = cRegistry::getCategoryId();
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

        // build form element
        $form1 = new cGuiTableForm("comment", "main.php?area=user_forum&frame=4", "post");
        $form1->addHeader($tr);
        $form1->setTableID("table");

        $user = new cApiUser();
        $user->loadByPrimaryKey($post['editedby']);
        $username = $user->getField('username');

        $id = $post['id_user_forum'];

        $likeButton = new cHTMLImage($cfg['path']['images'] . 'like.png');
        $dislikeButton = new cHTMLImage($cfg['path']['images'] . 'dislike.png');


        $name = new cHTMLTextBox("realname", str_replace('\\', '',(conHtmlSpecialChars($post['realname']))), 30, 255);
        $email = new cHTMLTextBox("email", $post['email'], 30, 255);
        $like = new cHTMLTextBox("like", $post['like'], 7, 7);
        $dislike = new cHTMLTextBox("dislike", $post['dislike'], 7, 7);

        $text = str_replace("<br />", "\n", conHtmlSpecialChars($post['forum']));
        $text = str_replace('\\','',$text);

        $forum = new cHTMLTextArea("forum", $text);

        $datearray = $this->formatTimeString($post['timestamp']);
        (empty($datearray))? $date = '' : $date = $datearray['day'] . '.' . $datearray['month'] . '.' . $datearray['year'] . ' ' . UserForum::i18n("AT") . ' ' . $datearray['hour'] . ':' . $datearray['minute'] . ' ' . UserForum::i18n("CLOCK");

        $editedatearray = $this->formatTimeString($post['editedat']);
        (empty($editedatearray))? $editedat = '' : $editedat = $editedatearray['day'] . '.' . $editedatearray['month'] . '.' . $editedatearray['year'] . ' ' . UserForum::i18n("AT") . ' ' . $editedatearray['hour'] . ':' . $editedatearray['minute'] . ' ' . UserForum::i18n("CLOCK");

        $timestamp = new cHTMLTextBox("timestamp", $date, 30, 255);
        $editedat = new cHTMLTextBox("editedat", $editedat, 30, 255);
        $editedby = new cHTMLTextBox("editedby", $username, 30, 255);

        $editedat->setDisabled(true);
        $timestamp->setDisabled(true);
        $editedby->setDisabled(true);

        $form1->addCancel("main.php?area=user_forum&frame=4&action=back&idart=$idart&idcat=$idcat");
        $form1->add(UserForum::i18n("USER"), $name, '');
        $form1->add(UserForum::i18n("EMAIL"), $email, '');
        $form1->add(UserForum::i18n("LIKE"), $like, '');
        $form1->add(UserForum::i18n("DISLIKE"), $dislike, '');
        $form1->add(UserForum::i18n("TIME"), $timestamp, '');
        $form1->add(UserForum::i18n("EDITDAT"), $editedat, '');
        $form1->add(UserForum::i18n("EDITEDBY"), $editedby, '');

        $onlineBox = new cHTMLCheckbox("onlineState", "");

        ($post['online'] == 1)? $onlineBox->setChecked(true) : $onlineBox->setChecked(false);
        $form1->add(UserForum::i18n("ONLINE"), $onlineBox, '');

        $idart = $post['idart'];
        $idcat = $post['idcat'];

        $form1->add(UserForum::i18n("COMMENT"), $forum, '');

        // $form1->setVar('online', $post['online']);
        $form1->setVar("id_user_forum", $post['id_user_forum']);
        $form1->setVar("idart", $post['idart']);
        $form1->setVar("idcat", $post['idcat']);
        $form1->setVar("action", 'update');
        $form1->setVar("mode", "list");
        $form1->setVar("activeChanges", $changes);

        $this->appendContent($form1);

        return $this;
    }

    protected function getForum($id_cat, $id_art, $id_lang) {
        $arrUsers = $this->_collection->getExistingforum($id_cat, $id_art, $id_lang);

        $arrforum = array();

        $this->_collection->getTreeLevel($id_cat, $id_art, $id_lang, $arrUsers, $arrforum);
        $result = array();
        $this->normalizeArray($arrforum, $result);
        $ret = $this->getMenu($result);

        return $ret;
    }

    protected function normalizeArray($arrforum, &$result, $level = 0) {
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
     * this function calls different actions depending on the received values
     * via $_POST oder $_GET.
     *
     * @param $get
     * @param $post
     * @throws Exception
     */
    public function receiveData(&$get, &$post) {
        if (isset($_REQUEST['action']) && $_REQUEST['action'] != NULL) {
            $this->switchActions();
        }
    }

    /**
     * switch case action calling
     *
     * @throws Exception
     */
    protected function switchActions() {
        $lang = cRegistry::getLanguageId();
        $idart = $_REQUEST['idart'];
        $idcat = $_REQUEST['idcat'];
        $action = $_REQUEST["action"];
        (isset($_REQUEST['onlineState']))? $online = 1 : $online = 0;

        switch ($action) {

            // after click on online button in std dialog
            case 'online_toggle':
                $this->_collection->toggleOnlineState($_REQUEST['online'], $_REQUEST['id_user_forum']);
                $this->getForum($idcat, $idart, $lang);
                break;
            // after click on delete button in std dialog
            case 'deleteComment':
                $this->_collection->deleteHierarchie($_REQUEST['key'], $_REQUEST['level'], $idart, $idcat, $lang);
                $this->getForum($idcat, $idart, $lang);
                break;
            // after click on save button in edit dialog
            case 'update':
                $this->_collection->updateValues($_POST['id_user_forum'], $_POST['realname'], $_POST['email'], $_POST['like'], $_POST['dislike'], $_POST['forum'], $online, $_POST['onlineState']);
                $this->getForum($idcat, $idart, $lang);
                break;
            case 'show_form':
                // lists all comments from given articleId
                $this->getForum($idcat, $idart, $lang);
                break;
            case 'edit':
                // shows edit mode for a comment
                $this->getEditModeMenu($_POST);
                break;
            // cancel Button in edit dialog
            case 'back':
                $this->getForum($idcat, $idart, $lang);
                break;
            case 'empty':
                // $this->getForum($idcat, $idart, $lang);
                break;
            default:
                $this->getForum($idcat, $idart, $lang);
                throw new Exception('$_GET["action"] type ' . $_REQUEST["action"] . ' not implemented');
        }
    }

}

?>