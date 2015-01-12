<?php
/**
 * description:
 *
 * @package Module
 * @subpackage ContentUserForum
 * @version SVN Revision $Rev:$
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') or die('Illegal call');

//call userforum administration
if (cRegistry::isBackendEditMode()) {
    echo "CMS_USERFORUM[2]";
}

/**
 *
 * @author claus.schunk
 */
class UserForumArticle {

    /**
     *
     * @var bool
     */
    protected $_qoute = true;

    /**
     *
     * @var string
     */
    protected $_messageText = '';

    /**
     *
     * @var bool
     */
    protected $_generate = true;

    /**
     *
     * @var unknown_type
     */
    protected $_allowDeleting;

    /**
     *
     * @var unknown_type
     */
    protected $_userLoggedIn;

    /**
     *
     * @var unknown_type
     */
    protected $_allowedToEditForum;

    /**
     *
     * @var unknown_type
     */
    protected $_modMode;

    /**
     *
     * @access protected
     * @var cSmartyFrontend
     */
    protected $_tpl;

    /**
     *
     * @access protected
     * @var string email
     */
    protected $_currentEmail;

    /**
     *
     * @access protected
     * @var string realname
     */
    protected $_currentRealname;

    /**e
     *
     * @access protected
     * @var bool counter
     *      used from checkCookie for validation if like/dislike feature was
     *      already used from same user.
     */
    protected $_counter;

    /**
     *
     * @access protected
     * @var int articleId
     */
    protected $_idart;

    /**
     *
     * @access protected
     * @var int CategoryId
     */
    protected $_idcat;

    /**
     *
     * @access protected
     * @var int LanguageId
     */
    protected $_idlang;

    /**
     *
     * @access protected
     * @var string userid
     */
    protected $_userid;

    /**
     *
     * @access protected
     * @var ArticleForumCollection
     */
    protected $_collection;

    /**
     *
     */
    public function __construct() {
        $this->_tpl = cSmartyFrontend::getInstance();
        $this->_messageText = '';
        $this->_generate = true;
        $this->_idart = cRegistry::getArticleId();
        $this->_idcat = cRegistry::getCategoryId();
        $this->_idlang = cRegistry::getLanguageId();
        $this->_collection = new ArticleForumCollection();
        $this->_qoute = ($this->_collection->getQuoteState($this->_idart));
        $this->_modMode = ($this->_collection->getModeModeActive($this->_idart));
    }

    /**
     * main method for controlling different actions received from $_REQUEST[]
     *
     * @param  array  $request  received $_REQUEST[]
     */
    public function receiveData(array $request) {
        $this->_checkCookie();

        $auth = cRegistry::getAuth();
        $this->_allowDeleting = (stristr($auth->auth['perm'], 'admin') === FALSE) ? false : true;
        $bAllowAnonymousforum = (getEffectiveSetting('user_forum', 'allow_anonymous_forum', '1') == '1') ? true : false;

        $this->_getUser($auth->auth['uid']);
        $this->_allowedToEditForum = ($bAllowAnonymousforum || $this->_userLoggedIn && !$bAllowAnonymousforum) ? true : false;

        switch ($_REQUEST['user_forum_action']) {
            // user interaction click on like button
            case 'like_forum':
                $this->_incrementLike();
                $this->_listForum();
                break;
            // user interaction click on dislike button
            case 'dislike_forum':
                $this->_incrementDislike();
                $this->_listForum();
                break;
            // user interaction click on new comment
            case 'new_forum':
                $this->_newEntry();
                break;
            // user interaction click at save in input new comment dialog
            case 'save_new_forum':
                if ($this->_modMode && $this->_saveForum()) {
                    echo '<br />';
                    echo mi18n("FEEDBACK");
                }
                $this->_listForum();
                break;
            default:
                $this->_listForum();
                break;
        }
    }

    /**
     *
     * @param string $userid
     */
    private function _getUser($userid) {
        if (($userid != '') && ($userid != 'nobody')) {
            $this->_userLoggedIn = true;
            $user = $this->_collection->selectUser($userid);
            $this->_currentEmail = $user['email'];
            $this->_currentRealname = $user['realname'];
        } else {
            $this->_userLoggedIn = false;
            $this->_userid = '';
        }
    }

    /**
     * increments the current number of likes
     */
    private function _incrementLike() {
        $form_id = (int) $_REQUEST['user_forum_id'];
        if ($form_id > 0 && $this->_counter) {
            $this->_collection->incrementLike($form_id);
        }
    }

    /**
     * increments the current number of dislikes
     */
    private function _incrementDislike() {
        $form_id = (int) $_REQUEST['user_forum_id'];
        if ($form_id > 0 && $this->_counter) {
            $this->_collection->incrementDislike($form_id);
        }
    }

    /**
     * submit for new entry will be called after click at new comment
     */
    private function _saveForum() {
        $regex = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/';
        // Run the preg_match() function on regex against the email address

        if ($this->_allowedToEditForum) {

            $this->_userid = $_REQUEST['userid'];
            $this->_allowDeleting = $_REQUEST['deleting'];
            $contenido = $_REQUEST['contenido'];
            $bInputOK = true;

            $email = trim($_REQUEST['email']);
            $realname = trim($_REQUEST['realname']);
            $forum = trim($_REQUEST['forum']);
            $parent = (int) $_REQUEST['user_forum_parent'];
            $forum_quote = trim($_REQUEST['forum_quote']);

            $this->_getUser($this->_userid);

            // error validation for user inputs
            if ($this->_userLoggedIn) {
                if ($forum == '') {
                    $this->_messageText .= mi18n("enterYourArticle") . '<br />';
                    $bInputOK = false;
                }
            } else {

                if ($email == '') {
                    $this->_messageText .= mi18n("enterYourMail") . '<br />';
                    $bInputOK = false;
                }
                if ($email != '') {
                    if (!preg_match($regex, $email)) {
                        $this->_messageText .= mi18n("enterValidMail") . '<br />';
                        $bInputOK = false;
                    }
                }

                if ($realname == '') {
                    $this->_messageText .= mi18n("enterYourName") . '<br />';
                    $bInputOK = false;
                }

                if ($forum == '') {
                    $this->_messageText .= mi18n("enterYourArticle") . '<br />';
                    $bInputOK = false;
                }
            }

            if ($bInputOK) {
                // build array for language synchonisation
                $ar = array(
                    'NEWENTRY' => mi18n("NEWENTRY"),
                    'NEWENTRYTEXT' => mi18n("NEWENTRYTEXT"),
                    'COMMENT' => mi18n("COMMENT"),
                    'USER' => mi18n("USER"),
                    'EMAIL' => mi18n("EMAILADR"),
                    'ARTICLE' => mi18n("INARTICLE")
                );
                $this->_collection->languageSync($ar);
                // persist comment
                $this->_collection->insertValues($parent, $this->_idart, $this->_idcat, $this->_idlang, $this->_userid, $email, $realname, $forum, $forum_quote);

                $this->_messageText .= mi18n("yourArticleSaved");
            } else {

                $this->_tpl->assign('MESSAGE', $this->_messageText);

                if ($this->_userLoggedIn) {
                    $this->_tpl->assign('INPUT_EMAIL', $this->_currentEmail . "<input type=\"hidden\" name=\"email\" value=\"$this->_currentEmail\" />");
                    $this->_tpl->assign('INPUT_REALNAME', $this->_currentRealname . "<input type=\"hidden\" name=\"realname\" value=\"$this->_currentRealname\" />");
                    $this->_tpl->assign('INPUT_FORUM', $forum);
                } else {
                    $this->_tpl->assign('INPUT_EMAIL', "<input type=\"text\" name=\"email\" value=\"$email\" />");
                    $this->_tpl->assign('INPUT_REALNAME', "<input type=\"text\" name=\"realname\" value=\"$realname\" />");
                    $this->_tpl->assign('INPUT_FORUM', $forum);
                    $this->_tpl->assign('INPUT_FORUM_QUOTE', $forum_quote);
                }

                if (strlen($forum_quote) > 0) {
                    $this->_tpl->assign('DISPLAY', 'display:block');
                    $this->_tpl->assign('INPUT_FORUM_QUOTE', $forum_quote);
                } else {
                    $this->_tpl->assign('DISPLAY', 'display:none');
                    $this->_tpl->assign('INPUT_FORUM_QUOTE', '');
                }

                $this->_tpl->assign('REALNAME', mi18n("yourName"));
                $this->_tpl->assign('EMAIL', mi18n("yourMailAddress"));
                $this->_tpl->assign('FORUM', mi18n("yourArticle"));
                $this->_tpl->assign('FORUM_QUOTE', mi18n("quote"));
                $this->_tpl->assign('IDCAT', $this->_idcat);
                $this->_tpl->assign('IDART', $this->_idart);
                $this->_tpl->assign('SAVE_FORUM', mi18n("saveArticle"));
                $this->_tpl->assign('USER_FORUM_PARENT', (int) $_REQUEST['user_forum_parent']);

                $this->_tpl->assign('CANCEL_FORUM', mi18n("cancel"));
                $this->_tpl->assign('CANCEL_LINK', "front_content.php?idart=$this->_idart");

                $this->_tpl->assign('USERID', $this->_userid);
                $this->_tpl->assign('CONTENIDO', $contenido);

                // check for replied comments
                $replyId = (int) $_REQUEST['user_forum_parent'];
                if ($replyId > 0) {

                    $content = $this->_collection->selectNameAndNameByForumId($replyId);
                    $empty = (count($content) > 0) ? false : true;

                    if (!$empty) {
                        $transTemplate = mi18n("answerToQuote");
                        $transTemplateAfter = mi18n("from");
                        $this->_tpl->assign('FORUM_REPLYMENT', $transTemplate . '<br/>' . $content['forum'] . "<br/><br/>" . $transTemplateAfter . ' ' . $content['realname']);
                    } else {
                        $this->_tpl->assign('FORUM_REPLYMENT', '');
                    }
                } else {
                    $this->_tpl->assign('FORUM_REPLYMENT', '');
                }

                $this->_generate = false;
                // template for new entry
                $this->_tpl->display('user_forum_new.tpl');
            }
        }
        return $bInputOK;
    }

    /**
     * displays all existing comments
     */
    private function _listForum() {
        $linkText = "$this->_userid&deleting=$this->_allowDeleting&idart=$this->_idart";
        if ($this->_generate) {

            // fetch all comments for this article from db.
            $arrUserforum = $this->_collection->getExistingforumFrontend($this->_idcat, $this->_idart, $this->_idlang, true);

            if (count($arrUserforum) == 0) {
                $this->_tpl->assign('MESSAGE', mi18n("noCommentsYet"));
                $this->_tpl->assign('FORUM_TEXT', mi18n("articles"));
                $this->_tpl->assign(mi18n("writeNewEntry"));
                if ($this->_allowedToEditForum) {
                    $link = $linkText;
                    $this->_tpl->assign('LINK_NEW_FORUM', $link);
                } else {
                    $this->_tpl->assign('LINK_NEW_FORUM', mi18n("noPosibleInputForArticle"));
                }
                $this->_tpl->assign('LINKTEXT', mi18n("writeNewEntry"));
                $this->_tpl->display('user_forum_list_empty.tpl');
            } else {
                $this->_tpl->assign('MESSAGE', $this->_messageText);
                $this->_tpl->assign('AMOUNT_forum', count($arrUserforum));
                $this->_tpl->assign('FORUM_TEXT', mi18n("articlesLabel"));

                $number = 1;
                $tplData = array();

                // build array for smarty
                foreach ($arrUserforum as $key => $value) {

                    $record = array();
                    $record['REALNAME'] = str_replace('\\', '', $value['realname']);
                    $record['EMAIL'] = str_replace('\\', '', $value['email']);
                    $record['NUMBER'] = $number;
                    $number++;

                    // string manipulation for time
                    $arrTmp = preg_split('/ /', $value['timestamp']);
                    $arrTmp2 = preg_split('/-/', $arrTmp[0]);
                    $ts = $arrTmp2[2] . '.' . $arrTmp2[1] . '.' . $arrTmp2[0] . ' ' . mi18n("about") . ' ';
                    $ts .= substr($arrTmp[1], 0, 5) . ' ' . mi18n("clock");

                    $record['AM'] = mi18n("AM");
                    $record['WROTE_ON'] = mi18n("wroteAt");
                    $record['WRITE_EMAIL'] = mi18n("emailToAuthor");
                    $record['TIMESTAMP'] = $ts;

                    if (strlen($value['forum_quote']) > 0) {
                        $record['FORUM_QUOTE'] = '<div class="forum_quote">' . $value['forum_quote'] . '</div>';
                    } else {
                        $record['FORUM_QUOTE'] = '';
                    }

                    $record['FORUM'] = str_replace('\\', '', $value['forum']);

                    if (($value['editedby'] != '') && ($value['editedat'] != "0000-00-00 00:00:00")) {

                        // string manipulation for edittime
                        $arrTmp = explode(' ', $value['editedat']);
                        $edittime = substr($arrTmp[1], 0, 5);
                        $arrTmp2 = explode('-', $arrTmp[0]);
                        $editdate = $arrTmp2[2] . '.' . $arrTmp2[1] . '.' . $arrTmp2[0];

                        // displays information if the comment was edited in
                        // backend mode.
                        $tmp = mi18n("articleWasEditAt");

                        $userColl = new cApiUserCollection();
                        $user = $userColl->loadItem($value['editedby'])->get('username');

                        $edit_information = sprintf($tmp, $editdate, $edittime, $user);
                        $record['EDIT_INFORMATION'] = "<br /><br /><em>$edit_information</em>";
                    }

                    // ansers allowed or not.
                    if ($this->_qoute) {
                        $record['REPLY'] = sprintf($linkText, $key);
                    } else {
                        $record['REPLY'] = NULL;
                    }

                    $record['REPLY_QUOTE'] = sprintf($linkText, $key, $key);
                    $record['LIKE'] = sprintf($linkText, $key, $value['like']);
                    $record['DISLIKE'] = sprintf($linkText, $key, $value['dislike']);
                    $record['FROM'] = mi18n("from");
                    $record['OPINION'] = mi18n("sameOpinion");
                    $record['LIKE_COUNT'] = $value['like'];
                    $record['DISLIKE_COUNT'] = $value['dislike'];
                    $record['PADDING'] = $value['level'] * 20;
                    $record['LINKTEXT'] = mi18n("writeNewEntry");
                    $record['REPLYTEXT'] = mi18n("answers");
                    $record['QUOTETEXT'] = mi18n("replyQuote");
                    $record['FORMID'] = $value['id_user_forum'];
                    $record['LINKBEGIN'] = "";
                    $record['LINKEND'] = "";
                    $record['MAILTO'] = '#';
                    $record['EMAIL'] = '';

                    array_push($tplData, $record);
                }

                $this->_tpl->assign('POSTS', $tplData);

                $sTemp = mi18n("showHideArticles");
                $sTemp = str_replace('___', count($arrUserforum), $sTemp);

                if ($this->_allowedToEditForum) {
                    $link = $linkText;

                    $tplOptionList = new cTemplate();
                    $tplOptionList->set('s', 'SHOW_forum', $sTemp);

                    $this->_tpl->assign('SHOW_FORUM_OPTION', $tplOptionList->generate('templates/user_forum_option_list.tpl', 1));
                    $this->_tpl->assign('LINKTEXT', mi18n("writeNewEntry"));
                    $this->_tpl->assign('LINK_NEW_FORUM', $linkText);
                } else {
                    $this->_tpl->assign('LINK_NEW_FORUM', mi18n("noPosibleInputForArticle"));
                }

                $this->_tpl->assign('NUM_FORUM', count($arrUserforum));
                // template : list all entries
                $this->_tpl->display('user_forum_list.tpl');
            }
        }
    }

    /**
     * generate view for new entrys
     */
    private function _newEntry() {
        if ($this->_allowedToEditForum) {
            $this->_tpl->assign('MESSAGE', $this->_messageText);
            $idquote = (int) $_REQUEST['user_forum_quote'];

            if ($idquote > 0) {
                $content = $this->_collection->selectNameAndNameByForumId($idquote);
                $empty = (count($content) > 0) ? false : true;
                if (!$empty) {
                    $ar = $this->_collection->getCommentContent($idquote);
                    $transTemplate = mi18n("quoteFrom");
                    $this->_tpl->assign('INPUT_FORUM_QUOTE', $transTemplate . ' ' . $ar['name'] . "\n" . $ar['content']);
                    $this->_tpl->assign('DISPLAY', 'display:block');
                } else {
                    $this->_tpl->assign('DISPLAY', 'display:none');
                    $this->_tpl->assign('INPUT_FORUM_QUOTE', '');
                }
            } else {
                $this->_tpl->assign('DISPLAY', 'display:none');
                $this->_tpl->assign('INPUT_FORUM_QUOTE', '');
            }

            $replyId = (int) $_REQUEST['user_forum_parent'];

            if ($replyId > 0) {
                $content = $this->_collection->selectNameAndNameByForumId($replyId);
                $empty = (count($content) > 0) ? false : true;

                if (!$empty) {
                    // Quote anser content
                    $ar = $this->_collection->getCommentContent($replyId);
                    $transTemplate = mi18n("answerToQuote");
                    $transTemplateContent = $ar['content'];
                    $transTemplateAfter = mi18n("from");
                    $transTemplateName = $ar['name'];
                    $this->_tpl->assign('FORUM_REPLYMENT', $transTemplate . '<br/>' . $transTemplateContent . "<br/><br/>" . $transTemplateAfter . ' ' . $transTemplateName);
                } else {
                    $this->_tpl->assign('FORUM_REPLYMENT', '');
                }
            } else {
                $this->_tpl->assign('FORUM_REPLYMENT', '');
            }

            if ($this->_modMode) {
                $this->_tpl->assign('MODEMODETEXT', mi18n('MODEMODETEXT'));
            }

            $this->_tpl->assign('INPUT_EMAIL', "<input type=\"text\" name=\"email\" value=\"\" tabindex=\"2\" />");
            $this->_tpl->assign('INPUT_REALNAME', "<input type=\"text\" name=\"realname\" value=\"\" tabindex=\"1\" />");
            $this->_tpl->assign('INPUT_FORUM', '');
            $this->_tpl->assign('REALNAME', mi18n("yourName"));
            $this->_tpl->assign('EMAIL', mi18n("yourMailAddress"));
            $this->_tpl->assign('FORUM', mi18n("yourArticle"));
            $this->_tpl->assign('FORUM_QUOTE', mi18n("quote"));
            $this->_tpl->assign('IDCAT', $this->_idcat);
            $this->_tpl->assign('IDART', $this->_idart);
            $this->_tpl->assign('SAVE_FORUM', mi18n("saveArticle"));
            $this->_tpl->assign('CANCEL_FORUM', mi18n("cancel"));
            $this->_tpl->assign('CANCEL_LINK', "front_content.php?idart=$this->_idart");
            $this->_tpl->assign('USERID', $_REQUEST['userid']);
            $this->_tpl->assign('DELETING', $_REQUEST['deleting']);
            $this->_tpl->assign('CONTENIDO', $_REQUEST['contenido']);
            $this->_tpl->assign('USER_FORUM_PARENT', (int) $_REQUEST['user_forum_parent']);
            $this->_tpl->display('user_forum_new.tpl');
        }
    }

    /**
     * this function sets a cookie when receiving a click on like/dislike -
     * buttons.
     * After the first click the user can�t add likes/dislikes for the same
     * comment for a fixed time intervall (value in cookie).
     * @TODO: Use $_REQUEST passed to receiveData()
     */
    private function _checkCookie() {
        $ip = $_SERVER['REMOTE_ADDR'];
        $time = time();

        if ($_REQUEST['user_forum_action'] == 'dislike_forum' && isset($_COOKIE['cookie'][$ip][$_REQUEST['user_forum_id']][$_REQUEST['user_forum_action']])) {
            $this->_counter = false;
        } elseif ($_REQUEST['user_forum_action'] == 'dislike_forum' && !isset($_COOKIE['cookie'][$ip][$_REQUEST['user_forum_id']][$_REQUEST['user_forum_action']])) {
            setcookie("cookie[" . $ip . "][" . $_REQUEST['user_forum_id'] . "][" . $_REQUEST['user_forum_action'] . "]", 1, $time + 3600);
            $this->_counter = true;
        }
        if ($_REQUEST['user_forum_action'] == 'like_forum' && isset($_COOKIE['cookie'][$ip][$_REQUEST['user_forum_id']][$_REQUEST['user_forum_action']])) {
            $this->_counter = false;
        } elseif ($_REQUEST['user_forum_action'] == 'like_forum' && !isset($_COOKIE['cookie'][$ip][$_REQUEST['user_forum_id']][$_REQUEST['user_forum_action']])) {
            setcookie("cookie[" . $ip . "][" . $_REQUEST['user_forum_id'] . "][" . $_REQUEST['user_forum_action'] . "]", 1, $time + 3600);
            $this->_counter = true;
        }
    }

}

// generate object
$userForumArticle = new UserForumArticle();
$userForumArticle->receiveData($_REQUEST);
?>