<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Foldable pager for menus
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.2
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created 2005-05-11
 *
 *   $Id: class.pager.php 2379 2012-06-22 21:00:16Z xmurrix $
 * }}
 *
 */
if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * cPager
 * Basic pager class without presentation logic
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cPager
{
    /**
     * Amount of items
     * @private integer
     * @access private
     */
    private $_items;

    /**
     * Item padding (before and after the current item)
     * @private integer
     * @access private
     */
    private $_itemPadding;

    /**
     * Items on the left side
     * @private integer
     * @access private
     */
    private $_previousItems;

    /**
     * Items on the right side
     * @private integer
     * @access private
     */
    private $_nextItems;

    /**
     * Current page
     * @private integer
     * @access private
     */
    private $_currentPage;

    /**
     * Items per page
     * @private integer
     * @access private
     */
    private $_itemsPerPage;

    /**
     * Constructor Function
     * Initializes the pager
     *
     * @param $items         int Amount of items
     * @param $itemsPerPage int Items displayed per page
     * @param $currentPage    int Defines the current page
     */
    public function __construct($items, $itemsPerPage, $currentPage)
    {
        $this->_items = $items;
        $this->_itemsPerPage = $itemsPerPage;
        $this->_currentPage = $currentPage;

        // Default values.
        $this->_itemPadding = 2;
        $this->_previousItems = 2;
        $this->_nextItems = 2;
    }

    /**
     * Returns the current page
     */
    public function getCurrentPage() {
        return $this->_currentPage;
    }

    /**
     * Returns if the currentPage pointer is the first page.
     *
     * @return boolean True if we're on the first page.
     */
    public function isFirstPage() {
        if ($this->_currentPage == 1) {
            return true;
        }

        return false;
    }

    /**
     * Returns if the currentPage pointer is the last page.
     *
     * @return boolean True if we're on the last page.
     */
    public function isLastPage() {
        if ($this->_currentPage == $this->getMaxPages()) {
            return true;
        }

        return false;
    }

    /**
     * Returns the amount of pages.
     *
     * @return int Page count
     */
    public function getMaxPages() {
        if ($this->_items == 0){
            return 1;
        } else if ($this->_itemsPerPage == 0) {
            return 1;
        } else {
            return (ceil($this->_items / $this->_itemsPerPage));
        }
    }

    /**
     * Returns an array with the pager structure.
     *
     * Array format:
     * Key  : Page Number
     * Value: | for "...", "current" for the current item, page number otherwise
     *
     * @return array Pager structure
     */
    public function getPagesInRange() {
        $items = array();

        $maxPages = $this->getMaxPages();

        if (($this->_itemPadding * 3) + $this->_previousItems + $this->_nextItems > $maxPages) {
            // Disable item padding
            for ($i = 1; $i < $this->getMaxPages() + 1; $i++) {
                $items[$i] = $i;
            }
        } else {
            for ($i = 1; $i < $this->_previousItems+1; $i++) {
                if ($i <= $maxPages && $i >= 1) {
                    $items[$i] = $i;
                }

                if ($i+1 <= $maxPages && $i >= 2) {
                    $items[$i+1] = "|";
                }
            }

            for ($i = $this->_currentPage - $this->_itemPadding; $i< $this->_currentPage + $this->_itemPadding + 1; $i++) {
                if ($i <= $maxPages && $i >= 1) {
                    $items[$i] = $i;
                }

                if ($i+1 <= $maxPages && $i >= 2) {
                    $items[$i+1] = "|";
                }
            }

            for ($i=($this->getMaxPages()-$this->_nextItems)+1; $i < $this->getMaxPages()+1; $i++) {
                if ($i <= $maxPages && $i >= 2) {
                    $items[$i] = $i;
                }
            }
        }

        $items[$this->_currentPage] = 'current';

        return ($items);
    }

}