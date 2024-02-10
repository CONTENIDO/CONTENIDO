<?php

/**
 * This file contains the frontend list class.
 *
 * @since      CONTENIDO 4.10.2 - - Class code extracted from `contenido/includes/api/functions.frontend.list.php`
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
 * Class cFrontendList for scrollable frontend lists.
 *
 * @TODO This class has similarities to {@see cGuiScrollList}, we may think to merge both into one solution.
 */
class cFrontendList
{

    /**
     * Wrap for table start.
     *
     * @var string
     */
    protected $_startWrap;

    /**
     * Wrap for table end.
     *
     * @var string
     */
    protected $_endWrap;

    /**
     * Wrap for a single item.
     *
     * @var string
     */
    protected $_itemWrap;

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
     * @param string $startWrap
     *         Wrap for the list start
     * @param string $endWrap
     *         Wrap for the list end
     * @param string $itemWrap
     *         Wrap for a single item
     */
    public function __construct(string $startWrap, string $endWrap, string $itemWrap)
    {
        $this->_startWrap = $startWrap;
        $this->_endWrap = $endWrap;
        $this->_itemWrap = $itemWrap;
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
     * @param mixed ...$params Additional parameters (data)
     * @noinspection PhpUnusedParameterInspection
     */
    public function setData(int $index, ...$params)
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
    public function setResultsPerPage(int $resultsPerPage)
    {
        $this->_resultsPerPage = $resultsPerPage;
    }

    /**
     * Sets the starting page number.
     *
     * @param int $listStart
     *         Page number on which the list display starts
     */
    public function setListStart(int $listStart)
    {
        $this->_listStart = $listStart;
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
        return (int)ceil(count($this->_data) / $this->_resultsPerPage);
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
    public function sort(string $field, int $order)
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
    public function convert(int $field, $value)
    {
        return $value;
    }

    /**
     * Outputs or optionally returns.
     *
     * @param bool $return
     *         if true, returns the list
     * @return string|void
     */
    public function output(bool $return = false)
    {
        $output = $this->_startWrap;

        $currentPage = $this->getCurrentPage();

        $itemStart = (($currentPage - 1) * $this->_resultsPerPage) + 1;

        if ($this->_resultsPerPage == 0) {
            $itemEnd = count($this->_data) - ($itemStart - 1);
        } else {
            $itemEnd = $currentPage * $this->_resultsPerPage;
        }

        if ($itemEnd > count($this->_data)) {
            $itemEnd = count($this->_data);
        }

        for ($i = $itemStart; $i < $itemEnd + 1; $i++) {
            $currentPos = $i - 1;
            if (is_array($this->_data[$currentPos])) {
                $items = "";
                foreach ($this->_data[$currentPos] as $key => $value) {
                    $items .= ", '" . addslashes($this->convert($key, $value)) . "'";
                }

                $itemWrap = str_replace('{LIST_ITEM_POS}', $currentPos, $this->_itemWrap);
                $execute = '$output .= sprintf($itemWrap ' . $items . ');';
                eval($execute);
            }
        }

        $output .= $this->_endWrap;

        $output = stripslashes($output);

        if ($return) {
            return $output;
        } else {
            echo $output;
        }
    }

}

/**
 * @deprecated [2024-02-04] Since 4.10.2, use {@see cFrontendList} instead!
 */
class FrontendList extends cFrontendList
{

    public function __construct(string $startWrap, string $endWrap, string $itemWrap)
    {
        cDeprecated("The class FrontendList is deprecated since CONTENIDO 4.10.2, use cFrontendList instead.");
        parent::__construct($startWrap, $endWrap, $itemWrap);
    }

}
