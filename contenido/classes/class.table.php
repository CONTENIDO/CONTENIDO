<?php

/**
 *  Class Table
 *
 *  Generic table builder
 *
 *  @author Timo A. Hummel <Timo.Hummel@4fb.de>
 *  @copyright  four for business AG <http:#www.4fb.de>
 *  @version 0.2
 */
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
    function Table($m_bordercolor = "#EEEEEE", $m_borderstyle = "solid", $m_cellspacing = "0", $m_cellpadding="2", $m_header_color = "#222222", $m_light_color = "#AAAAAA", $m_dark_color = "#777777", $m_fullborder = false, $m_directoutput = true) {
        $this->border_color = $m_bordercolor;
        $this->border_style = $m_borderstyle;
        $this->cellspacing = $m_cellspacing;
        $this->cellpadding = $m_cellpadding;
        $this->header_color = $m_header_color;
        $this->dark_color = $m_dark_color;
        $this->light_color = $m_light_color;
        $this->fullborder = $m_fullborder;
        $this->directoutput = $m_directoutput;

    } # end function


    


    /**
     * Begins the new table
     * @param none
     * @return void
     */
    function start_table() {

        if (!$this->fullborder)
        {
            $starttable =  '<table style="border: 0px; border-left:1px; border-bottom: 1px; border-color: ' . $this->border_color . '; border-style: ' . $this->border_style . '" cellspacing="'. $this->cellspacing . '" cellpadding="'. $this->cellpadding . '">';
        } else {
            $starttable = '<table style="border: 1px; border-color: ' . $this->border_color . '; border-style: ' . $this->border_style . '" cellspacing="'. $this->cellspacing . '" cellpadding="'. $this->cellpadding . '">';
        }

        $dark_color = 0;

        if ($this->directoutput)
        {
            echo $starttable;
        } else {
            return $starttable;
        }

     
    } # end function



    /**
     * Outputs a header row
     * @param none
     * @return void
     */
    function header_row($additional="") {
            
         $headerrow = '<tr class="textw_medium" style="background-color: ' . $this->header_color . '" '.$additional.'>';     

         if ($this->directoutput)
         {
            echo $headerrow;
         } else {
            return $headerrow;
         }
    } # end function



    /**
     * Outputs a regular row
     * @param none
     * @return void
     */
    function row($id = '') {
         if ($this->dark_row)
         {
             $bgColor = $this->light_color;
         } else {
             $bgColor = $this->dark_color;
         }

         $this->dark_row = !$this->dark_row;
         
         $row = '<tr class="text_medium" style="background-color: ' . $bgColor . '" '.$id.'>';

         if ($this->directoutput)
         {
            echo $row;
         } else {
            return $row;
         }

    } # end function



    /**
     * Outputs a header cell
     * @param $content The content which will fill the cell
     * @param $align   The horizontal alignment of the cell, default "center"
     * @param $valign  The vertical alignment of the cell, default "top"
     * @return void
     */
    function header_cell($content, $align="center", $valign="top", $additional="", $borderTop = 1){

         $header_cell = '<th class="textg_medium" valign="' . $valign . '" style="border: 0px; border-top:'.$borderTop.'px; border-right:1px; border-color: '. $this->border_color . '; border-style: ' . $this->border_style . '" align="' . $align . '"' . $additional . '>' . $content . '</th>';
         
         if ($this->first_cell)
         {
             $this->table_cols = 0;
             $this->first_cell = false;
         }
         
         $this->table_cols++;

         if ($this->directoutput)
         {
            echo $header_cell;
         } else {
            return $header_cell;
         }
         
    } # end function



    /**
     * Outputs a regular cell
     * @param $content The content which will fill the cell
     * @param $align   The horizontal alignment of the cell, default "center"
     * @param $valign  The vertical alignment of the cell, default "top"    
     * @param $additional Additional flags for the table
     */
    function cell($content, $align="center", $valign="top", $additional = "", $bSetStyle = true){

         if (strlen($content) == 0)
         {
            $content = "&nbsp;";
         }
         $cell = '<td '. $additional;
		 
		 if ($valign != '') {
		 	$cell.=' valign="'.$valign .'"';
		 }
		  
		 if ($bSetStyle) {
		 	$cell.=' style="border: 0px; border-bottom:1px; border-top:0px; border-right:1px; border-color: '. $this->border_color . '; border-style: ' . $this->border_style . '"'; 
		 }
		 
		 if ($align != '') {
		 	$cell.=' align="'.$align .'"';
		 }
		 
		 $cell.='>'.$content.'</td>';

         if ($this->first_cell)
         {
             $this->table_cols = 0;
             $this->first_cell = false;
         }
         
         $this->table_cols++;

         if ($this->directoutput)
         {
            echo $cell;
         } else {
            return $cell;
         }

    } # end function

    /**
     * Outputs a borderless cell
     * @param $content The content which will fill the cell
     * @param $align   The horizontal alignment of the cell, default "center"
     * @param $valign  The vertical alignment of the cell, default "top"    
     * @param $additional Additional flags for the table
     */
    function borderless_cell($content, $align="center", $valign="top", $additional = ""){

         if (strlen($content) == 0)
         {
            $content = "&nbsp;";
         }
         $borderless_cell = '<td '. $additional .' valign="' . $valign . '" align="' . $align . '">' . $content . '</td>';

         if ($this->first_cell)
         {
             $this->table_cols = 0;
             $this->first_cell = false;
         }
         
         $this->table_cols++;

         if ($this->directoutput)
         {
            echo $borderless_cell;
         } else {
            return $borderless_cell;
         }

    } # end function

    
    /**
     * Outputs a sum cell
     * @param $content The content which will fill the cell
     * @param $align   The horizontal alignment of the cell, default "center"
     * @param $valign  The vertical alignment of the cell, default "top"     
     */
    function sumcell($content, $align="center", $valign="top"){

         if (strlen($content) == 0)
         {
            $content = "&nbsp;";
         }
         $sumcell = '<td colspan="'.$this->table_cols.'" valign="' . $valign . '" style="border: 0px; border-top:0px; border-right:1px; border-color: '. $this->border_color . '; border-style: ' . $this->border_style . '" align="' . $align . '">' . $content . '</td>';

         if ($this->directoutput)
         {
            echo $sumcell;
         } else {
            return $sumcell;
         }

    } # end function


    /**
     * Ends a row
     * @param none
     * @return void
     */
     
    function end_row()
    {
         $end_row = '</tr>';

         $this->first_cell = true;

        if ($this->directoutput)
        {
            echo $end_row;
        } else {
            return $end_row;
        }
         
    }


    /**
     * Ends a table
     * @param none
     * @return void
     */
    function end_table(){
        $end_table = '</table>';

        if ($this->directoutput)
        {
            echo $end_table;
        } else {
            return $end_table;
        }
    } # end function

} # end class Table

?>
