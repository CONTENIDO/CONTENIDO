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
    /**
     * @var ArticleForumCollection
     */
    protected $_collection;

    /**
     * ArticleForumLeftBottom constructor.
     */
    public function __construct() {
        parent::__construct('left_bottom', 'userforum');
        $this->_collection = new ArticleForumCollection();
        $this->addScript('location.js');
        $this->addStyle('right_bottom.css');
    }

    /**
     * @return cGuiMenu|string
     * @throws cDbException
     */
    protected function getMenu() {
        $arts = new ArticleForumCollection();
        $result = $arts->getAllCommentedArticles();
        if (count($result) === 0) {
            echo UserForum::i18n("NOENTRY");
        }

        $cfg = cRegistry::getConfig();

        // get all forms of current client in current language
        $forms = $arts->getAllCommentedArticles();
        if (count($forms) === 0) {
            return '';
        }

        $idart = cSecurity::toInteger(isset($_REQUEST['idart']) ?? '0');

        $menu = new cGuiMenu();
        for ($i = 0; $i < count($forms); $i++) {
            // We use idart as id for the menu entry
            $id = cSecurity::toInteger($result[$i]['idart']);

            $formName = $result[$i]['title'];
            $menu->setId($id, $id);
            $menu->setTitle($id, $formName);

            if ($idart == $id) {
                $menu->setMarked($id);
            }

            // add 'show form' link
            $link = new cHTMLLink();
            $link->setClass('show_item')
                ->setLink('javascript:;')
                ->setAttribute('data-action', 'show_forum')
                ->setAttribute('data-idart', $result[$i]['idart'])
                ->setAttribute('data-idcat', $result[$i]['idcat']);
            $menu->setLink($id, $link);

            $link = new cHTMLLink();
            $deleteForm = UserForum::i18n('ALLDELETEFROMCAT');
            $link->setLink('javascript:;')
                ->setAttribute('data-action', 'delete_forum')
                ->setAttribute('data-idart', $result[$i]['idart'])
                ->setContent('<img class="vAlignMiddle" src="' . $cfg['path']['images'] . 'delete.gif" title="' . $deleteForm . '" alt="' . $deleteForm . '">');
            $menu->setActions($id, 'delete', $link);
        }

        return $menu;
    }

    /**
     * @param $get
     *
     * @return cGuiMenu|string
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function receiveData(&$get) {
        return $this->getMenu();
    }

}

?>