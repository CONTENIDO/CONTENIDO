<?php
class ArticleForumLeftBottom extends cGuiPage {

    function __construct() {
        parent::__construct('left_bottom', 'articlelist');
    }


    public function getMenu() {
        $arts = new ArticleForumCollection();
        $result = $arts->getAllCommentedArticles();

        $tt = new cHTMLList();
        global $area;
        $ar = array();
        $cfg = cRegistry::getConfig();
        $client = cRegistry::getClientId();
        $lang = cRegistry::getLanguageId();

        // get all forms of current client in current language
        $forms = $arts->getAllCommentedArticles();

        if (false === $forms) {
            return '';
        }

        $menu = new cGuiMenu();
        for ($i = 0; $i < count($forms); $i++) {

            $res = $arts->getIdCat($result[$i]['idart']);
            $formName = $result[$i]['title'];

            $menu->setTitle("", $formName);

            // add 'show form' link
            $link = new cHTMLLink();
            $link->setCLink($area, 4, 'show_form');
            $link->setTargetFrame('right_bottom');
            $link->setCustom('idart', $result[$i]['idart']);
            $link->setCustom('idcat', $res[0]['idcat']);
            $link->setContent('name ' . $formName);
            $menu->setLink("", $link);

            // add 'delete' action
            $delete = new cHTMLLink();
            $delete->setCLink($area, 2, 'delete_form');
            $delete->setTargetFrame('left_bottom');
            $delete->setCustom('idart', $result[$i]['idart']);
            //$delete->setClass('pifa-icon-delete-form');
            $deleteForm = UserForum::i18n('DELETE_COMMENTS');
            $delete->setAlt($deleteForm);
            $delete->setContent('<img src="' . $cfg['path']['images'] . 'delete.gif" title="' . $deleteForm . '" alt="' . $deleteForm . '">');
            $menu->setActions("", 'delete', $delete);

            $tt->appendContent($menu);
        }

        return $tt;
    }

}

?>