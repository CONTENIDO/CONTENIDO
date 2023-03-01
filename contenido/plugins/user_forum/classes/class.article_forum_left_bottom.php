<?php

/**
 * This file contains the class for visualisation and interactions in the left
 * frame.
 *
 * @package    Plugin
 * @subpackage UserForum
 * @author     Claus Schunk
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains builds the content of the left frame
 *
 *
 * @package    Plugin
 * @subpackage UserForum
 */
class ArticleForumLeftBottom extends cGuiPage
{

    /**
     * @var ArticleForumCollection
     */
    protected $_collection;

    /**
     * @var cGuiMenu
     */
    protected $_guiMenu;

    /**
     * ArticleForumLeftBottom constructor.
     */
    public function __construct()
    {
        parent::__construct('left_bottom', 'userforum');
        $this->_collection = new ArticleForumCollection();
        $this->_guiMenu = new cGuiMenu();
        $this->addScript('location.js');
        $this->addStyle('right_bottom.css');
    }

    /**
     * Returns the menu for the user forum.
     *
     * @return cGuiMenu
     * @throws cDbException
     */
    protected function getMenu()
    {
        if (!$this->_guiMenu->hasItems()) {
            $this->_fillGuiMenu();
        }

        return $this->_guiMenu;
    }

    /**
     * @param $get
     *
     * @return cGuiMenu
     * @throws cDbException
     */
    public function receiveData(&$get) {
        return $this->getMenu();
    }

    /**
     * Loads the entries from the forum table and fills the gui menu.
     *
     * @return void
     * @throws cDbException
     */
    protected function _fillGuiMenu()
    {
        // Get all forms of current client in current language
        $arts = new ArticleForumCollection();
        $forms = $arts->getAllCommentedArticles();
        if (count($forms) === 0) {
            $cGuiNotification = new cGuiNotification();
            $notification = $cGuiNotification->returnNotification(
                cGuiNotification::LEVEL_INFO, UserForum::i18n('NOENTRY')
            );
            $this->_guiMenu->setId('-1', '-1');
            $this->_guiMenu->setLink('-1', '');
            $this->_guiMenu->setTitle('-1', new cHTMLSpan($notification));
            return;
        }

        $cfg = cRegistry::getConfig();

        $idart = cSecurity::toInteger(isset($_REQUEST['idart']) ?? '0');

        for ($i = 0; $i < count($forms); $i++) {
            // We use idart as id for the menu entry
            $id = cSecurity::toInteger($forms[$i]['idart']);

            $formName = $forms[$i]['title'];
            $this->_guiMenu->setId($id, $id);
            $this->_guiMenu->setTitle($id, $formName);

            if ($idart == $id) {
                $this->_guiMenu->setMarked($id);
            }

            // Add 'show form' link
            $link = new cHTMLLink();
            $link->setClass('show_item')
                ->setLink('javascript:void(0)')
                ->setAttribute('data-action', 'show_forum')
                ->setAttribute('data-idart', $forms[$i]['idart'])
                ->setAttribute('data-idcat', $forms[$i]['idcat']);
            $this->_guiMenu->setLink($id, $link);

            $link = new cHTMLLink();
            $deleteForm = UserForum::i18n('ALLDELETEFROMCAT');
            $link->setLink('javascript:void(0)')
                ->setAttribute('data-action', 'delete_forum')
                ->setAttribute('data-idart', $forms[$i]['idart'])
                ->setContent(cHTMLImage::img($cfg['path']['images'] . 'delete.gif', $deleteForm));
            $this->_guiMenu->setActions($id, 'delete', $link);
        }
    }

}
