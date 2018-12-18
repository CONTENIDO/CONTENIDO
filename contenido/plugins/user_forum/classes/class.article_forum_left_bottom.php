<?php
/**
 * This file contains the class for visualisation and interactions in the left
 * frame.
 *
 * @package Plugin
 * @subpackage UserForum
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
        parent::__construct('left_bottom', 'userforum');
        $this->_collection = new ArticleForumCollection();
        $this->addScript('location.js');
        $this->addStyle('right_bottom.css');
    }

    protected function getMenu() {
        $arts = new ArticleForumCollection();
        $result = $arts->getAllCommentedArticles();
        if (count($result) == 0) {
            echo UserForum::i18n("NOENTRY");
        }

        $cfg = cRegistry::getConfig();

        // get all forms of current client in current language
        $forms = $arts->getAllCommentedArticles();

        if (false === $forms) {
            return '';
        }

        global $area;

        $menu = new cGuiMenu();
        for ($i = 0; $i < count($forms); $i++) {

            $formName = $result[$i]['title'];
            $menu->setTitle($i, $formName);

            // add 'show form' link
            $link = new cHTMLLink();

            $link->setCLink($area, 4, 'show_form');
            $link->setTargetFrame('right_bottom');
            $link->setClass('linktext');
            $link->setCustom('idart', $result[$i]['idart']);
            $link->setCustom('idcat', $result[$i]['idcat']);
            $link->setContent($formName);
            $menu->setLink($i, $link);

            $link = new cHTMLLink();

            $arg = $result[$i]['idart'];
            $message = UserForum::i18n('ALLDELETEFROMCAT');
            $link->setLink('javascript:void(0)');
            $link->setAttribute("onclick", 'Con.showConfirmation(&quot;' . $message . '&quot;, function(){ deleteArticlesByIdLeft(' . $arg . '); }); return false;');
            $link->setImage($cfg['path']['images'] . 'delete.gif');
            $link->setAlt($message);
            $menu->setActions($i, 'delete', $link);
        }

        return $menu;
    }

    public function receiveData(&$get) {
        if ($_GET['action'] === 'delete_form') {
            $this->_collection->deleteAllCommentsById($get['idart']);
        }

        return $this->getMenu();
    }

}

?>