<?php
/**
 * This file contains the the pager class.
 *
 * @package Core
 * @subpackage GUI
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * cPager
 * Basic pager class without presentation logic
 *
 * @package Core
 * @subpackage GUI
 */
class cPager {

    /**
     * Amount of items
     *
     * @var int
     */
    private $_items;

    /**
     * Item padding (before and after the current item)
     *
     * @var int
     */
    private $_itemPadding;

    /**
     * Items on the left side
     *
     * @var int
     */
    private $_previousItems;

    /**
     * Items on the right side
     *
     * @var int
     */
    private $_nextItems;

    /**
     * Current page
     *
     * @var int
     */
    private $_currentPage;

    /**
     * Items per page
     *
     * @var int
     */
    private $_itemsPerPage;

    /**
     * Constructor Function
     * Initializes the pager
     *
     * @param int $items
     *         Amount of items
     * @param int $itemsPerPage
     *         Items displayed per page
     * @param int $currentPage
     *         Defines the current page
     */
    public function __construct($items, $itemsPerPage, $currentPage) {
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
     *
     * @return int
     */
    public function getCurrentPage() {
        return $this->_currentPage;
    }

    /**
     * Returns if the currentPage pointer is the first page.
     *
     * @return bool
     *         True if we're on the first page.
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
     * @return bool
     *         True if we're on the last page.
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
     * @return int
     *         Page count
     */
    public function getMaxPages() {
        if ($this->_items == 0) {
            return 1;
        } else if ($this->_itemsPerPage == 0) {
            return 1;
        } else {
            return ceil($this->_items / $this->_itemsPerPage);
        }
    }

    /**
     * Returns an array with the pager structure.
     *
     * Array format:
     * Key : Page Number
     * Value: | for "...", "current" for the current item, page number otherwise
     *
     * @return array
     *         Pager structure
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
            for ($i = 1; $i < $this->_previousItems + 1; $i++) {
                if ($i <= $maxPages && $i >= 1) {
                    $items[$i] = $i;
                }

                if ($i + 1 <= $maxPages && $i >= 2) {
                    $items[$i + 1] = "|";
                }
            }

            for ($i = $this->_currentPage - $this->_itemPadding; $i < $this->_currentPage + $this->_itemPadding + 1; $i++) {
                if ($i <= $maxPages && $i >= 1) {
                    $items[$i] = $i;
                }

                if ($i + 1 <= $maxPages && $i >= 2) {
                    $items[$i + 1] = "|";
                }
            }

            for ($i = ($this->getMaxPages() - $this->_nextItems) + 1; $i < $this->getMaxPages() + 1; $i++) {
                if ($i <= $maxPages && $i >= 2) {
                    $items[$i] = $i;
                }
            }
        }

        $items[$this->_currentPage] = 'current';

        return $items;
    }
}