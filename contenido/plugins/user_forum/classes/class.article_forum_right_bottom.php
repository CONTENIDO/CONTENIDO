<?php
class ArticleForumRightBottom extends cGuiPage {

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

            $this->getTreeLevel($id_cat, $id_art, $id_lang, $arrUsers, $arrforum[$db->f('id_user_forum')]['children'], $db->f('id_user_forum'));
        }
    }

    function getMenu(&$result) {

        $maxWidth = $this->getMaxLevel($result);
        $maxHeight = count($result);

        $testet = new cHTMLContentElement();
        $testet->setID("Content");

        $table = new cHTMLTable();

        $table->updateAttributes(array("class" => "generic", "cellspacing" => "0", "cellpadding" => "2"));




        $menu = new cGuiMenu();
        $cfg = cRegistry::getConfig();
        $client = cRegistry::getClientId();
        $lang = cRegistry::getLanguageId();


        foreach ($result as $key => $cont) {


            // echo $cont['forum'];
            $set = false;
            // echo ($this->_res[1]['forum']);
            if ($cont['level'] == 0) {
                $this->appendContent($table);
                $table = new cHTMLTable();
                $table->updateAttributes(array("class" => "generic", "cellspacing" => "0", "cellpadding" => "2"));
            }
            $tr = new cHTMLTableRow();
            $tr->updateAttributes(array("class" => "textw_medium"));


            for ($i = 0; $i < $maxWidth; $i++) {


                if ($cont['level'] == $i && !$set) {

                    //build buttons
                    $del = new cHTMLButton("del");
                    $del->setImageSource($cfg['path']['images'] . "delete.gif");
                    $del->setMode('image');
                    $save = new cHTMLButton("save");
                    $save->setMode('image');
                    $save->setImageSource($cfg['path']['images'] . "but_ok.gif");
                    $edit = new cHTMLButton("edit");
                    $edit->setImageSource($cfg['path']['images'] . "but_back.gif");
                    $edit->setMode('image');


                    $td = new cHTMLTableData( $edit->render(). $save->render(). $del->render()  ."<br>" . "User : " . $cont['realname'] . "<br>" . "Text : " . $cont['forum']);
                    $tr->appendContent($td);
                    $set = true;
                } else {
                    $td = new cHTMLTableData("");
                    $tr->appendContent($td);
                }
            }
            $table->appendContent($tr);
        }

        //$testet->appendContent($table);
        $this->appendContent($table);
        //$this->appendContent($testet);
       // $this->render();
        return $this;
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