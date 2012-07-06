<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Generic table builder
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.0.2
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created unknown
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

class Table {

    /**
     * Table border color
     * @var string
     */
    var $border_color = '';

    /**
     * Table border style
     * @var string
     */
    var $border_style = '';

    /**
     * Table cell spacing
     * @var string
     */
    var $cell_spacing = '';

    /**
     * Table cell padding
     * @var string
     */
    var $cell_padding = '';

    /**
     * Table header color
     * @var string
     */
    var $header_color = '';

    /**
     * Table light row color
     * @var string
     */
    var $light_color = '';

    /**
     * Table dark row color
     * @var string
     */
    var $dark_color = '';

    /**
     * Internal dark/light row counter
     * @var bool
     */
    var $dark_row = 0;

    /**
     * Internal table width counter
     * @var int
     */
    var $table_cols = 0;

    /**
     * Internal first cell checker
     * @var bool
     */
    var $first_cell = 0;

    /**
     * Internal full border checker
     * @var bool
     */
    var $fullborder = false;

    /**
     * Directly output table if true
     *
     */
    var $directoutput = true;

    /**
     * Constructor
     */
    function Table($m_bordercolor = "", $m_borderstyle = "", $m_cellspacing = "0", $m_cellpadding = "2", $m_header_color = "", $m_light_color = "", $m_dark_color = "", $m_fullborder = false, $m_directoutput = true) {
        cDeprecated("Use class cHTMLTable instead");

        $this->border_color = $m_bordercolor;
        $this->border_style = $m_borderstyle;
        $this->cellspacing = $m_cellspacing;
        $this->cellpadding = $m_cellpadding;
        $this->header_color = $m_header_color;
        $this->dark_color = $m_dark_color;
        $this->light_color = $m_light_color;
        $this->fullborder = $m_fullborder;
        $this->directoutput = $m_directoutput;
    }

    /**
     * Begins the new table
     * @param none
     * @return void
     */
    function start_table() {
        if (!$this->fullborder) {
            $starttable = '<table class="generic" cellspacing="' . $this->cellspacing . '" cellpadding="' . $this->cellpadding . '">';
        } else {
            $starttable = '<table class="fullborder" cellspacing="' . $this->cellspacing . '" cellpadding="' . $this->cellpadding . '">';
        }

        if ($this->directoutput) {
            echo $starttable . "\n";
        } else {
            return $starttable . "\n";
        }
    }

    /**
     * Outputs a header row
     * @param none
     * @return void
     */
    function header_row($additional = "") {
        $headerrow = '<tr class="textw_medium" style="' . $additional . '">';

        if ($this->directoutput) {
            echo $headerrow . "\n";
        } else {
            return $headerrow . "\n";
        }
    }

    /**
     * Outputs a regular row
     * @param none
     * @return void
     */
    function row($id = '') {
        $row = '<tr ' . $id . '>';

        if ($this->directoutput) {
            echo $row . "\n";
        } else {
            return $row . "\n";
        }
    }

    /**
     * Outputs a header cell
     * @param $content The content which will fill the cell
     * @param $align   The horizontal alignment of the cell, default "center"
     * @param $valign  The vertical alignment of the cell, default "top"
     * @return void
     */
    function header_cell($content, $align = "center", $valign = "top", $additional = "", $borderTop = 1) {
        $sBorder = "";
        if ($borderTop != 1) {
            $sBorder = "style='border-top-width: " . $borderTop . "px;'";
        }
        $header_cell = '<th class="center" ' . $sBorder . ' valign="' . $valign . '" align="' . $align . '"' . $additional . '>' . $content . '</th>';

        if ($this->first_cell) {
            $this->table_cols = 0;
            $this->first_cell = false;
        }

        $this->table_cols++;

        if ($this->directoutput) {
            echo $header_cell . "\n";
        } else {
            return $header_cell . "\n";
        }
    }

    /**
     * Outputs a regular cell
     * @param $content The content which will fill the cell
     * @param $align   The horizontal alignment of the cell, default "center"
     * @param $valign  The vertical alignment of the cell, default "top"
     * @param $additional Additional flags for the table
     */
    function cell($content, $align = "center", $valign = "top", $additional = "", $bSetStyle = true) {
        if (strlen($content) == 0) {
            $content = "&nbsp;";
        }
        $cell = '<td ' . $additional;

        if ($valign != '') {
            $cell.=' valign="' . $valign . '"';
        }

        if ($bSetStyle) {
            $cell.='';
        }

        if ($align != '') {
            $cell.=' align="' . $align . '"';
        }

        $cell.='>' . $content . '</td>';

        if ($this->first_cell) {
            $this->table_cols = 0;
            $this->first_cell = false;
        }

        $this->table_cols++;

        if ($this->directoutput) {
            echo $cell . "\n";
        } else {
            return $cell . "\n";
        }
    }

    /**
     * Outputs a borderless cell
     * @param $content The content which will fill the cell
     * @param $align   The horizontal alignment of the cell, default "center"
     * @param $valign  The vertical alignment of the cell, default "top"
     * @param $additional Additional flags for the table
     */
    function borderless_cell($content, $align = "center", $valign = "top", $additional = "") {
        if (strlen($content) == 0) {
            $content = "&nbsp;";
        }
        $borderless_cell = '<td ' . $additional . ' valign="' . $valign . '" align="' . $align . '">' . $content . '</td>';

        if ($this->first_cell) {
            $this->table_cols = 0;
            $this->first_cell = false;
        }

        $this->table_cols++;

        if ($this->directoutput) {
            echo $borderless_cell . "\n";
        } else {
            return $borderless_cell . "\n";
        }
    }

    /**
     * Outputs a sum cell
     * @param $content The content which will fill the cell
     * @param $align   The horizontal alignment of the cell, default "center"
     * @param $valign  The vertical alignment of the cell, default "top"
     */
    function sumcell($content, $align = "center", $valign = "top") {
        if (strlen($content) == 0) {
            $content = "&nbsp;";
        }
        $sumcell = '<td colspan="' . $this->table_cols . '" valign="' . $valign . '" align="' . $align . '">' . $content . '</td>';

        if ($this->directoutput) {
            echo $sumcell . "\n";
        } else {
            return $sumcell . "\n";
        }
    }

    /**
     * Ends a row
     * @param none
     * @return void
     */
    function end_row() {
        $end_row = '</tr>';

        $this->first_cell = true;

        if ($this->directoutput) {
            echo $end_row . "\n";
        } else {
            return $end_row . "\n";
        }
    }

    /**
     * Ends a table
     * @param none
     * @return void
     */
    function end_table() {
        $end_table = '</table>';

        if ($this->directoutput) {
            echo $end_table . "\n";
        } else {
            return $end_table . "\n";
        }
    }

}

?>