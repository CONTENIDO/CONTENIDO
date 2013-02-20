<?php
$tpl = Contenido_SmartyWrapper::getInstance();
global $force;
if (1 == $force) {
    $tpl->clearAllCache();
}

$db = cRegistry::getDb();

$messageText = '';
$generate = true;

if (stristr($auth->auth['perm'],'admin') === FALSE) {
    $bAllowDeleting = false;
} else {
    $bAllowDeleting = true;
}

if (getEffectiveSetting('user_forum','allow_anonymous_forum', '1') == '1') {
    $bAllowAnonymousforum = true;
} else {
    $bAllowAnonymousforum = false;
}

$userid = $auth->auth['uid'];

if (($userid != '') && ($userid != 'nobody')) {
    $bUserLoggedIn = true;

    $db->query("SELECT * FROM ".$cfg['tab']['phplib_auth_user_md5']." WHERE user_id = '$userid'");

    $db->next_record();

    $current_email = $db->f("email");
    $current_realname = $db->f("realname");
} else {
    $bUserLoggedIn = false;
    $userid = '';
}

if (cRegistry::isBackendEditMode()) {
  $like_forum_link = "<a href='front_content.php?userid=$userid&deleting=$bAllowDeleting&idart=$idart&contenido=$contenido&user_forum_action=like_forum&user_forum_id=%s' class='like'>%s</a>";
  $dislike_forum_link = "<a href='front_content.php?userid=$userid&deleting=$bAllowDeleting&idart=$idart&contenido=$contenido&user_forum_action=dislike_forum&user_forum_id=%s' class='dislike'>%s</a>";
  $new_forum_link = "<a href='front_content.php?userid=$userid&deleting=$bAllowDeleting&idart=$idart&contenido=$contenido&idcat=$idcat&user_forum_action=new_forum' class='new button red'>".mi18n("writeNewEntry")."</a>";
  $reply_forum_link = "<a href='front_content.php?userid=$userid&deleting=$bAllowDeleting&idart=$idart&contenido=$contenido&idcat=$idcat&user_forum_action=new_forum&user_forum_parent=%s' class='reply'>".mi18n("answers")."</a>";
  $reply_quote_forum_link = "<a href='front_content.php?userid=$userid&deleting=$bAllowDeleting&idart=$idart&contenido=$contenido&idcat=$idcat&user_forum_action=new_forum&user_forum_parent=%s&user_forum_quote=%s' class='reply_quote'>".mi18n("replyQuote")."</a>";
} else {
  $like_forum_link = "<a href='front_content.php?userid=$userid&deleting=$bAllowDeleting&idart=$idart&user_forum_action=like_forum&user_forum_id=%s' class='like'>%s</a>";
  $dislike_forum_link = "<a href='front_content.php?userid=$userid&deleting=$bAllowDeleting&idart=$idart&user_forum_action=dislike_forum&user_forum_id=%s' class='dislike'>%s</a>";
  $new_forum_link = "<a href='front_content.php?userid=$userid&deleting=$bAllowDeleting&idart=$idart&user_forum_action=new_forum' class='new button red'>".mi18n("writeNewEntry")."</a>";
  $reply_forum_link = "<a href='front_content.php?userid=$userid&deleting=$bAllowDeleting&idart=$idart&user_forum_action=new_forum&user_forum_parent=%s' class='reply'>".mi18n("answers")."</a>";
  $reply_quote_forum_link = "<a href='front_content.php?userid=$userid&deleting=$bAllowDeleting&idart=$idart&user_forum_action=new_forum&user_forum_parent=%s&user_forum_quote=%s' class='reply_quote'>".mi18n("replyQuote")."</a>";
}

if ($bAllowAnonymousforum) {
    $bAllowNewforum = true;
} elseif ($bUserLoggedIn && !$bAllowAnonymousforum) {
    $bAllowNewforum = true;
} else {
    $bAllowNewforum = false;
}
if ($_REQUEST['user_forum_action'] == 'like_forum' || $_REQUEST['user_forum_action'] == 'dislike_forum') {
    $form_id = (int) $_REQUEST['user_forum_id'];
    if ($form_id > 0) {
        if ($_REQUEST['user_forum_action'] == 'like_forum') {
            $fieldname = 'like';
        } else {
            $fieldname = 'dislike';
        }

        $query = "UPDATE con_pi_user_forum SET
                        `$fieldname` = `$fieldname` + 1
                    WHERE
                        id_user_forum = ".mysql_real_escape_string($form_id);

        $db->query($query);
    }
} else if (($_REQUEST['user_forum_action'] == 'delete_forum') && ($_REQUEST['user_forum_delete_id'] != '') && ($bAllowDeleting)) {

    deleteUserForum($_REQUEST['user_forum_delete_id']);

    $message = mi18n("articleRemoved");
    $messageText = mi18n("articleRemoved");

} elseif (($_REQUEST['user_forum_action'] == 'new_forum') && ($bAllowNewforum)) {
    //$tpl->clear_all_assign();

    $tpl->assign('MESSAGE', $messageText);

    $idquote = (int)$_REQUEST['user_forum_quote'];
    if ($idquote > 0) {
        $query = "SELECT * FROM con_pi_user_forum WHERE id_user_forum = ".mysql_real_escape_string($idquote);
        $db->query($query);

        if ($db->next_record()) {
            $transTemplate = mi18n("quoteFrom");
            $tpl->assign('INPUT_FORUM_QUOTE', $transTemplate. ' ' .$db->f('realname'). "\n". $db->f('forum'));
            $tpl->assign('DISPLAY','display:block');
        } else {
            $tpl->assign('DISPLAY','display:none');
            $tpl->assign('INPUT_FORUM_QUOTE','');
        }
    } else {
        $tpl->assign('DISPLAY','display:none');
        $tpl->assign('INPUT_FORUM_QUOTE','');
    }



    $replyId = (int) $_REQUEST['user_forum_parent'];
    if ($replyId > 0) {
        $query = "SELECT * FROM con_pi_user_forum WHERE id_user_forum = ".mysql_real_escape_string($replyId);
        $db->query($query);

        if ($db->next_record()) {
            $transTemplate = mi18n("answerToQuote");
            $transTemplateAfter = mi18n("from");
            $tpl->assign('FORUM_REPLYMENT', $transTemplate. '<br/>' .$db->f('forum'). "<br/><br/>". $transTemplateAfter.' '.$db->f('realname'));
        } else {
            $tpl->assign('FORUM_REPLYMENT', '');
        }
    } else {
        $tpl->assign('FORUM_REPLYMENT', '');
    }

    $tpl->assign('INPUT_EMAIL', "<input type=\"text\" name=\"email\" value=\"\" />");
    $tpl->assign('INPUT_REALNAME', "<input type=\"text\" name=\"realname\" value=\"\" />");
    $tpl->assign('INPUT_FORUM','');

    $tpl->assign('REALNAME', mi18n("yourName"));
    $tpl->assign('EMAIL', mi18n("yourMailAddress"));
    $tpl->assign('FORUM', mi18n("yourArticle"));
    $tpl->assign('FORUM_QUOTE', mi18n("quote"));
    $tpl->assign('IDCAT', $idcat);
    $tpl->assign('IDART', $idart);
    $tpl->assign('SAVE_FORUM', mi18n("saveArticle"));
    $tpl->assign('CANCEL_FORUM', mi18n("cancel"));
    $tpl->assign('CANCEL_LINK', "front_content.php?idart=$idart");

    $tpl->assign('USERID', $_REQUEST['userid']);
    $tpl->assign('DELETING', $_REQUEST['deleting']);
    $tpl->assign('CONTENIDO', $_REQUEST['contenido']);
    $tpl->assign( 'USER_FORUM_PARENT', (int) $_REQUEST['user_forum_parent']);

    $tpl->display('user_forum_new.tpl');
} elseif (($_REQUEST['user_forum_action'] == 'edit_forum') && ($bAllowDeleting)) {
    //not implemented yet
    $idforum = $_REQUEST['user_forum_edit_id'];

    $userid = $_REQUEST['userid'];
    $bAllowDeleting = $_REQUEST['deleting'];
    $contenido = $_REQUEST['contenido'];

    //$tpl->clear_all_assign();

    $query = "SELECT * FROM con_pi_user_forum WHERE id_user_forum = ".mysql_real_escape_string($idforum);
    $db->query($query);

    if ($db->next_record()) {
        $forum = $db->f('forum');
        $email = $db->f('email');
        $realname = $db->f('realname');

        $tpl->assign('SHOW_EMAIL', $email);
        $tpl->assign('SHOW_REALNAME', $realname);
        $tpl->assign('INPUT_FORUM', $forum);
        $tpl->assign('INPUT_FORUM_QUOTE', $forum);

        $tpl->assign('REALNAME', mi18n("name"));
        $tpl->assign('EMAIL', mi18n("emailAddress"));
        $tpl->assign('FORUM_QUOTE', mi18n("quote"));
        $tpl->assign('FORUM', mi18n("yourArticle"));
        $tpl->assign('IDCAT', $idcat);
        $tpl->assign('IDART', $idart);
        $tpl->assign('IDFORUM', $idforum);
        $tpl->assign('SAVE_FORUM', mi18n("saveChangedArticle"));

        $tpl->assign('USERID', $userid);
        $tpl->assign('DELETING', $bAllowDeleting);
        $tpl->assign('CONTENIDO', $contenido);

        $tpl->display('user_forum_new.tpl');
    } else {
        echo mi18n("fatalError");
        die();
    }
} elseif (
   (($_REQUEST['user_forum_action'] == 'save_new_forum') && ($bAllowNewforum)) ||
   (($_REQUEST['user_forum_action'] == 'save_edited_forum') && $_REQUEST['deleting'])) {

    $userid = $_REQUEST['userid'];
    $bAllowDeleting = $_REQUEST['deleting'];
    $contenido = $_REQUEST['contenido'];


    if ($_REQUEST['user_forum_action'] == 'save_new_forum') {
        $bNewforum = true;
    } else {
        $bNewforum = false;
    }

    $bInputOK = true;

    $email = trim($_REQUEST['email']);
    $realname = trim($_REQUEST['realname']);
    $forum = trim($_REQUEST['forum']);
    $parent = (int) $_REQUEST['user_forum_parent'];
    $forum_quote = trim($_REQUEST['forum_quote']);

    $message = '';

    if (($userid != '') && ($userid != 'nobody')) {
        $bUserLoggedIn = true;

        $db->query("SELECT * FROM ".$cfg['tab']['phplib_auth_user_md5']." WHERE user_id = '$userid'");

        $db->next_record();

        $current_email = $db->f("email");
        $current_realname = $db->f("realname");
    } else {
        $bUserLoggedIn = false;
        $userid = '';
    }

    if ($bUserLoggedIn) {
        if ($forum == '') {
            $messageText.=mi18n("enterYourArticle").'<br />';
            $bInputOK = false;
        }
    } else {
        if ($bNewforum) {
            if ($email == '') {
                $messageText.=mi18n("enterYourMail").'<br />';
                $bInputOK = false;
            }

            if ($realname == '') {
                $messageText.=mi18n("enterYourName").'<br />';
                $bInputOK = false;
            }
        }

        if ($forum == '') {
            $messageText.=mi18n("enterYourArticle").'<br />';
            $bInputOK = false;
        }
    }

    if ($bInputOK) {
        //$tpl->clear_all_assign();

        if ($bNewforum) {
            $query = "INSERT INTO con_pi_user_forum VALUES(
                NULL,
                $parent,
                $idart,
                $idcat,
                $lang,
                '".mysql_real_escape_string($userid)."',
                '".mysql_real_escape_string($email)."',
                '".mysql_real_escape_string($realname)."',
                '".mysql_real_escape_string($forum)."',
                '".mysql_real_escape_string($forum_quote)."',
                0,
                0,
                '',
                '',
                '".date("Y-m-d H:i:s")."',
                '".$_SERVER['REMOTE_ADDR']."'
                )";

            $messageText.=mi18n("yourArticleSaved");
        } else {
            $query = "UPDATE con_pi_user_forum SET
                        forum = '".mysql_real_escape_string($forum)."',
                        editedat = '".date("Y-m-d H:i:s")."',
                        editedby = '".mysql_real_escape_string($current_realname)."'
                    WHERE
                        id_user_forum = ".mysql_real_escape_string($_REQUEST['idforum']);

             $messageText.=mi18n("changedArticleSaved");
        }

        $db->query($query);
    } else {

        //$tpl->clear_all_assign();
        $tpl->assign('MESSAGE', $messageText);

        if ($bUserLoggedIn) {
            $tpl->assign('INPUT_EMAIL', $current_email."<input type=\"hidden\" name=\"email\" value=\"$current_email\" />");
            $tpl->assign('INPUT_REALNAME', $current_realname."<input type=\"hidden\" name=\"realname\" value=\"$current_realname\" />");
            $tpl->assign('INPUT_FORUM', $forum);
        } else {
            $tpl->assign('INPUT_EMAIL', "<input type=\"text\" name=\"email\" value=\"$email\" />");
            $tpl->assign('INPUT_REALNAME', "<input type=\"text\" name=\"realname\" value=\"$realname\" />");
            $tpl->assign('INPUT_FORUM',$forum);
            $tpl->assign('INPUT_FORUM_QUOTE',$forum_quote);
        }

        if (strlen($forum_quote) > 0) {
            $tpl->assign('DISPLAY','display:block');
            $tpl->assign('INPUT_FORUM_QUOTE',$forum_quote);
        } else {
            $tpl->assign('DISPLAY','display:none');
            $tpl->assign('INPUT_FORUM_QUOTE','');
        }

        $tpl->assign('REALNAME', mi18n("yourName"));
        $tpl->assign('EMAIL', mi18n("yourMailAddress"));
        $tpl->assign('FORUM', mi18n("yourArticle"));
        $tpl->assign('FORUM_QUOTE', mi18n("quote"));
        $tpl->assign('IDCAT', $idcat);
        $tpl->assign('IDART', $idart);
        $tpl->assign('SAVE_FORUM', mi18n("saveArticle"));
        $tpl->assign( 'USER_FORUM_PARENT', (int) $_REQUEST['user_forum_parent']);

        $tpl->assign('CANCEL_FORUM', mi18n("cancel"));
        $tpl->assign('CANCEL_LINK', "front_content.php?idart=$idart");

        $tpl->assign('USERID', $userid);
        $tpl->assign('DELETING', $bAllowDeleting);
        $tpl->assign('CONTENIDO', $contenido);

        $replyId = (int) $_REQUEST['user_forum_parent'];
        if ($replyId > 0) {
            $query = "SELECT * FROM con_pi_user_forum WHERE id_user_forum = ".mysql_real_escape_string($replyId);
            $db->query($query);

            if ($db->next_record()) {
                $transTemplate = mi18n("answerToQuote");
                $transTemplateAfter = mi18n("from");
                $tpl->assign('FORUM_REPLYMENT', $transTemplate. '<br/>' .$db->f('forum'). "<br/><br/>". $transTemplateAfter.' '.$db->f('realname'));
            } else {
                $tpl->assign('FORUM_REPLYMENT', '');
            }
        } else {
            $tpl->assign('FORUM_REPLYMENT', '');
        }

        $generate = false;
        $tpl->display('user_forum_new.tpl');
    }
}

#List existing forum
if (($_REQUEST['user_forum_action'] != 'new_forum') && ($_REQUEST['user_forum_action'] != 'edit_forum') && $generate) {

    $arrUserforum = getExistingforum($idcat, $idart, $lang);

    if (count($arrUserforum) == 0) {
        //$tpl->clear_all_assign();

        $tpl->assign('MESSAGE',mi18n("noCommentsYet"));
        $tpl->assign('FORUM_TEXT',mi18n("articles"));
        if ($bAllowNewforum) {
            $link = $new_forum_link;
            $tpl->assign( 'LINK_NEW_FORUM', $link);
        } else {
            $tpl->assign( 'LINK_NEW_FORUM', mi18n("noPosibleInputForArticle"));
        }
        $tpl->display('user_forum_list_empty.tpl');
    } else {
        //$tpl->clear_all_assign();
        $tpl->assign('MESSAGE',$messageText);
        $tpl->assign( 'AMOUNT_forum', count($arrUserforum));
        $tpl->assign( 'FORUM_TEXT', mi18n("articlesLabel"));

        $number = 1;
        $tplData = array();
        foreach($arrUserforum as $key => $value) {
            $record = array();
            $record['REALNAME'] = $value['realname'];
            $record['EMAIL'] = $value['email'];
            $record['NUMBER'] = $number;
            $number++;

            $arrTmp = preg_split('/ /', $value['timestamp']);
            $arrTmp2 = preg_split('/-/',$arrTmp[0]);

            $ts = $arrTmp2[2].'.'.$arrTmp2[1].'.'.$arrTmp2[0].' '.mi18n("about").' ';
            $ts.= substr($arrTmp[1],0,5).' '.mi18n("clock");

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
                $arrTmp = explode(' ',$value['editedat']);

                $edittime = substr($arrTmp[1],0,5);

                $arrTmp2 = explode('-',$arrTmp[0]);
                $editdate = $arrTmp2[2].'.'.$arrTmp2[1].'.'.$arrTmp2[0];

                $tmp = mi18n("articleWasEditAt");

                $edit_information = sprintf($tmp, $editdate, $edittime, $value['editedby']);

                if ((trim($_REQUEST['email']) != '' && trim($_REQUEST['realname']) != '' && trim($_REQUEST['forum']) != '') ||$edit) {
                   $record['EDIT_INFORMATION'] = "<br /><br /><em>$edit_information</em>";
                } else {
                    $record['EDIT_INFORMATION'] = "";
                }
            } else {
                $record['EDIT_INFORMATION'] = "";
            }

            if ($bAllowDeleting || $edit) {
                $path = $cfg['path']['contenido_fullhtml'].'external/backendedit/';

                $deletelink = "<a href='".$path."front_content.php?changeview=edit&action=con_editart&client=$client&lang=$lang&idart=$idart&contenido=$contenido&idcat=$idcat&user_forum_action=delete_forum&user_forum_delete_id=".$key."'>".mi18n("delete")."</a>";
                $editlink = "<a href='".$path."front_content.php?changeview=edit&userid=$userid&deleting=$bAllowDeleting&action=con_editart&client=$client&lang=$lang&idart=$idart&contenido=$contenido&idcat=$idcat&user_forum_action=edit_forum&user_forum_edit_id=".$key."'>".mi18n("edit")."</a>";

                $record['DELETELINK'] = $deletelink;
                $record['EDITLINK'] = "";

            } else {
                $record['DELETELINK'] = "";
                $record['EDITLINK'] = "";
            }

            $record['REPLY'] = sprintf($reply_forum_link, $key);
            $record['REPLY_QUOTE'] = sprintf($reply_quote_forum_link, $key, $key);
            $record['LIKE'] = sprintf($like_forum_link, $key, $value['like']);
            $record['DISLIKE'] = sprintf($dislike_forum_link, $key, $value['dislike']);
            $record['FROM'] = mi18n("from");
            $record['OPINION'] = mi18n("sameOpinion");
            $record['LIKE_COUNT'] = $value['like'];
            $record['DISLIKE_COUNT'] = $value['dislike'];
            $record['PADDING'] = $value['level']*20;

            array_push($tplData, $record);
        }

        $tpl->assign( 'POSTS', $tplData);

        $sTemp = mi18n("showHideArticles");
        $sTemp = str_replace('___', count($arrUserforum), $sTemp);

        if ($bAllowNewforum) {
            $link = $new_forum_link;

            $tplOptionList = new cTemplate();
            $tplOptionList->set('s', 'SHOW_forum', $sTemp);

            $tpl->assign( 'SHOW_FORUM_OPTION', $tplOptionList->generate('templates/user_forum_option_list.tpl', 1));

            $tpl->assign( 'LINK_NEW_FORUM', "<br />".$link."<br />");

        } else {
            $tpl->assign( 'LINK_NEW_FORUM', mi18n("noPosibleInputForArticle"));
        }

        $tpl->assign( 'NUM_FORUM', count($arrUserforum));

        $tpl->display('user_forum_list.tpl');
    }
}

function getExistingforum($id_cat, $id_art, $id_lang) {

    global $cfg;

    $db = cRegistry::getDb();

    $query = "SELECT * FROM ".$cfg['tab']['phplib_auth_user_md5'];

    $db->query($query);

    $arrUsers = array();

    while ($db->next_record()) {
        $arrUsers[$db->f('user_id')]['email'] = $db->f('email');
        $arrUsers[$db->f('user_id')]['realname'] = $db->f('realname');
    }

    $arrforum = array();
    getTreeLevel($id_cat, $id_art, $id_lang, $arrUsers, $arrforum);

    $result = array();
    normalizeArray($arrforum, $result);
    return $result ;
}

function normalizeArray($arrforum, &$result, $level = 0) {
    if (is_array($arrforum)) {
        foreach($arrforum as $key => $value) {
            $value['level'] = $level;
            unset($value['children']);
            $result[$key] = $value;
            normalizeArray($arrforum[$key]['children'], $result, $level+1);
        }
    }
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

        $arrforum[$db->f('id_user_forum')]['forum'] = str_replace(chr(13).chr(10), '<br />', $db->f('forum'));
        $arrforum[$db->f('id_user_forum')]['forum_quote'] = str_replace(chr(13).chr(10), '<br />', $db->f('forum_quote'));
        $arrforum[$db->f('id_user_forum')]['timestamp'] = $db->f('timestamp');
        $arrforum[$db->f('id_user_forum')]['like'] = $db->f('like');
        $arrforum[$db->f('id_user_forum')]['dislike'] = $db->f('dislike');

        $arrforum[$db->f('id_user_forum')]['editedat'] = $db->f('editedat');
        $arrforum[$db->f('id_user_forum')]['editedby'] = $db->f('editedby');

        getTreeLevel($id_cat, $id_art, $id_lang, $arrUsers, $arrforum[$db->f('id_user_forum')]['children'], $db->f('id_user_forum'));
    }
}

function deleteUserForum($id_forum) {
    $id_forum = (int) $id_forum;
    if ($id_forum > 0) {
        $db = cRegistry::getDb();
        $query = "SELECT * FROM con_pi_user_forum WHERE id_user_forum_parent = ".mysql_real_escape_string($id_forum);
        $db->query($query);
        while ($db->next_record()) {
            deleteUserForum($db->f('id_user_forum'));
        }

        $query = "DELETE FROM con_pi_user_forum WHERE id_user_forum = ".mysql_real_escape_string($id_forum)." LIMIT 1";

        $db->query($query);
    }
}
?>
