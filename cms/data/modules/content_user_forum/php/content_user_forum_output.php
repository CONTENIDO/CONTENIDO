<?php
class UserForumArticle {

    protected $tpl;

    protected $force;

    protected $messageText = '';

    protected $generate = true;

    protected $bAllowDeleting;

    protected $bAllowAnonymousforum;

    protected $userid;

    protected $current_email;

    protected $current_realname;

    protected $bUserLoggedIn;

    protected $bAllowNewforum;

    protected $ip;

    protected $bCounter;

    protected $idart;

    protected $idcat;

    protected $idlang;

    public function __construct() {
        $this->tpl = Contenido_SmartyWrapper::getInstance();
        $this->messageText = '';
        $this->generate = true;
        $this->idart = cRegistry::getArticleId();
        $this->idcat = cRegistry::getCategoryId();
        $this->idlang = cRegistry::getLanguageId();
    }

    function receiveData($request) {
        $this->checkCookie();
        $this->checkForceState();

        (stristr($auth->auth['perm'], 'admin') === FALSE)? $this->bAllowDeleting = false : $this->bAllowDeleting = true;
        (getEffectiveSetting('user_forum', 'allow_anonymous_forum', '1') == '1')? $this->bAllowAnonymousforum = true : $this->bAllowAnonymousforum = false;

        $uuid = $auth->auth['uid'];
        $this->getUser($uuid);

        ($this->bAllowAnonymousforum || $this->bUserLoggedIn && !$this->bAllowAnonymousforum)? $this->bAllowNewforum = true : $this->bAllowNewforum = false;

        switch ($_REQUEST['user_forum_action']) {
            case 'like_forum':
                $form_id = (int) $_REQUEST['user_forum_id'];
                if ($form_id > 0 && $this->bCounter) {
                    $this->incrementLike($form_id);
                }
                $this->listForum();
                break;

            case 'dislike_forum':
                $form_id = (int) $_REQUEST['user_forum_id'];
                if ($form_id > 0 && $this->bCounter) {
                    $this->incrementDislike($form_id);
                }
                $this->listForum();
                break;

            case 'new_forum':
                $this->newEntry();
                break;

            case 'save_new_forum':
                $this->saveForum();
                $this->listForum();
                break;

            default:
                $this->listForum();
                break;
        }
    }

    function getUser($userid) {
        $db = cRegistry::getDb();
        $cfg = cRegistry::getConfig();
        if (($userid != '') && ($userid != 'nobody')) {
            $this->bUserLoggedIn = true;

            $db->query("SELECT * FROM " . $cfg['tab']['phplib_auth_user_md5'] . " WHERE user_id = '$userid'");
            $db->next_record();
            $this->current_email = $db->f("email");
            $this->current_realname = $db->f("realname");
        } else {
            $this->bUserLoggedIn = false;
            $this->userid = '';
        }
    }

    function incrementLike(&$form_id) {
        $db = cRegistry::getDb();
        $query = "UPDATE con_pi_user_forum pi SET pi.like = pi.like + 1
              WHERE id_user_forum = " . mysql_real_escape_string($form_id);

        $db->query($query);
    }

    function incrementDislike(&$form_id) {
        $db = cRegistry::getDb();
        $query = "UPDATE con_pi_user_forum pi SET pi.dislike = pi.dislike + 1
              WHERE id_user_forum = " . mysql_real_escape_string($form_id);

        $db->query($query);
    }

    function selectNameAndNameByForumId($idquote) {
        $db = cRegistry::getDb();
        $query = "SELECT realname,forum FROM con_pi_user_forum WHERE id_user_forum = " . mysql_real_escape_string($idquote);
        $db->query($query);
        $data = array();
        while ($db->next_record()) {
            array_push($data, $db->toArray());
        }
        return $data;
    }

    function insertValues($parent, $idart, $idcat, $lang, $userid, $email, $realname, $forum, $forum_quote) {
        $db = cRegistry::getDb();

        $query = "INSERT INTO con_pi_user_forum VALUES(
        NULL, $parent, $idart, $idcat, $lang,'" . mysql_real_escape_string($userid) . "', '" . mysql_real_escape_string($email) . "',
		'" . mysql_real_escape_string($realname) . "', '" . mysql_real_escape_string($forum) . "',
		'" . mysql_real_escape_string($forum_quote) . "', 0, 0, '','', '" . date("Y-m-d H:i:s") . "', '1')";

        $db->query($query);
    }

    function saveForum() {
        if ($this->bAllowNewforum) {

            // AFTER ZITATSUBMIT

            $this->userid = $_REQUEST['userid'];
            $this->bAllowDeleting = $_REQUEST['deleting'];
            $contenido = $_REQUEST['contenido'];

            $bInputOK = true;

            $email = trim($_REQUEST['email']);
            $realname = trim($_REQUEST['realname']);
            $forum = trim($_REQUEST['forum']);
            $parent = (int) $_REQUEST['user_forum_parent'];
            $forum_quote = trim($_REQUEST['forum_quote']);

            // $this->message = '';
            $this->getUser($this->userid);

            if ($this->bUserLoggedIn) {
                if ($forum == '') {
                    $this->messageText .= mi18n("enterYourArticle") . '<br />';
                    $bInputOK = false;
                }
            } else {

                if ($email == '') {
                    $this->messageText .= mi18n("enterYourMail") . '<br />';
                    $bInputOK = false;
                }

                if ($realname == '') {
                    $this->messageText .= mi18n("enterYourName") . '<br />';
                    $bInputOK = false;
                }

                if ($forum == '') {
                    $this->messageText .= mi18n("enterYourArticle") . '<br />';
                    $bInputOK = false;
                }
            }

            if ($bInputOK) {
                $this->insertValues($parent, $this->idart, $this->idcat, $this->idlang, $this->userid, $email, $realname, $forum, $forum_quote);
                $this->messageText .= mi18n("yourArticleSaved");
            } else {

                $this->tpl->assign('MESSAGE', $this->messageText);

                if ($this->bUserLoggedIn) {
                    $this->tpl->assign('INPUT_EMAIL', $this->current_email . "<input type=\"hidden\" name=\"email\" value=\"$this->current_email\" />");
                    $this->tpl->assign('INPUT_REALNAME', $this->current_realname . "<input type=\"hidden\" name=\"realname\" value=\"$this->current_realname\" />");
                    $this->tpl->assign('INPUT_FORUM', $forum);
                } else {
                    $this->tpl->assign('INPUT_EMAIL', "<input type=\"text\" name=\"email\" value=\"$email\" />");
                    $this->tpl->assign('INPUT_REALNAME', "<input type=\"text\" name=\"realname\" value=\"$realname\" />");
                    $this->tpl->assign('INPUT_FORUM', $forum);
                    $this->tpl->assign('INPUT_FORUM_QUOTE', $forum_quote);
                }

                if (strlen($forum_quote) > 0) {
                    $this->tpl->assign('DISPLAY', 'display:block');
                    $this->tpl->assign('INPUT_FORUM_QUOTE', $forum_quote);
                } else {
                    $this->tpl->assign('DISPLAY', 'display:none');
                    $this->tpl->assign('INPUT_FORUM_QUOTE', '');
                }

                $this->tpl->assign('REALNAME', mi18n("yourName"));
                $this->tpl->assign('EMAIL', mi18n("yourMailAddress"));
                $this->tpl->assign('FORUM', mi18n("yourArticle"));
                $this->tpl->assign('FORUM_QUOTE', mi18n("quote"));
                $this->tpl->assign('IDCAT', $this->idcat);
                $this->tpl->assign('IDART', $this->idart);
                $this->tpl->assign('SAVE_FORUM', mi18n("saveArticle"));
                $this->tpl->assign('USER_FORUM_PARENT', (int) $_REQUEST['user_forum_parent']);

                $this->tpl->assign('CANCEL_FORUM', mi18n("cancel"));
                $this->tpl->assign('CANCEL_LINK', "front_content.php?idart=$this->idart");

                $this->tpl->assign('USERID', $this->userid);
                $this->tpl->assign('CONTENIDO', $contenido);

                $replyId = (int) $_REQUEST['user_forum_parent'];
                if ($replyId > 0) {

                    $content = $this->selectNameAndNameByForumId($replyId);
                    (count($content) > 0)? $empty = false : $empty = true;

                    if (!$empty) {
                        $transTemplate = mi18n("answerToQuote");
                        $transTemplateAfter = mi18n("from");
                        $this->tpl->assign('FORUM_REPLYMENT', $transTemplate . '<br/>' . $db->f('forum') . "<br/><br/>" . $transTemplateAfter . ' ' . $db->f('realname'));
                    } else {
                        $this->tpl->assign('FORUM_REPLYMENT', '');
                    }
                } else {
                    $this->tpl->assign('FORUM_REPLYMENT', '');
                }

                $this->generate = false;
                $this->tpl->display('user_forum_new.tpl');
            }
        }
    }

    function listForum() {
        $like_forum_link = "<a href='front_content.php?userid=$this->userid&deleting=$this->bAllowDeleting&idart=$this->idart&user_forum_action=like_forum&user_forum_id=%s' class='like'>%s</a>";
        $dislike_forum_link = "<a href='front_content.php?userid=$this->userid&deleting=$this->bAllowDeleting&idart=$this->idart&user_forum_action=dislike_forum&user_forum_id=%s' class='dislike'>%s</a>";
        $new_forum_link = "<a href='front_content.php?userid=$this->userid&deleting=$this->bAllowDeleting&idart=$this->idart&user_forum_action=new_forum' class='new button red'>" . mi18n("writeNewEntry") . "</a>";
        $reply_forum_link = "<a href='front_content.php?userid=$this->userid&deleting=$this->bAllowDeleting&idart=$this->idart&user_forum_action=new_forum&user_forum_parent=%s' class='reply'>" . mi18n("answers") . "</a>";
        $reply_quote_forum_link = "<a href='front_content.php?userid=$this->userid&deleting=$this->bAllowDeleting&idart=$this->idart&user_forum_action=new_forum&user_forum_parent=%s&user_forum_quote=%s' class='reply_quote'>" . mi18n("replyQuote") . "</a>";

        if ($this->generate) {

            $arrUserforum = $this->getExistingforum($this->idcat, $this->idart, $this->idlang);

            if (count($arrUserforum) == 0) {
                // $tpl->clear_all_assign();

                $this->tpl->assign('MESSAGE', mi18n("noCommentsYet"));
                $this->tpl->assign('FORUM_TEXT', mi18n("articles"));
                if ($this->bAllowNewforum) {
                    $link = $new_forum_link;
                    $this->tpl->assign('LINK_NEW_FORUM', $link);
                } else {
                    $this->tpl->assign('LINK_NEW_FORUM', mi18n("noPosibleInputForArticle"));
                }
                $this->tpl->display('user_forum_list_empty.tpl');
            } else {
                // $tpl->clear_all_assign();
                $this->tpl->assign('MESSAGE', $this->messageText);
                $this->tpl->assign('AMOUNT_forum', count($arrUserforum));
                $this->tpl->assign('FORUM_TEXT', mi18n("articlesLabel"));

                $number = 1;
                $tplData = array();
                foreach ($arrUserforum as $key => $value) {
                    $record = array();
                    $record['REALNAME'] = $value['realname'];
                    $record['EMAIL'] = $value['email'];
                    $record['NUMBER'] = $number;
                    $number++;

                    $arrTmp = preg_split('/ /', $value['timestamp']);
                    $arrTmp2 = preg_split('/-/', $arrTmp[0]);

                    $ts = $arrTmp2[2] . '.' . $arrTmp2[1] . '.' . $arrTmp2[0] . ' ' . mi18n("about") . ' ';
                    $ts .= substr($arrTmp[1], 0, 5) . ' ' . mi18n("clock");

                    $record['DAY'] = $arrTmp2[2];
                    $record['WROTE_ON'] = mi18n("wroteAt");
                    $record['WRITE_EMAIL'] = mi18n("emailToAuthor");
                    $record['TIMESTAMP'] = $ts;

                    if (strlen($value['forum_quote']) > 0) {
                        $record['FORUM_QUOTE'] = '<div class="forum_quote">' . $value['forum_quote'] . '</div>';
                    } else {
                        $record['FORUM_QUOTE'] = '';
                    }

                    $record['FORUM'] = $value['forum'];

                    if (($value['editedby'] != '') && ($value['editedat'] != '')) {
                        $arrTmp = explode(' ', $value['editedat']);

                        $edittime = substr($arrTmp[1], 0, 5);

                        $arrTmp2 = explode('-', $arrTmp[0]);
                        $editdate = $arrTmp2[2] . '.' . $arrTmp2[1] . '.' . $arrTmp2[0];

                        $tmp = mi18n("articleWasEditAt");

                        $edit_information = sprintf($tmp, $editdate, $edittime, $value['editedby']);

                        if ((trim($_REQUEST['email']) != '' && trim($_REQUEST['realname']) != '' && trim($_REQUEST['forum']) != '') || $edit) {
                            $record['EDIT_INFORMATION'] = "<br /><br /><em>$edit_information</em>";
                        } else {
                            $record['EDIT_INFORMATION'] = "";
                        }
                    } else {
                        $record['EDIT_INFORMATION'] = "";
                    }

                    $record['REPLY'] = sprintf($reply_forum_link, $key);
                    $record['REPLY_QUOTE'] = sprintf($reply_quote_forum_link, $key, $key);
                    $record['LIKE'] = sprintf($like_forum_link, $key, $value['like']);
                    $record['DISLIKE'] = sprintf($dislike_forum_link, $key, $value['dislike']);
                    $record['FROM'] = mi18n("from");
                    $record['OPINION'] = mi18n("sameOpinion");
                    $record['LIKE_COUNT'] = $value['like'];
                    $record['DISLIKE_COUNT'] = $value['dislike'];
                    $record['PADDING'] = $value['level'] * 20;

                    array_push($tplData, $record);
                }

                $this->tpl->assign('POSTS', $tplData);

                $sTemp = mi18n("showHideArticles");
                $sTemp = str_replace('___', count($arrUserforum), $sTemp);

                if ($this->bAllowNewforum) {
                    $link = $new_forum_link;

                    $tplOptionList = new cTemplate();
                    $tplOptionList->set('s', 'SHOW_forum', $sTemp);

                    $this->tpl->assign('SHOW_FORUM_OPTION', $tplOptionList->generate('templates/user_forum_option_list.tpl', 1));

                    $this->tpl->assign('LINK_NEW_FORUM', "<br />" . $link . "<br />");
                } else {
                    $this->tpl->assign('LINK_NEW_FORUM', mi18n("noPosibleInputForArticle"));
                }

                $this->tpl->assign('NUM_FORUM', count($arrUserforum));

                $this->tpl->display('user_forum_list.tpl');
            }
        }
    }

    function newEntry() {
        if ($this->bAllowNewforum) {
            // $tpl->clear_all_assign();
            // ZitatAntwort
            $db = cRegistry::getDb();
            $this->tpl->assign('MESSAGE', $this->messageText);
            $idquote = (int) $_REQUEST['user_forum_quote'];

            if ($idquote > 0) {
                $content = $this->selectNameAndNameByForumId($idquote);
                (count($content) > 0)? $empty = false : $empty = true;
                if (!$empty) {
                    $transTemplate = mi18n("quoteFrom");
                    $this->tpl->assign('INPUT_FORUM_QUOTE', $transTemplate . ' ' . $content['realname'] . "\n" . $content['forum']);
                    $this->tpl->assign('DISPLAY', 'display:block');
                } else {
                    $this->tpl->assign('DISPLAY', 'display:none');
                    $this->tpl->assign('INPUT_FORUM_QUOTE', '');
                }
            } else {
                $this->tpl->assign('DISPLAY', 'display:none');
                $this->tpl->assign('INPUT_FORUM_QUOTE', '');
            }

            $replyId = (int) $_REQUEST['user_forum_parent'];
            if ($replyId > 0) {
                $content = $this->selectNameAndNameByForumId($replyId);
                (count($content) > 0)? $empty = false : $empty = true;

                if (!$empty) {
                    $transTemplate = mi18n("answerToQuote");
                    $transTemplateAfter = mi18n("from");
                    $this->tpl->assign('FORUM_REPLYMENT', $transTemplate . '<br/>' . $content['forum'] . "<br/><br/>" . $transTemplateAfter . ' ' . $content['realname']);
                } else {
                    $this->tpl->assign('FORUM_REPLYMENT', '');
                }
            } else {
                $this->tpl->assign('FORUM_REPLYMENT', '');
            }

            $this->tpl->assign('INPUT_EMAIL', "<input type=\"text\" name=\"email\" value=\"\" />");
            $this->tpl->assign('INPUT_REALNAME', "<input type=\"text\" name=\"realname\" value=\"\" />");
            $this->tpl->assign('INPUT_FORUM', '');

            $this->tpl->assign('REALNAME', mi18n("yourName"));
            $this->tpl->assign('EMAIL', mi18n("yourMailAddress"));
            $this->tpl->assign('FORUM', mi18n("yourArticle"));
            $this->tpl->assign('FORUM_QUOTE', mi18n("quote"));
            $this->tpl->assign('IDCAT', $this->idcat);
            $this->tpl->assign('IDART', $this->idart);
            $this->tpl->assign('SAVE_FORUM', mi18n("saveArticle"));
            $this->tpl->assign('CANCEL_FORUM', mi18n("cancel"));
            $this->tpl->assign('CANCEL_LINK', "front_content.php?idart=$this->idart");

            $this->tpl->assign('USERID', $_REQUEST['userid']);
            $this->tpl->assign('DELETING', $_REQUEST['deleting']);
            $this->tpl->assign('CONTENIDO', $_REQUEST['contenido']);
            $this->tpl->assign('USER_FORUM_PARENT', (int) $_REQUEST['user_forum_parent']);

            $this->tpl->display('user_forum_new.tpl');

            // ENDE ZitatANTWORT
        }
    }

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
        return $result;
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

    function getTreeLevel($id_cat, $id_art, $id_lang, &$arrUsers, &$arrforum, $parent = 0) {
        $db = cRegistry::getDb();

        $query = "SELECT * FROM con_pi_user_forum WHERE (idart = $id_art) AND (idcat = $id_cat) AND (idlang = $id_lang)
                 AND (id_user_forum_parent = $parent) ORDER BY timestamp DESC";

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

            $this->getTreeLevel($id_cat, $id_art, $id_lang, $arrUsers, $arrforum[$db->f('id_user_forum')]['children'], $db->f('id_user_forum'));
        }
    }

    function checkCookie() {
        // global $REMOTE_ADDR;
        $this->ip = $REMOTE_ADDR? $REMOTE_ADDR : $_SERVER['REMOTE_ADDR'];
        $time = time();

        if ($_REQUEST['user_forum_action'] == 'dislike_forum' && isset($_COOKIE['cookie'][$this->ip][$_REQUEST['user_forum_id']][$_REQUEST['user_forum_action']])) {
            $this->bCounter = false;
        } elseif ($_REQUEST['user_forum_action'] == 'dislike_forum' && !isset($_COOKIE['cookie'][$this->ip][$_REQUEST['user_forum_id']][$_REQUEST['user_forum_action']])) {
            setcookie("cookie[" . $this->ip . "][" . $_REQUEST['user_forum_id'] . "][" . $_REQUEST['user_forum_action'] . "]", 1, $time + 3600);
            $this->bCounter = true;
        }
        if ($_REQUEST['user_forum_action'] == 'like_forum' && isset($_COOKIE['cookie'][$this->ip][$_REQUEST['user_forum_id']][$_REQUEST['user_forum_action']])) {
            $this->bCounter = false;
        } elseif ($_REQUEST['user_forum_action'] == 'like_forum' && !isset($_COOKIE['cookie'][$this->ip][$_REQUEST['user_forum_id']][$_REQUEST['user_forum_action']])) {
            setcookie("cookie[" . $this->ip . "][" . $_REQUEST['user_forum_id'] . "][" . $_REQUEST['user_forum_action'] . "]", 1, $time + 3600);
            $this->bCounter = true;
        }
    }

    function checkForceState() {
        global $force;
        if (1 == $force) {
            $this->tpl->clearAllCache();
        }
    }

}

$userForumArticle = new UserForumArticle();
$userForumArticle->receiveData($_REQUEST);

?>
