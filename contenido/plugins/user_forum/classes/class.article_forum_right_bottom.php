<?php
/**
 * This file contains the class for visualisation and interactions in the right frame.
 *
 * @package Plugin
 * @subpackage UserForum
 * @author Claus Schunk
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains builds the content of the right frame.
 *
 *
 * @package Plugin
 * @subpackage UserForum
 */


/**
 * Class ArticleForumRightBottom
 */
class ArticleForumRightBottom extends cGuiPage {

    /**
     * @var int
     */
    private $_indentFactor = 20;

    /**
     * @var ArticleForumCollection
     */
    protected $_collection;

    /**
     *
     */
    function __construct() {
        $this->_collection = new ArticleForumCollection();
        parent::__construct('right_bottom', 'user_forum');
        $this->addStyle('right_bottom.css');
        $this->addScript('location.js');
    }

    /**
     * @param $timeStamp
     *
     * @return array
     */
    protected function formatTimeString($timeStamp) {
        $nullString = '0';
        if ($timeStamp == "0000-00-00 00:00:00") {
            return [];
        } else {
            $ar = (date_parse($timeStamp));
            // if elements are smaller than 2 digits add a '0' at front. e.g
            // 2:10 -> 02:10
            (cString::getStringLength($ar['day']) < 2) ? $ar['day'] = $nullString . $ar['day'] : '';
            (cString::getStringLength($ar['month']) < 2) ? $ar['month'] = $nullString . $ar['month'] : '';
            (cString::getStringLength($ar['minute']) < 2) ? $ar['minute'] = $nullString . $ar['minute'] : '';
            (cString::getStringLength($ar['hour']) < 2) ? $ar['hour'] = $nullString . $ar['hour'] : '';
        }

        return $ar;
    }

    /**
     * this function returns an inactive link or an link with mail-to directive
     * if the given mail address is valid
     *
     * @param string $emailAddr
     * @param string $realName
     * @return cHTMLLink
     */
    protected function checkValidEmail($emailAddr, $realName) {
        $regex = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/';
        // Run the preg_match() function on regex against the email address
        if (preg_match($regex, $emailAddr)) {
            $mail = new cHTMLLink();
            $mail->setClass('emailactive');
            $mail->setLink("mailto:" . $emailAddr);
            $mail->setContent($realName);
        } else {
            $mail = new cHTMLLink();
            $mail->setLink('#');
            $mail->setClass('emaildeactive');
            $mail->setContent($realName);
        }
        return $mail;
    }

    /**
     * this function builds buttons for user interactions
     *
     * @param $key
     * @param $cont
     * @param $cfg
     * @param $mod
     *
     * @return array with buttons
     */
    protected function buildOnlineButtonBackendListMode(&$key, &$cont, &$cfg, $mod = null) {
        $area = cRegistry::getArea();
        $buttons = [];

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

        // additional params to identify actions from moderator startpage
        if (isset($mod)) {
            $online->setCustom('mod', true);
            // $edit->setCustom('mod', true);
        }

        $message = UserForum::i18n('ALLDELETEFROMCATHIER');
        $level = $cont['level'] ?? 0;
        $keyy = $key;
        $id = $cont['id_user_forum'];
        $idacat = $cont['idcat'];
        $idaart = $cont['idart'];

        // button with delete action
        $deleteLink = '<a title="' . $message . '" href="javascript:void(0)" onclick="Con.showConfirmation(&quot;' . $message . '&quot;, function(){ deleteArticlesByIdRight(' . $level . ', ' . $keyy . ', ' . $id . ', ' . $idacat . ', ' . $idaart . '); });return false;"><img src="' . $cfg['path']['images'] . 'delete.gif" title="' . $message . '" alt="' . $message . '"></a>';

        // insert buttons to array for return
        $buttons['online'] = $online;
        $buttons['edit'] = $edit;
        $buttons['delete'] = $deleteLink;

        return $buttons;
    }

    /**
     * generate main menu
     *
     * @param array $result array with comments
     * @param null  $mod
     *
     * @return ArticleForumRightBottom|cHTMLTable
     * @throws cException
     */
    public function getMenu(&$result, $mod = null) {
        $area = cRegistry::getArea();
        $table = new cHTMLTable();
        if (count($result) < 1) {
            return $table;
        }
        $table->setCellPadding('100px');
        $table->updateAttributes([
            "class" => "generic",
            "cellspacing" => "0",
            "cellpadding" => "2"
        ]);

        if (count($result) > 0) {
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

        $cfg = cRegistry::getConfig();

        foreach ($result as $key => $cont) {
            $like = $cont['like'];
            $dislike = $cont['dislike'];
            $cont['level'] = $cont['level'] ?? 0;

            $arrDate = $this->formatTimeString($cont['timestamp']);
            $date =  (empty($arrDate)) ? '' : $arrDate['day'] . '.' . $arrDate['month'] . '.' . $arrDate['year'] . ' ' . UserForum::i18n("AT") . ' ' . $arrDate['hour'] . ':' . $arrDate['minute'] . ' ' . UserForum::i18n("CLOCK");

            $buttons = $this->buildOnlineButtonBackendListMode($key, $cont, $cfg, $mod);

            $online = $buttons['online'];
            $edit = $buttons['edit'];
            $delete = $buttons['delete'];

            // row
            $tr = new cHTMLTableRow();
            $trLike = new cHTMLTableRow();

            $likeButton = new cHTMLImage($cfg['path']['images'] . 'like.png');
            // $likeButton->setAttribute('valign', 'bottom');
            $dislikeButton = new cHTMLImage($cfg['path']['images'] . 'dislike.png');

            // valid email
            $maili = $this->checkValidEmail($cont['email'], $cont['realname']);
            $text = $cont['forum'];

            $arrEditedAtDate = $this->formatTimeString($cont['editedat']);
            $editdate = (empty($arrEditedAtDate)) ? '' : $arrEditedAtDate['day'] . '.' . $arrEditedAtDate['month'] . '.' . $arrEditedAtDate['year'] . ' ' . UserForum::i18n("AT") . ' ' . $arrEditedAtDate['hour'] . ':' . $arrEditedAtDate['minute'] . ' ' . UserForum::i18n("CLOCK");

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

            // not allowed at moderator starpage overview
            $tdButtons->appendContent($edit);
            if (!isset($mod)) {
                $tdButtons->appendContent($delete);
            }

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
            $hiddenName->setValue(str_replace('\\', '', conHtmlSpecialChars($cont['realname'])));
            $hiddenEmail->setValue(str_replace('\\', '', conHtmlSpecialChars($cont['email'])));
            $hiddenLevel->setValue($cont['level'] ?? 0);
            $hiddenEditdat->setValue($cont['editedat']);
            $hiddenEditedby->setValue($cont['editedby']);
            $hiddenTimestamp->setValue($date);
            $hiddenForum->setValue(str_replace('\\', '', conHtmlSpecialChars($cont['forum'])));
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

            if (isset($mod)) {
                $form->appendContent(new cHTMLHiddenField('mod'));
            }

            // generate output text
            $form->appendContent($date . " von " . str_replace('\\', '', $maili) . " <br><br>");
            $form->appendContent((str_replace('\\', '', $text)) . "<br><br>");

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
     * @param array $post
     *
     * @return ArticleForumRightBottom
     * @throws cDbException
     * @throws cException
     */
    protected function getEditModeMenu($post) {
        $changes = 0;
        $cfg = cRegistry::getConfig();
        $idart = cRegistry::getArticleId();
        $idcat = cRegistry::getCategoryId();
        $tr = new cHTMLTableRow();

        $th = new cHTMLTableHead();
        $th->setContent(UserForum::i18n("PARAMETER"));

        $th2 = new cHTMLTableHead();
        $th2->setContent(UserForum::i18n("CONTENT"));
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

        $name = new cHTMLTextBox("realname", str_replace('\\', '',(conHtmlSpecialChars($post['realname']))), 30, 255);
        $email = new cHTMLTextBox("email", $post['email'], 30, 255);
        $like = new cHTMLTextBox("like", $post['like'], 7, 7);
        $dislike = new cHTMLTextBox("dislike", $post['dislike'], 7, 7);

        $text = conHtmlSpecialChars(str_replace(['<br />', '<br>', '<br/>'], ['', '', ''], $post['forum']));
        $text = str_replace('\\', '', $text);

        $forum = new cHTMLTextArea("forum", $text);

        $arrDate = $this->formatTimeString($post['timestamp']);
        $date = (empty($arrDate)) ? '' : $arrDate['day'] . '.' . $arrDate['month'] . '.' . $arrDate['year'] . ' ' . UserForum::i18n("AT") . ' ' . $arrDate['hour'] . ':' . $arrDate['minute'] . ' ' . UserForum::i18n("CLOCK");

        $arrEditedAtDate = $this->formatTimeString($post['editedat']);
        $editedat = (empty($arrEditedAtDate)) ? '' : $arrEditedAtDate['day'] . '.' . $arrEditedAtDate['month'] . '.' . $arrEditedAtDate['year'] . ' ' . UserForum::i18n("AT") . ' ' . $arrEditedAtDate['hour'] . ':' . $arrEditedAtDate['minute'] . ' ' . UserForum::i18n("CLOCK");

        $timestamp = new cHTMLTextBox("timestamp", $date, 30, 255);
        $editedat = new cHTMLTextBox("editedat", $editedat, 30, 255);
        $editedby = new cHTMLTextBox("editedby", $username, 30, 255);

        $editedat->setDisabled(true);
        $timestamp->setDisabled(true);
        $editedby->setDisabled(true);

        $form1->add(UserForum::i18n("USER"), $name, '');
        $form1->add(UserForum::i18n("EMAIL"), $email, '');
        $form1->add(UserForum::i18n("LIKE"), $like, '');
        $form1->add(UserForum::i18n("DISLIKE"), $dislike, '');
        $form1->add(UserForum::i18n("TIME"), $timestamp, '');
        $form1->add(UserForum::i18n("EDITDAT"), $editedat, '');
        $form1->add(UserForum::i18n("EDITEDBY"), $editedby, '');

        // handle moderation mode actions
        if (isset($post['mod'])) {
            $form1->setVar('mod', 'mod');
            $form1->addCancel("main.php?area=user_forum&frame=4&action=back&mod=mod");
        } else {
            $form1->addCancel("main.php?area=user_forum&frame=4&action=back&idart=$idart&idcat=$idcat");
        }

        $onlineBox = new cHTMLCheckbox("onlineState", "");
        $onlineBox->setChecked($post['online'] == 1);
        $form1->add(UserForum::i18n("ONLINE"), $onlineBox, '');

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

    /**
     * @param $idCat
     * @param $idArt
     * @param $idLang
     *
     * @return ArticleForumRightBottom
     * @throws cException
     */
    public function getForum($idCat, $idArt, $idLang) {
        $arrUsers = $this->_collection->getExistingforum();

        $arrForum = [];
        $this->_collection->getTreeLevel($idCat, $idArt, $idLang, $arrUsers, $arrForum);

        $result = [];
        $this->normalizeArray($arrForum, $result);

        return $this->getMenu($result);
    }

    /**
     * @param array $arrForum
     * @param array $result
     * @param int   $level
     */
    protected function normalizeArray($arrForum, &$result, $level = 0) {
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
     * @throws cException
     */
    public function getStartpage() {
        $cGuiNotification = new cGuiNotification();
        echo $cGuiNotification->returnNotification(cGuiNotification::LEVEL_INFO, UserForum::i18n('MODMODE'));
        echo '<br />';

        $comments = $this->_collection->getUnmoderatedComments();
        $this->getMenu($comments, 'mod');
    }

    /**
     * switch case action calling
     *
     * @throws Exception
     */
    protected function switchActions() {
        $lang = cSecurity::toInteger(cRegistry::getLanguageId());
        $idart = $_REQUEST['idart'];
        $idcat = $_REQUEST['idcat'];
        $action = $_REQUEST["action"];
        $online = isset($_REQUEST['onlineState']) ? 1 : 0;

        switch ($action) {

            // after click on online button in std dialog
            case 'online_toggle':
                $this->_collection->toggleOnlineState($_REQUEST['online'], $_REQUEST['id_user_forum'], $idart);

                if(!isset($_REQUEST['mod'])) {
                    $this->getForum($idcat, $idart, $lang);
                } else{
                    $this->getStartpage();
                }

                break;
            // after click on delete button in std dialog
            case 'deleteComment':
                $this->_collection->deleteHierarchy($_REQUEST['key'], $_REQUEST['level'], $idart, $idcat, $lang);
                $this->getForum($idcat, $idart, $lang);
                $this->reloadLeftBottomFrame([]);
                break;
            // after click on save button in edit dialog
            case 'update':
                $this->_collection->updateValues($_POST['id_user_forum'], $_POST['realname'], $_POST['email'], $_POST['like'], $_POST['dislike'], $_POST['forum'], $online);
                if(!isset($_REQUEST['mod'])) {
                    $this->getForum($idcat, $idart, $lang);
                } else{
                    $this->getStartpage();
                }

                break;
            case 'show_forum':
                // lists all comments from given articleId
                $this->getForum($idcat, $idart, $lang);
                break;
            case 'delete_forum':
                // deletes all comments from given articleId
                $this->_collection->deleteAllCommentsById($idart);
                $this->reloadLeftBottomFrame(['idart' => null]);
                break;
            case 'edit':
                // shows edit mode for a comment
                $this->getEditModeMenu($_POST);
                break;
                // cancel Button in edit dialog
            case 'back':
                if(!isset($_REQUEST['mod'])) {
                    $this->getForum($idcat, $idart, $lang);
                } else{
                    $this->getStartpage();
                }
                // $this->getForum($idcat, $idart, $lang);
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