<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Excel handling class
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

cInclude('pear', 'Spreadsheet/Excel/Writer.php');

/** @deprecated 2012-03-05 This class is not supported any longer. */
class ExcelWorksheet
{
    var $_data = array();
    var $_title;
    var $_filename;

    function ExcelWorksheet ($title, $filename)
    {
        cDeprecated("This class is not supported any longer.");
        $this->_title         = cSecurity::escapeDB($title, null);
        $this->_filename     = cSecurity::escapeDB($filename, null);
    }

    function setRow ($row)
    {
        $row = cSecurity::escapeDB($row, null);
        $args = func_num_args();

        for ($arg=1;$arg<$args;$arg++)
        {
            $ma = func_get_arg($arg);
            $this->setCell($row, $arg, $ma);
        }
    }

    function setCell($row, $cell, $data)
    {
        $row     = cSecurity::escapeDB($row, null);
        $cell     = cSecurity::escapeDB($cell, null);
        $data     = cSecurity::escapeDB($data, null);
        $this->_data[$row][$cell] = $data;
    }

    function make ()
    {

        $workbook = new Spreadsheet_Excel_Writer();
        $workbook->send($this->_filename);

        $worksheet = & $workbook->addWorksheet($this->_title);

        foreach ($this->_data as $row => $line)
        {
            foreach ($line as $col => $coldata)
            {
                $worksheet->writeString($row-1, $col-1, $coldata);
            }

        }

        $workbook->close();
    }
}
?>