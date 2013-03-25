<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Foldable pager for menus
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.2
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

class cGuiObjectPager extends cGuiFoldingRow {

    public $_pagerLink;
    public $_parameterToAdd;

    /**
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
            return false;
        }
        $this->_cPager = new cPager($items, $itemsperpage, $currentpage);
        $this->_pagerLink = $link;
        $this->_parameterToAdd = $parameterToAdd;
    }

    function render($bContentOnly = false) {
        #Do not display Page navigation if there is only one Page and we are not in newsletter section
        if ($this->_cPager->getMaxPages() == 1) {
            $this->_headerRow->setStyle("display:none");
            $this->_contentRow->setStyle("display:none");
        }

        $items = $this->_cPager->getPagesInRange();
        $link = $this->_pagerLink;

        if (!$this->_cPager->isFirstPage()) {
            $img = new cHTMLImage("images/paging/first.gif");

            $link->setAlt(i18n("First page"));
            $link->setContent($img);
            $link->setCustom($this->_parameterToAdd, 1);
            $output .= $link->render();
            $output .= " ";

            $img = new cHTMLImage("images/paging/previous.gif");
            $link->setAlt(i18n("Previous page"));
            $link->setContent($img);

            $link->setCustom($this->_parameterToAdd, $this->_cPager->getCurrentPage() - 1);

            $output .= $link->render();
            $output .= " ";
        } else {
            $output .= '<img src="images/spacer.gif" width="8"> ';
            $output .= '<img src="images/spacer.gif" width="8">';
        }

        foreach ($items as $key => $item) {
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
            $link->setAlt(i18n("Next page"));
            $link->setContent($img);
            $link->setCustom($this->_parameterToAdd, $this->_cPager->getCurrentPage() + 1);

            $output .= $link->render();
            $output .= " ";

            $img = new cHTMLImage("images/paging/last.gif");

            $link->setCustom($this->_parameterToAdd, $this->_cPager->getMaxPages());
            $link->setAlt(i18n("Last page"));
            $link->setContent($img);

            $output .= $link->render();
            $output .= " ";
        } else {
            $output .= '<img src="images/spacer.gif" width="8"> ';
            $output .= '<img src="images/spacer.gif" width="8">';
        }

        $this->_contentData->setAlignment("center");
        $this->_contentData->setClass("foldingrow_content");

        #Do not display Page navigation if there is only one Page and we are not in newsletter section
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

}
