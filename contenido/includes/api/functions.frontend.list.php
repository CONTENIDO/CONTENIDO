<?php

/**
 * This file contains the frontend list class.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Timo Hummel
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class FrontendList for scrollable frontend lists.
 */
class FrontendList {

    /**
     * Wrap for table start.
     *
     * @var string
     */
    var $startwrap;

    /**
     * Wrap for table end.
     *
     * @var string
     */
    var $endwrap;

    /**
     * Wrap for a single item.
     *
     * @var string
     */
    var $itemwrap;

    /**
     * Data container.
     *
     * @var array
     */
    var $data = array();

    /**
     * Number of records displayed per page.
     *
     * @var int
     */
    var $resultsPerPage = 0;

    /**
     * Start page.
     *
     * @var int
     */
    var $listStart = 1;

    /**
     * Creates a new FrontendList object.
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
    function FrontendList($startwrap, $endwrap, $itemwrap) {
        $this->startwrap = $startwrap;
        $this->endwrap = $endwrap;
        $this->itemwrap = $itemwrap;
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
    function setData($index) {
        $numargs = func_num_args();

        for ($i = 1; $i < $numargs; $i++) {
            $this->data[$index][$i] = func_get_arg($i);
        }
    }

    /**
     * Sets the number of records per page.
     *
     * @param int $resultsPerPage
     *         Amount of records per page
     */
    function setResultsPerPage($resultsPerPage) {
        $this->resultsPerPage = $resultsPerPage;
    }

    /**
     * Sets the starting page number.
     *
     * @param int $listStart
     *         Page number on which the list display starts
     */
    function setListStart($listStart) {
        $this->listStart = $listStart;
    }

    /**
     * Returns the current page.
     *
     * @return int
     *         Current page number
     */
    function getCurrentPage() {
        if ($this->resultsPerPage == 0) {
            return 1;
        }

        return $this->listStart;
    }

    /**
     * Returns the amount of pages.
     *
     * @return int
     *         Amount of pages
     */
    function getNumPages() {
        return ceil(count($this->data) / $this->resultsPerPage);
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
    function sort($field, $order) {
        $this->data = cArray::csort($this->data, "$field", $order);
    }

    /**
     * Field converting facility.
     * Needs to be overridden in the child class to work properbly.
     *
     * @param int $field
     *         Field index
     * @param mixed $value
     *         Field value
     * @return mixed
     */
    function convert($field, $value) {
        return $value;
    }

    /**
     * Outputs or optionally returns
     *
     * @param bool $return
     *         if true, returns the list
     * @return string
     */
    function output($return = false) {
        $output = $this->startwrap;

        $currentpage = $this->getCurrentPage();

        $itemstart = (($currentpage - 1) * $this->resultsPerPage) + 1;

        if ($this->resultsPerPage == 0) {
            $itemend = count($this->data) - ($itemstart - 1);
        } else {
            $itemend = $currentpage * $this->resultsPerPage;
        }

        if ($itemend > count($this->data)) {
            $itemend = count($this->data);
        }

        for ($i = $itemstart; $i < $itemend + 1; $i++) {
            if (is_array($this->data[$i - 1])) {
                $items = "";
                foreach ($this->data[$i - 1] as $key => $value) {
                    $items .= ", '" . addslashes($this->convert($key, $value)) . "'";
                }

                $execute = '$output .= sprintf($this->itemwrap ' . $items . ');';
                eval($execute);
            }
        }

        $output .= $this->endwrap;

        $output = stripslashes($output);

        if ($return == true) {
            return $output;
        } else {
            echo $output;
        }
    }

}
