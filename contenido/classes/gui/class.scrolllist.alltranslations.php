<?php

/**
 * This file contains the all translations scrollable lists GUI class.
 *
 * @since      CONTENIDO 4.10.2 - Class code extracted from `contenido/includes/include.con_translate.php`.
 * @package    Core
 * @subpackage GUI
 * @author     Ingo van Peeren
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Extend cGuiScrollList for some special features like CSS class for table data
 */
class cGuiScrollListAlltranslations extends cGuiScrollList
{

    /**
     * Constructor to create an instance of this class.
     */
    public function __construct()
    {
        parent::__construct(false);
        $this->objTable->setClass("generic all_translations");
        $this->objTable->updateAttributes([
            "cellpadding" => "2"
        ]);
    }

    /**
     * Is called when a new row is rendered
     *
     * @param int $row
     *         The current row which is being rendered
     */
    public function onRenderRow(int $row)
    {
        // Add module name to the table row, we need it for the "inused_module" action
        $this->objRow->setAttribute('data-name', $this->data[$row - 1][1] ?? '');
    }

    /**
     * Is called when a new column is rendered
     *
     * @param int|string $column
     *         The current column which is being rendered
     */
    public function onRenderColumn($column)
    {
        $iColumns = count($this->data[0]);

        switch ($column) {
            case 1:
                $sClass = 'module';
                break;
            case 2:
                $sClass = 'inuse';
                break;
            case 3:
                $sClass = 'keyword';
                break;
            case $iColumns:
                $sClass = 'actions';
                break;

            default:
                $sClass = 'translation';
                break;
        }

        $this->objItem->setClass($sClass);
    }

    /**
     * Sorts the list by a given field and a given order.
     *
     * @param int $field
     *         Field index
     * @param string $order
     *         'ASC' od 'DESC'
     */
    public function sort(int $field, string $order = 'ASC')
    {
        $this->sortkey = $field;
        $this->sortmode = ($order === 'DESC') ? SORT_DESC : SORT_ASC;

        $field = $field + 1;

        if ($field > 3) {
            $sortby = [];
            foreach ($this->data as $row => $cols) {
                $sortby[$row] = trim(cString::toLowerCase(conHtmlentities($cols[$field])));
            }
            $this->data = cArray::csort($this->data, $sortby, $this->sortmode);
        } else {
            $this->data = cArray::csort($this->data, "$field", $this->sortmode);
        }
    }

}
