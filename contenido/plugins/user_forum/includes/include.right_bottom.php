<?php



print_r($_POST);


if (isset($_GET['idart']) && $_GET['idart'] !== NULL) {

    $idart = $_GET['idart'];
    $idcat = $_GET['idcat'];

    if (isset($_GET['id_user_forum']) && isset($_GET['action'])) {

        $action = $_GET["action"];
        switch ($action) {

            case 'delete':
                echo 'delete';
                break;

            case 'save':
                echo 'save';
                break;

            case 'edit':
                echo 'edit';
                break;

            default:
                throw new Exception('$_GET["action"] type ' . $_GET["action"] . ' not implemented');
        }
    }

    $cfg = cRegistry::getConfig();
    $client = cRegistry::getClientId();
    $lang = cRegistry::getLanguageId();

    $right = new ArticleForumRightBottom("content");
    $test = $right->getExistingforum($idcat, $idart, $lang);
    $test->render();
}

/*
 * for ($i = 0; $i < count($text); $i++) { $idform = " "; //
 * $result[$i]['title']; $formName = $text[$i]['id_user_forum']; // $link = new
 * cHTMLLink(); //
 * main.php?area=form&frame=4&action=show_form&idform=6&contenido=75ijqvmgbgc2dt61gt8pfcmg01
 * // $link->setCLink($area, 4, 'show_form'); //
 * $link->setTargetFrame('right_bottom'); // $link->setCustom('idart',
 * $result[$i]['idart']); // $link->setContent('name ' . $formName); //
 * $menu->setLink($idform, $link); // $menu->setTitle($idform, $formName); //
 * $test->display(); $edit = new cHTMLLink(); $edit->setCLink($area, 4, 'edit');
 * $edit->setTargetFrame('left_bottom'); $edit->setCustom('idart', $idart);
 * $edit->setClass('pifa-icon-delete-formd'); $deleteForm =
 * Pifa::i18n('DELETE_FORM'); // $edit->setAlt(""); $edit->setContent('<img
 * src="' . $cfg['path']['images'] . 'editieren.gif" title="' . $deleteForm . '"
 * alt="' . $deleteForm . '">'); // $menu->setLink($idform,$edit); //
 * $menu->setActions($idform, 'edit', $edit); $save = new cHTMLLink();
 * $save->setCLink($area, 4, 'edit'); $save->setTargetFrame('left_bottom');
 * $save->setCustom('idart', $idart);
 * $save->setClass('pifa-icon-delete-formdd'); $deleteForm =
 * Pifa::i18n('DELETE_FORM'); // $edit->setAlt(""); $save->setContent('<img
 * src="' . $cfg['path/// artikel Online Status , set offline, online bla !!!!
 * $delete = new cHTMLLink(); $delete->setCLink($area, 4, 'delete_form');
 * $delete->setTargetFrame('left_bottom'); $delete->setCustom('idart', $idart);
 * $delete->setClass('pifa-icon-delete-form'); $deleteForm =
 * Pifa::i18n('DELETE_FORM'); $delete->setAlt($deleteForm);
 * $delete->setContent('<img src="' . $cfg['path']['images'] . 'delete.gif"
 * title="' . $deleteForm . '" alt="' . $deleteForm . '">');
 * $menu->setLink($idform, $delete); $menu->setActions($idform, 'delete',
 * $delete); // $tt->appendContent($menu);
 */
// function getMaxLevel(&$forum_content) {
// $max = 0;
// foreach ($forum_content as $key => $content) {
// if ($content['level'] > $max) {
// $max = $content['level'];
// }
// }
// return $max;
// }

// function getMenu(&$forum_content) {
// echo '<pre>';
// // print_r($forum_content);
// echo '</pre>';

// $maxWidth = getMaxLevel($forum_content);
// $maxHeight = count($forum_content);
// // var_dump($max);

// $testet = new cHTMLContentElement();

// $table = new cHTMLTable();

// //$table->setBorder(1);
// // $test = array();

// // $row = new cHTMLTableRow();

// foreach ($forum_content as $key => $content) {

// // if($content['level']=== 0)
// $set = false;

// if($content['level'] == 0)
// {
// $testet->appendContent($table);
// $table = new cHTMLTable();
// }
// $tr = new cHTMLTableRow();
// for ($i = 0; $i < $maxWidth; $i++) {
// if ($content['level'] == $i && !$set) {
// $td = new cHTMLTableData( "User :" .$content['realname'] ."<br>". "Text : "
// .$content['forum']);
// $tr->appendContent($td);
// $set = true;
// } else {
// $td = new cHTMLTableData("");
// $tr->appendContent($td);
// }

// }
// $table->appendContent($tr);
// // $testet->appendContent($table);

// // $row = new cHTMLTableData();
// // $row->setContent($content['forum']);
// // $table->setContent($content['forum']);
// // $table->setContent($row);

// // $table->setContent($tr);

// // if($forum_content['level']== )
// // {
// // echo $content['userid'];
// // echo $content['forum'];
// // echo $content['level'];
// // echo '<hr>';
// }
// $testet->appendContent($table);

// //$table->display();
// $testet->display();
// }

// function getExistingforum($id_cat, $id_art, $id_lang) {
// global $cfg;

// $db = cRegistry::getDb();

// $query = "SELECT * FROM " . $cfg['tab']['phplib_auth_user_md5'];

// $db->query($query);

// $arrUsers = array();

// while ($db->next_record()) {
// $arrUsers[$db->f('user_id')]['email'] = $db->f('email');
// $arrUsers[$db->f('user_id')]['realname'] = $db->f('realname');
// }

// $arrforum = array();
// getTreeLevel($id_cat, $id_art, $id_lang, $arrUsers, $arrforum);

// $result = array();
// normalizeArray($arrforum, $result);
// // echo '<pre>';
// // var_dump($result);
// // echo '</pre>';
// // return $result ;
// getMenu($result);
// }

// function normalizeArray($arrforum, &$result, $level = 0) {
// if (is_array($arrforum)) {
// foreach ($arrforum as $key => $value) {
// $value['level'] = $level;
// unset($value['children']);
// $result[$key] = $value;
// normalizeArray($arrforum[$key]['children'], $result, $level + 1);
// }
// }
// }

// function getTreeLevel($id_cat, $id_art, $id_lang, &$arrUsers, &$arrforum,
// $parent = 0) {
// $db = cRegistry::getDb();

// $query = "SELECT * FROM con_pi_user_forum WHERE (idart = $id_art) AND (idcat
// = $id_cat) AND (idlang = $id_lang) AND (id_user_forum_parent = $parent) ORDER
// BY timestamp DESC";

// $db->query($query);

// while ($db->next_record()) {
// $arrforum[$db->f('id_user_forum')]['userid'] = $db->f('userid');

// if (array_key_exists($db->f('userid'), $arrUsers)) {
// $arrforum[$db->f('id_user_forum')]['email'] =
// $arrUsers[$db->f('userid')]['email'];
// $arrforum[$db->f('id_user_forum')]['realname'] =
// $arrUsers[$db->f('userid')]['realname'];
// } else {
// $arrforum[$db->f('id_user_forum')]['email'] = $db->f('email');
// $arrforum[$db->f('id_user_forum')]['realname'] = $db->f('realname');
// }

// $arrforum[$db->f('id_user_forum')]['forum'] = str_replace(chr(13) . chr(10),
// '<br />', $db->f('forum'));
// $arrforum[$db->f('id_user_forum')]['forum_quote'] = str_replace(chr(13) .
// chr(10), '<br />', $db->f('forum_quote'));
// $arrforum[$db->f('id_user_forum')]['timestamp'] = $db->f('timestamp');
// $arrforum[$db->f('id_user_forum')]['like'] = $db->f('like');
// $arrforum[$db->f('id_user_forum')]['dislike'] = $db->f('dislike');

// $arrforum[$db->f('id_user_forum')]['editedat'] = $db->f('editedat');
// $arrforum[$db->f('id_user_forum')]['editedby'] = $db->f('editedby');

// getTreeLevel($id_cat, $id_art, $id_lang, $arrUsers,
// $arrforum[$db->f('id_user_forum')]['children'], $db->f('id_user_forum'));
// }
// }

// getExistingforum($idcat, $idart, 1);

// // outpout Commentwithout Parent

// echo '<pre>';
// print_r($text);
// print_r($subComments);
// echo '</pre>';
// $space = "&nbsp";
// foreach ($text as $key => $comment) {

// echo 'name : ' . $comment['realname'] . '<br>';
// echo ' email : ' . $comment['email'] . '<br>';
// echo ' text : ' . $comment['forum'] . '<br>';
// echo 'id : ' . $comment['id_user_forum'] . '<br>';
// echo '<hr>';

// $lastComm = $comment['id_user_forum'];

// foreach ($subComments as $key1 => $subComment) {

// // Subkategorie gefunden.
// // schauen ob subcomments weitere subcomments haben
// // letzter Kommentar ist elternknoten
// // while (!$end)
// // {
// // var_dump($lastComm);

// // ////// Mehrere Subkommentare auf selbe Parent möglich !!!!!!!!

// if ($lastComm == $subComment['id_user_forum_parent']) {
// $space .= "&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp";
// // echo "gefunden";
// // if($subComment['id_user_forum'] <$lastComm)
// // {

// // foreach ($subComments as $key => $subSubComment)
// // {
// // if($lastComm == $subSubComment['id_user_forum_parent'])
// // {
// // echo $space.'name : '.$subSubComment['realname']. '<br>';
// // echo $space.' email : ' .$subSubComment['email'] . '<br>';
// // echo $space.' text : ' . $subSubComment['forum'] . '<br>';
// // echo $space.' id : ' . $subSubComment['id_user_forum'] .
// // '<br>';
// // echo $space.'<hr>';
// // var_dump($lastComm);

// // }
// // }

// //var_dump($lastComm);

// echo $space . 'name : ' . $subComment['id_user_forum_parent'] . '<br>';
// echo $space . 'name : ' . $subComment['realname'] . '<br>';
// echo $space . ' email : ' . $subComment['email'] . '<br>';
// echo $space . ' text : ' . $subComment['forum'] . '<br>';
// echo $space . ' id : ' . $subComment['id_user_forum'] . '<br>';
// echo $space . '<hr>';
// // var_dump($lastComm);
// $temp = $subComment['id_user_forum_parent'];
// $check = false;

// for ($i = $key1 + 1; $i < count($subComments) - $i; $i++) {

// if ($subComments[$i]['id_user_forum_parent'] == $temp) {
// echo "MAthc";
// echo $space . 'name : ' . $subComments[$i]['id_user_forum_parent'] .
// '<br>';
// echo $space . 'name : ' . $subComments[$i]['realname'] . '<br>';
// echo $space . ' email : ' . $subComments[$i]['email'] . '<br>';
// echo $space . ' text : ' . $subComments[$i]['forum'] . '<br>';
// echo $space . ' id : ' . $subComments[$i]['id_user_forum'] . '<br>';
// echo $space . '<hr>';
// $temp = $subComments[$i]['id_user_forum'];
// $check = true;
// }
// }

// if ($check) {
// $lastComm = $temp;

// } else {
// $lastComm = $subComment['id_user_forum'];

// }
// }
// }
// }

?>