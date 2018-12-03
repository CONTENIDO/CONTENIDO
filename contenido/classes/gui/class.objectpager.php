<?php

/**
 * This file contains the foldable pager for menus GUI class.
 *
 * @package          Core
 * @subpackage       GUI
 *
 * @author           Timo Hummel
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class for foldable pager for menus.
 *
 * @package    Core
 * @subpackage GUI
 */
class cGuiObjectPager extends cGuiFoldingRow {

    /**
     *
     * @var cHTMLLink
     */
    public $_pagerLink;

    /**
     *
     * @var string
     */
    public $_parameterToAdd;

    /**
     * @var cPager
     */
    protected $_cPager;

    /**
     * Constructor to create an instance of this class.
     *
     * @param string    $uuid
     * @param int       $items
     *                      Amount of items
     * @param int       $itemsperpage
     *                      Items displayed per page
     * @param int       $currentpage
     *                      Defines the current page
     * @param cHTMLLink $link
     * @param string    $parameterToAdd
     * @param string    $id [optional]
     *
     * @throws cException if the given link is not an object
     */
    public function __construct($uuid, $items, $itemsperpage, $currentpage, $link, $parameterToAdd, $id = '') {
        if ((int) $currentpage == 0) {
            $currentpage = 1;
        }

        if ($id == '') {
            parent::__construct($uuid, i18n("Paging"));
        } else {
            parent::__construct($uuid, i18n("Paging"), $id);
        }

        if (!is_object($link)) {
            throw new cException('Parameter link is not an object');
        }
        
        $this->_cPager = new cPager($items, $itemsperpage, $currentpage);
        $this->_pagerLink = $link;
        $this->_parameterToAdd = $parameterToAdd;
    }

    /**
     *
     * @see cGuiFoldingRow::render()
     * @param bool $bContentOnly [optional]
     * @return string
     *         Generated markup
     */
    public function render($bContentOnly = false) {
        // Do not display Page navigation if there is only one Page
        // and we are not in newsletter section.
        if ($this->_cPager->getMaxPages() == 1) {
            $this->_headerRow->setStyle("display:none");
            $this->_contentRow->setStyle("display:none");
        }

        $items = $this->_cPager->getPagesInRange();

        $output = '';

        if (!$this->_cPager->isFirstPage()) {
            $img = new cHTMLImage("images/paging/first.gif");

            $link = $this->_getPagerLinkForNextUsage();
            $link->setAlt(i18n("First page"));
            $link->setContent($img);
            $link->setCustom($this->_parameterToAdd, 1);
            $output .= $link->render();
            $output .= " ";

            $img = new cHTMLImage("images/paging/previous.gif");
            $link = $this->_getPagerLinkForNextUsage();
            $link->setAlt(i18n("Previous page"));
            $link->setContent($img);

            $link->setCustom($this->_parameterToAdd, $this->_cPager->getCurrentPage() - 1);

            $output .= $link->render();
            $output .= " ";
        } else {
            $output .= '<img src="images/spacer.gif" alt="" width="8"> ';
            $output .= '<img src="images/spacer.gif" alt="" width="8">';
        }

        foreach ($items as $key => $item) {
            $link = $this->_getPagerLinkForNextUsage();
            $link->setContent($key);
            $link->setAlt(sprintf(i18n("Page %s"), $key));
            $link->setCustom($this->_parameterToAdd, $key);

            switch ($item) {
                case "|": $output .= "...";
                    break;
                case "current": $output .= '<span class="cpager_currentitem">' . $key . "</span>";
                    break;
                default: $output .= $link->render();
            }

            $output .= " ";
        }

        if (!$this->_cPager->isLastPage()) {
            $img = new cHTMLImage("images/paging/next.gif");
            $link = $this->_getPagerLinkForNextUsage();
            $link->setAlt(i18n("Next page"));
            $link->setContent($img);
            $link->setCustom($this->_parameterToAdd, $this->_cPager->getCurrentPage() + 1);

            $output .= $link->render();
            $output .= " ";

            $img = new cHTMLImage("images/paging/last.gif");

            $link = $this->_getPagerLinkForNextUsage();
            $link->setCustom($this->_parameterToAdd, $this->_cPager->getMaxPages());
            $link->setAlt(i18n("Last page"));
            $link->setContent($img);

            $output .= $link->render();
            $output .= " ";
        } else {
            $output .= '<img src="images/spacer.gif" alt="" width="8"> ';
            $output .= '<img src="images/spacer.gif" alt="" width="8">';
        }

        $this->_contentData->setAlignment("center");
        $this->_contentData->setClass("foldingrow_content");

        // Do not display Page navigation if there is only one Page
        // and we are not in newsletter section.
        if ($this->_cPager->getMaxPages() == 1) {
            $output = '';
        }

        $this->_contentData->setContent($output);

        if ($bContentOnly) {
            return $output;
        } else {
            return parent::render();
        }
    }

    /**
     * Resets the title attribute of the pager link property and returns the it for new usage
     * @return cHTMLLink
     */
    protected function _getPagerLinkForNextUsage() {
        $link = $this->_pagerLink;
        $link->removeAttribute("title");
        return $link;
    }
}
