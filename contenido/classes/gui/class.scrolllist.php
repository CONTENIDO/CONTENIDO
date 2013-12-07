<?php
/**
 * This file contains the scrollable lists GUI class.
 *
 * @package Core
 * @subpackage GUI
 * @version SVN Revision $Rev:$
 *
 * @author Mischa Holz
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Scrollable lists GUI class
 *
 * @package Core
 * @subpackage GUI
 */
class cGuiScrollList {

    /**
     * Data container
     * @public array
     */
    public $data = Array();

    /**
     * Header container
     * @public array
     */
    public $header = Array();

    /**
     * Number of records displayed per page
     * @public string
     */
    public $resultsPerPage;

    /**
     * Start page
     * @public string
     */
    public $listStart;

    /**
     * sortable flag
     * @public string
     */
    public $sortable;

    /**
     * sortlink
     * @public string
     */
    public $sortlink;

    /**
     * Table item
     */
    public $objTable;

    /**
     * Header row
     */
    public $objHeaderRow;

    /**
     * Header item
     */
    public $objHeaderItem;

    /**
     * Header item
     */
    public $objRow;

    /**
     * Header item
     */
    public $objItem;

    /*
     * TODO: Shouldn't $area and $frame be parameters instead of global
     * variables?
     */

    /**
     * Creates a new FrontendList object.
     *
     * @param $defaultstyle boolean use the default style for object
     *        initializing?
     */
    public function __construct($defaultstyle = true, $action = "") {
        global $cfg, $area, $frame;

        $this->resultsPerPage = 0;
        $this->listStart = 1;
        $this->sortable = false;

        $this->objTable = new cHTMLTable();
        if ($defaultstyle == true) {
            $this->objTable->setClass("generic");
            $this->objTable->updateAttributes(array(
                "cellpadding" => "2"
            ));
        }

        $this->objHeaderRow = new cHTMLTableRow();

        $this->objHeaderItem = new cHTMLTableHead();

        $this->objRow = new cHTMLTableRow();

        $this->objItem = new cHTMLTableData();

        $this->sortlink = new cHTMLLink();
        $this->sortlink->setStyle("color: #666666;");
        $this->sortlink->setCLink($area, $frame, $action);
    }

    /**
     * Sets the sortable flag for a specific row.
     *
     * $obj->setSortable(true);
     *
     * @param $sortable boolean true or false
     */
    public function setSortable($key, $sortable) {
        $this->sortable[$key] = $sortable;
    }

    /**
     * Sets the custom parameters for sortable links
     *
     * $obj->setCustom($key, $custom);
     *
     * @param $key Custom entry key
     * @param $custom Custom entry value
     */
    public function setCustom($key, $custom) {
        $this->sortlink->setCustom($key, $custom);
    }

    /**
     * Is called when a new row is rendered
     *
     * @param $row The current row which is being rendered
     */
    public function onRenderRow($row) {
        $this->objRow->setStyle("white-space:nowrap;");
    }

    /**
     * Is called when a new column is rendered
     *
     * @param $row The current column which is being rendered
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
     * @param $index Numeric index
     * @param ... Additional parameters (data)
     */
    public function setHeader() {
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
     * @param $index int Numeric index
     * @param ... Additional parameters (data)
     */
    public function setData($index) {
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
     * @param $index int Numeric index
     * @param ... Additional parameters (data)
     */
    public function setHiddenData($index) {
        $numargs = func_num_args();

        for ($i = 1; $i < $numargs; $i++) {
            $this->data[$index]["hiddendata"][$i] = func_get_arg($i);
        }
    }

    /**
     * Sets the number of records per page.
     *
     * @param $numresults int Amount of records per page
     */
    public function setResultsPerPage($numresults) {
        $this->resultsPerPage = $numresults;
    }

    /**
     * Sets the starting page number.
     *
     * @param $startpage int Page number on which the list display starts
     */
    public function setListStart($startpage) {
        $this->listStart = $startpage;
    }

    /**
     * Returns the current page.
     *
     * @param $none
     * @return s Current page number
     */
    public function getCurrentPage() {
        if ($this->resultsPerPage == 0) {
            return 1;
        }

        return ($this->listStart);
    }

    /**
     * Returns the amount of pages.
     *
     * @param $none
     * @return s Amount of pages
     */
    public function getNumPages() {
        return (ceil(count($this->data) / $this->resultsPerPage));
    }

    /**
     * Sorts the list by a given field and a given order.
     *
     * @param $field int Field index
     * @param $order string|int Sort order (see php's sort documentation)
     */
    public function sort($field, $order) {
        if ($order == "") {
            $order = SORT_ASC;
        }

        if ($order == "ASC") {
            $order = SORT_ASC;
        }

        if ($order == "DESC") {
            $order = SORT_DESC;
        }

        $this->sortkey = $field;
        $this->sortmode = $order;

        $field = $field + 1;
        $this->data = cArray::csort($this->data, "$field", $order);
    }

    /**
     * Field converting facility.
     * Needs to be overridden in the child class to work properbly.
     *
     * @param $field Field index
     * @param $value Field value
     */
    public function convert($field, $value, $hiddendata) {
        return $value;
    }

    /**
     * Outputs or optionally returns
     *
     * @param $return If true, returns the list
     */
    public function render($return = true) {
        global $cfg;

        $currentpage = $this->getCurrentPage();

        $itemstart = (($currentpage - 1) * $this->resultsPerPage) + 1;

        $headeroutput = "";
        $output = "";

        /* Render header */
        foreach ($this->header as $key => $value) {
            if (is_array($this->sortable)) {
                if (array_key_exists($key, $this->sortable) && $this->sortable[$key] == true) {
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
                    $hiddendata = $this->data[$i - 1]["hiddendata"];

                    $this->objItem->setContent($this->convert($key, $value, $hiddendata));
                    $items .= $this->objItem->render();
                }
                $this->objItem->advanceID();
            }

            $this->objRow->setContent($items);
            $items = "";

            $output .= $this->objRow->render();
            $this->objRow->advanceID();
        }

        $this->objTable->setContent($headeroutput . $output);

        $output = stripslashes($this->objTable->render());

        if ($return == true) {
            return $output;
        } else {
            echo $output;
        }
    }

}
