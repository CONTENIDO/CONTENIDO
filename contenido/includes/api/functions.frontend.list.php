<?php

/**
 * This file contains the frontend list class.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class FrontendList for scrollable frontend lists.
 */
class FrontendList
{

    /**
     * Wrap for table start.
     *
     * @var string
     */
    protected $_startwrap;

    /**
     * Wrap for table end.
     *
     * @var string
     */
    protected $_endwrap;

    /**
     * Wrap for a single item.
     *
     * @var string
     */
    protected $_itemwrap;

    /**
     * Data container.
     *
     * @var array
     */
    protected $_data = [];

    /**
     * Number of records displayed per page.
     *
     * @var int
     */
    protected $_resultsPerPage = 0;

    /**
     * Start page.
     *
     * @var int
     */
    protected $_listStart = 1;

    /**
     * Constructor to create an instance of this class.
     *
     * The placeholder for item wraps are the same as for sprintf.
     * See the documentation for sprintf.
     *
     * Caution: Make sure that percentage signs are written as %%.
     *
     * @param string $startwrap
     *         Wrap for the list start
     * @param string $endwrap
     *         Wrap for the list end
     * @param string $itemwrap
     *         Wrap for a single item
     */
    public function __construct($startwrap, $endwrap, $itemwrap)
    {
        $this->_startwrap = $startwrap;
        $this->_endwrap = $endwrap;
        $this->_itemwrap = $itemwrap;
    }

    /**
     * Sets data.
     *
     * Note: This function eats as many parameters as you specify.
     *
     * Example:
     * $obj->setData(0, "foo", "bar");
     *
     * Make sure that the amount of parameters stays the same for all
     * setData calls in a single object.
     *
     * @param int $index
     *         Numeric index
     * @param ... Additional parameters (data)
     * @SuppressWarnings docBlocks
     */
    public function setData($index)
    {
        $numArgs = func_num_args();

        for ($i = 1; $i < $numArgs; $i++) {
            $this->_data[$index][$i] = func_get_arg($i);
        }
    }

    /**
     * Sets the number of records per page.
     *
     * @param int $resultsPerPage
     *         Amount of records per page
     */
    public function setResultsPerPage($resultsPerPage)
    {
        $this->_resultsPerPage = $resultsPerPage;
    }

    /**
     * Sets the starting page number.
     *
     * @param int $listStart
     *         Page number on which the list display starts
     */
    public function setListStart($listStart)
    {
        $this->_listStart = cSecurity::toInteger($listStart);
    }

    /**
     * Returns the current page.
     *
     * @return int
     *         Current page number
     */
    public function getCurrentPage(): int
    {
        if ($this->_resultsPerPage == 0) {
            return 1;
        }

        return $this->_listStart;
    }

    /**
     * Returns the amount of pages.
     *
     * @return int
     *         Amount of pages
     */
    public function getNumPages(): int
    {
        return ceil(count($this->_data) / $this->_resultsPerPage);
    }

    /**
     * Sorts the list by a given field and a given order.
     *
     * @param string $field
     *         name of field to sort for
     * @param int $order
     *         Sort order (see php's sort documentation)
     *         one of SORT_ASC, SORT_DESC, SORT_REGULAR, SORT_NUMERIC, SORT_STRING
     */
    public function sort($field, $order)
    {
        $this->_data = cArray::csort($this->_data, "$field", $order);
    }

    /**
     * Field converting facility.
     * Needs to be overridden in the child class to work properly.
     *
     * @param int $field
     *         Field index
     * @param mixed $value
     *         Field value
     * @return mixed
     */
    protected function convert($field, $value)
    {
        return $value;
    }

    /**
     * Outputs or optionally returns
     *
     * @param bool $return
     *         if true, returns the list
     * @return string|void
     */
    public function output($return = false)
    {
        $output = $this->_startwrap;

        $currentpage = $this->getCurrentPage();

        $itemstart = (($currentpage - 1) * $this->_resultsPerPage) + 1;

        if ($this->_resultsPerPage == 0) {
            $itemend = count($this->_data) - ($itemstart - 1);
        } else {
            $itemend = $currentpage * $this->_resultsPerPage;
        }

        if ($itemend > count($this->_data)) {
            $itemend = count($this->_data);
        }

        for ($i = $itemstart; $i < $itemend + 1; $i++) {
            $currentPos = $i - 1;
            if (is_array($this->_data[$currentPos])) {
                $items = "";
                foreach ($this->_data[$currentPos] as $key => $value) {
                    $items .= ", '" . addslashes($this->convert($key, $value)) . "'";
                }

                $itemWrap = str_replace('{LIST_ITEM_POS}', $currentPos, $this->_itemwrap);
                $execute = '$output .= sprintf($itemWrap ' . $items . ');';
                eval($execute);
            }
        }

        $output .= $this->_endwrap;

        $output = stripslashes($output);

        if ($return) {
            return $output;
        } else {
            echo $output;
        }
    }

}
