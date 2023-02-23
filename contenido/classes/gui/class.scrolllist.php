<?php

/**
 * This file contains the scrollable lists GUI class.
 *
 * @package Core
 * @subpackage GUI
 *
 * @author Mischa Holz
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Scrollable lists GUI class.
 *
 * @package Core
 * @subpackage GUI
 */
class cGuiScrollList {

    /**
     * Data container.
     *
     * @var array
     */
    public $data = [];

    /**
     * Header container.
     *
     * @var array
     */
    public $header = [];

    /**
     * Number of records displayed per page.
     *
     * @var string
     */
    public $resultsPerPage;

    /**
     * Start page.
     *
     * @var string
     */
    public $listStart;

    /**
     * Sortable flags for rows.
     *
     * @var array
     */
    public $sortable;

    /**
     * sortlink
     *
     * @var cHTMLLink
     */
    public $sortlink;

    /**
     * Table item
     *
     * @var cHTMLTable
     */
    public $objTable;

    /**
     * Header row
     *
     * @var cHTMLTableRow
     */
    public $objHeaderRow;

    /**
     * Header item
     *
     * @var cHTMLTableHead
     */
    public $objHeaderItem;

    /**
     * Header item
     *
     * @var cHTMLTableRow
     */
    public $objRow;

    /**
     * Header item
     *
     * @var cHTMLTableData
     */
    public $objItem;

    /**
     * @var string
     */
    public $sortkey;

    /**
     * @var int - SORT_ASC or SORT_DESC
     */
    public $sortmode;

    /**
     * Constructor to create an instance of this class.
     *
     * @param bool $defaultstyle [optional]
     *         use the default style for object initializing?
     * @param string $action [optional]
     */
    public function __construct($defaultstyle = true, $action = "") {
        $this->resultsPerPage = 0;
        $this->listStart = 1;
        $this->sortable = [];

        $this->objTable = new cHTMLTable();
        if ($defaultstyle) {
            $this->objTable->setClass("generic");
            $this->objTable->updateAttributes(["cellpadding" => "2"]);
        }

        $this->objHeaderRow = new cHTMLTableRow();

        $this->objHeaderItem = new cHTMLTableHead();

        $this->objRow = new cHTMLTableRow();

        $this->objItem = new cHTMLTableData();

        $this->sortlink = new cHTMLLink();
        $this->sortlink->setStyle("color: #666666;");
        $this->sortlink->setCLink(cRegistry::getArea(), cRegistry::getFrame(), $action);
    }

    /**
     * Sets the sortable flag for a specific row.
     *
     * $obj->setSortable(true);
     *
     * @param int $key
     * @param bool $sortable
     *         true or false
     */
    public function setSortable($key, $sortable) {
        $this->sortable[$key] = $sortable;
    }

    /**
     * Sets the custom parameters for sortable links.
     *
     * $obj->setCustom($key, $custom);
     *
     * @param string $key
     *         Custom entry key
     * @param string $custom
     *         Custom entry value
     */
    public function setCustom($key, $custom) {
        $this->sortlink->setCustom($key, $custom);
    }

    /**
     * Is called when a new row is rendered.
     *
     * @param int $row
     *         The current row which is being rendered
     */
    public function onRenderRow($row) {
        $this->objRow->setStyle("white-space:nowrap;");
    }

    /**
     * Is called when a new column is rendered.
     *
     * @param int $column
     *         The current column which is being rendered
     */
    public function onRenderColumn($column) {
    }

    /**
     * Sets header data.
     *
     * Note: This public function eats as many parameters as you specify.
     *
     * Example:
     * $obj->setHeader("foo", "bar");
     *
     * Make sure that the amount of parameters stays the same for all
     * setData calls in a single object.
     *
     * @param mixed ...$values
     *         Additional parameters (data)
     */
    public function setHeader(...$values) {
        $numargs = func_num_args();

        for ($i = 0; $i < $numargs; $i++) {
            $this->header[$i] = func_get_arg($i);
        }
    }

    /**
     * Sets data.
     *
     * Note: This public function eats as many parameters as you specify.
     *
     * Example:
     * $obj->setData(0, "foo", "bar");
     *
     * Make sure that the amount of parameters stays the same for all
     * setData calls in a single object. Also make sure that your index
     * starts from 0 and ends with the actual number - 1.
     *
     * @param int $index
     *         Numeric index
     * @param mixed ...$values
     *         Additional parameters (data)
     */
    public function setData($index, ...$values) {
        $numargs = func_num_args();

        for ($i = 1; $i < $numargs; $i++) {
            $this->data[$index][$i] = func_get_arg($i);
        }
    }

    /**
     * Sets hidden data.
     *
     * Note: This public function eats as many parameters as you specify.
     *
     * Example:
     * $obj->setHiddenData(0, "foo", "bar");
     *
     * Make sure that the amount of parameters stays the same for all
     * setData calls in a single object. Also make sure that your index
     * starts from 0 and ends with the actual number - 1.
     *
     * @param int $index
     *         Numeric index
     * @param mixed ...$values
     *         Additional parameters (data)
     */
    public function setHiddenData($index, ...$values) {
        $numargs = func_num_args();

        for ($i = 1; $i < $numargs; $i++) {
            $this->data[$index]["hiddendata"][$i] = func_get_arg($i);
        }
    }

    /**
     * Sets the number of records per page.
     *
     * @param int $numresults
     *         Amount of records per page
     */
    public function setResultsPerPage($numresults) {
        $this->resultsPerPage = $numresults;
    }

    /**
     * Sets the starting page number.
     *
     * @param int $startpage
     *         Page number on which the list display starts
     */
    public function setListStart($startpage) {
        $this->listStart = $startpage;
    }

    /**
     * Returns the current page.
     *
     * @return int
     *         Current page number
     */
    public function getCurrentPage() {
        if ($this->resultsPerPage == 0) {
            return 1;
        }

        return $this->listStart;
    }

    /**
     * Returns the amount of pages.
     *
     * @return float
     *         Amount of pages
     */
    public function getNumPages() {
        return ceil(count($this->data) / $this->resultsPerPage);
    }

    /**
     * Sorts the list by a given field and a given order.
     *
     * @param int $field
     *         Field index
     * @param string|int $order
     *         Sort order (see php's sort documentation)
     */
    public function sort($field, $order) {
        $this->sortkey = $field;
        $this->sortmode = ($order === 'DESC') ? SORT_DESC : SORT_ASC;

        $field = $field + 1;
        $this->data = cArray::csort($this->data, "$field", $this->sortmode);
    }

    /**
     * Field converting facility.
     * Needs to be overridden in the child class to work properbly.
     *
     * @param int $field
     *         Field index
     * @param string $value
     *         Field value
     * @param array $hiddendata
     * @return string
     */
    public function convert($field, $value, $hiddendata) {
        return $value;
    }

    /**
     * Outputs or optionally returns.
     *
     * @param bool $return [optional]
     *         If true, returns the list
     * @return string|void
     */
    public function render($return = true) {

        $currentpage = $this->getCurrentPage();

        $itemstart = (($currentpage - 1) * $this->resultsPerPage) + 1;

        $headeroutput = "";
        $output = "";

        // Render header
        foreach ($this->header as $key => $value) {
            if (is_array($this->sortable)) {
                if (array_key_exists($key, $this->sortable) && $this->sortable[$key]) {
                    $this->sortlink->setContent($value);
                    $this->sortlink->setCustom("sortby", $key);

                    if ($this->sortkey == $key && $this->sortmode == SORT_ASC) {
                        $this->sortlink->setCustom("sortmode", "DESC");
                    } else {
                        $this->sortlink->setCustom("sortmode", "ASC");
                    }

                    $this->objHeaderItem->setContent($this->sortlink->render());
                    $headeroutput .= $this->objHeaderItem->render();
                } else {
                    $this->objHeaderItem->setContent($value);
                    $headeroutput .= $this->objHeaderItem->render();
                }
            } else {
                $this->objHeaderItem->setContent($value);
                $headeroutput .= $this->objHeaderItem->render();
            }
            $this->objHeaderItem->advanceID();
        }

        $this->objHeaderRow->setContent($headeroutput);

        $headeroutput = $this->objHeaderRow->render();

        if ($this->resultsPerPage == 0) {
            $itemend = count($this->data) - ($itemstart - 1);
        } else {
            $itemend = $currentpage * $this->resultsPerPage;
        }

        if ($itemend > count($this->data)) {
            $itemend = count($this->data);
        }

        for ($i = $itemstart; $i < $itemend + 1; $i++) {

            // At the last entry we get NULL as result
            // This produce an error, therefore use continue
            if ($this->data[$i - 1] == NULL) {
                continue;
            }

            $items = "";

            $this->onRenderRow($i);

            foreach ($this->data[$i - 1] as $key => $value) {
                $this->onRenderColumn($key);

                if ($key != "hiddendata") {
                    $hiddendata = !empty($this->data[$i - 1]["hiddendata"]) && is_array($this->data[$i - 1]["hiddendata"]) ? $this->data[$i - 1]["hiddendata"] : [];

                    $this->objItem->setContent($this->convert($key, $value, $hiddendata));
                    $items .= $this->objItem->render();
                }
                $this->objItem->advanceID();
            }

            $this->objRow->setContent($items);

            $output .= $this->objRow->render();
            $this->objRow->advanceID();
        }

        $this->objTable->setContent($headeroutput . $output);

        $output = $this->objTable->render();

        if ($return) {
            return $output;
        } else {
            echo $output;
        }
    }

}
