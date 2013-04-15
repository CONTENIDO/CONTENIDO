<?php
/**
 * This file contains the class for visualisation and interactions in the left frame.
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
 * This class contains builds the content of the left frame
 *
 *
 * @package Plugin
 * @subpackage UserForum
 */
class ArticleForumLeftBottom extends cGuiPage {

    protected $_collection;

    public function __construct() {
        parent::__construct('left_bottom', 'articlelist');
        $this->_collection = new ArticleForumCollection();
        $this->addScript('../plugins/user_forum/scripts/location.js');
        $this->addStyle('../plugins/user_forum/styles/right_bottom.css');
    }

    protected function getMenu() {
        $arts = new ArticleForumCollection();
        $result = $arts->getAllCommentedArticles();
        if (count($result) == 0) {
            echo UserForum::i18n("NOENTRY");
        }

        // $this->addScript('../scripts/location.js');
        $list = new cHTMLList();
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

        $table = new cHTMLTable();
        $table->setCellPadding('100px');
        global $area;
        $table->updateAttributes(array(
            "class" => "generic",
            "cellspacing" => "0",
            "cellpadding" => "2"
        ));

        $tr = new cHTMLTableRow();
        $thleft = new cHTMLTableHead();

        $thright = new cHTMLTableHead();
        $thleft->setContent(i18n("ARTICLES", "user_forum"));
        // $thleft->setStyle('widht:20px');
        $thleft->setStyle('text-align: center');
        $thleft->setAttribute('valign', 'top');
        $thright->setContent(i18n("ACTIONS", "user_forum"));
        $thright->setStyle('widht:20px');
        $thright->setStyle('text-align: center');
        $thright->setAttribute('valign', 'center');
        $tr->appendContent($thleft);
        $tr->appendContent($thright);
        $table->appendContent($tr);
        // $tr->appendContent($th);

        $menu = new cGuiMenu();
        for ($i = 0; $i < count($forms); $i++) {

            $tr = new cHTMLTableRow();

            $tdname = new cHTMLTableData();
            $tdname->setStyle('text-align: center');
            $tdlink = new cHTMLTableData();

            $res = $result[$i]['idart'];

            $formName = $result[$i]['title'];
            $menu->setTitle("", $formName);

            // add 'show form' link
            $link = new cHTMLLink();
            $link->setCLink($area, 4, 'show_form');
            $link->setTargetFrame('right_bottom');
            // $link->setStyle('text-align: center');
            $link->setClass('linktext');
            $link->setCustom('idart', $result[$i]['idart']);
            $link->setCustom('idcat', $result[$i]['idcat']);
            $link->setContent('' . $formName);
            $menu->setLink("", $link);

            $arg = $result[$i]['idart'];
            $message = UserForum::i18n('ALLDELETEFROMCAT');

            $deletebutton = '<a title="' . $result[$i]['title'] . '" href="javascript:void(0)"
            onclick="showConfirmation(&quot;' . $message . '&quot;, function(){deleteArticlesByIdLeft(' . $arg . ');});
            return false;"><img class="links" src="' . $cfg['path']['images'] . 'delete.gif" border="0" title="' . $message . '" alt="' . $message . '"></a>';

            $tdname->appendContent($link);
            $tdlink->appendContent($deletebutton);
            $tr->appendContent($tdname);
            $tr->appendContent($tdlink);
            $table->appendContent($tr);
        }
        if (count($forms) > 0) {
            return $table;
        } else {

            return new cHTMLTable();
        }
    }

    public function receiveData(&$get) {
        if ($_GET['action'] === 'delete_form') {
            // print_r($_GET['idart']);
            $this->_collection->deleteAllCommentsById($get['idart']);
        }

        $this->appendContent($this->getMenu());
    }

}

?>