<?php
/**
 * This file contains the frontend list class.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Timo Hummel
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */


defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class FrontendList
 * Class for scrollable frontend lists
 *
 * @package          Core
 * @subpackage       Backend
 */
class FrontendList {

    /**
     * Wrap for a single item
     *
     * @var string
     */
    var $itemwrap;

    /**
     * Wrap for table start
     *
     * @var string
     */
    var $startwrap;

    /**
     * Wrap for table end
     *
     * @var string
     */
    var $endwrap;

    /**
     * Data container
     *
     * @var array
     */
    var $data = Array();

    /**
     * Number of records displayed per page
     *
     * @var string
     */
    var $resultsPerPage;

    /**
     * Start page
     *
     * @var string
     */
    var $listStart;

    /**
     * Creates a new FrontendList object.
     *
     * The placeholder for item wraps are the same as for
     * sprintf. See the documentation for sprintf.
     *
     * Caution: Make sure that percentage signs are written as %%.
     *
     * @param $startwrap Wrap for the list start
     * @param $endwrap Wrap for the list end
     * @param $itemwrap Wrap for a single item
     */
    function FrontendList($startwrap, $endwrap, $itemwrap) {
        $this->resultsPerPage = 0;
        $this->listStart = 1;

        $this->itemwrap = $itemwrap;
        $this->startwrap = $startwrap;
        $this->endwrap = $endwrap;
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
     * @param $index int Numeric index
     * @param ... Additional parameters (data)
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
     * @param $numresults int Amount of records per page
     */
    function setResultsPerPage($numresults) {
        $this->resultsPerPage = $numresults;
    }

    /**
     * Sets the starting page number.
     *
     * @param $startpage int Page number on which the list display starts
     */
    function setListStart($startpage) {
        $this->listStart = $startpage;
    }

    /**
     * Returns the current page.
     *
     * @return int Current page number
     */
    function getCurrentPage() {
        if ($this->resultsPerPage == 0) {
            return 1;
        }

        return ($this->listStart);
    }

    /**
     * Returns the amount of pages.
     *
     * @return int Amount of pages
     */
    function getNumPages() {
        return (ceil(count($this->data) / $this->resultsPerPage));
    }

    /**
     * Sorts the list by a given field and a given order.
     *
     * @param $field Field index
     * @param $order Sort order (see php's sort documentation)
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
     * @param $return If true, returns the list
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
