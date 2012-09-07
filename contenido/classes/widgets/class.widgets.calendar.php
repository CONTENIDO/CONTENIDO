<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * 
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 
 *   
 *   $Id: class.widgets.calendar.php 738 2008-08-27 10:21:19Z timo.trautmann $: 
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


cInclude("classes", "class.htmlelements.php");
cInclude("classes", "datatypes/class.datatype.datetime.php");

class cCalendarControl extends cHTMLTable
{
	var $_oDate;
	
	function cCalendarControl ($initDate = false)
	{
		parent::cHTMLTable();
		
		$this->_initFormatting();
		$this->_oDate = new cDatatypeDateTime;
		
		/* Development: Set today's date */
		$this->_oDate->setSourceFormat(cDateTime_ISO);
		
		if ($initDate === false)
		{
			$this->_oDate->set(date("Y-m-d H:i:s"));
		} else {
			$this->_oDate->set($initDate);
		}
	}
	

	
	function _initFormatting ()
	{
		$this->setClass("calendar");
		$this->setCellSpacing(0);
		$this->setCellPadding(0);
	}
	
	function _renderHead ()
	{
		$oHead = new cHTMLTableHeader;
		
		$aDayOrder = $this->_oDate->getDayOrder();

		$oRow = new cHTMLTableRow;
		
		$aData = array();
		
		foreach ($aDayOrder as $iDay)
		{
			$oData = new cHTMLTableData;
			$oData->setContent(substr($this->_oDate->getDayName($iDay),0,2));
			
			$aData[] = $oData;
		}
		
		$oRow->setContent($aData);
		$oHead->setContent($oRow);
		
		return ($oHead);
	}
	
	function _renderBody ()
	{
		$iRows = 6;
		$iDaysPerWeek = 7;
		
		$oHead = new cHTMLTableBody;
		
		$aRowData = array();
		
		for ($iRow = 0; $iRow < $iRows; $iRow++)
		{
			$oRow = new cHTMLTableRow;
			$aData = array();
			$aDayOrder = $this->_oDate->getDayOrder();
			
			foreach ($aDayOrder as $iDay)
			{
				$oData = new cHTMLTableData;
				$oData->setContent('&nbsp;');
				$oData->setStyle("border: 1px solid white");
				$oData->setId("cal_".$this->getId()."_".$iRow . "_".$iDay);
				$aData[] = $oData;
			}
			$oRow->setContent($aData);
			$aRowData[] = $oRow;
		}	
		
		$oHead->setContent($aRowData);
		
		return ($oHead);
	}

	function _renderJS ()
	{
		$this->_oDate->setSourceFormat(cDateTime_UNIX);
		
		$oScript = new cHTMLScript;
		$sScript = '

			var {cid}_markedDay;
			var {cid}_markedMonth;
			var {cid}_markedYear;
			var {cid}_currentYear;
			var {cid}_currentMonth;
			var {cid}_markedCell;
			var {cid}_callBack = function (year, month, day) {};

			{cid}_setDefaultDay(2006, 03, 30);

			{cid}_setCalendar('.$this->_oDate->getYear().', '. $this->_oDate->getMonth(). ', '. $this->_oDate->getDay().');

			function {cid}_setDefaultDay (year, month, day)
			{
				
				{cid}_markedDay = parseInt(day, 10);
				{cid}_markedMonth = parseInt(month, 10);
				{cid}_markedYear = parseInt(year, 10);

				mDate = new Date(year, month - 1, 1);
				
				var bMarkedFound = false;
				var sMarkedID = "";

				var iRow = 0;
				var iFirstDayOfWeek = '.$this->_oDate->getFirstDayOfWeek().';

				for (var i=1; i < 32; i++)
				{
					mDate.setDate(i);

					var dayOfWeek = mDate.getDay(); 
					var lid = "cal_'.$this->getId().'_" + iRow + "_" + dayOfWeek;
	
					if (dayOfWeek == '.$this->_oDate->getLeapDay().')
					{
						iRow++;
					}

					if (year == {cid}_markedYear && month == {cid}_markedMonth && mDate.getDate() == {cid}_markedDay)
					{
						sMarkedID = lid;
						break;
					}
					
				}

				{cid}_markedCell = sMarkedID;

			}


			function {cid}_setCalendar (year, month)
			{
				var tmpDate = new Date();
				var tmpMonth = parseInt(month, 10);
				var tmpYear = parseInt(year, 10);

				if (isNaN(tmpMonth))
				{
					month = tmpDate.getMonth();
				}

				if (isNaN(tmpYear))
				{
					year = tmpDate.getFullYear();
				}

				if (document.getElementById("{cid}_monthselect"))
				{
					document.getElementById("{cid}_monthselect").selectedIndex = month - 1;
				}

				if (document.getElementById("{cid}_yearbox"))
				{
					document.getElementById("{cid}_yearbox").value = year;
				}

				

				mDate = new Date(year, month - 1, 1);
				
				{cid}_currentYear = year;
				{cid}_currentMonth = month;

				var iRow = 0;
				var iFirstDayOfWeek = '.$this->_oDate->getFirstDayOfWeek().';

				for (var i=0; i < 7; i++)
				{
					for (var j=0; j< 6; j++)
					{
						var lid = "cal_'.$this->getId().'_" + j + "_" + i;
						document.getElementById(lid).firstChild.nodeValue = String.fromCharCode(160);
						document.getElementById(lid).className = "";
						{cid}_detachHandler(document.getElementById(lid));
					}
				}

				for (var i=1; i < {cid}_GetMonthLength(year, month) + 1; i++)
				{
					mDate.setDate(i);
	
					var dayOfWeek = mDate.getDay(); 
					var lid = "cal_'.$this->getId().'_" + iRow + "_" + dayOfWeek;
	
					if (dayOfWeek == '.$this->_oDate->getLeapDay().')
					{
						iRow++;
					}
					
					if (year == {cid}_markedYear && month == {cid}_markedMonth && {cid}_markedCell == lid)
					{
						document.getElementById(lid).className = "marked";
					} else {
						document.getElementById(lid).className = "";
					}

					document.getElementById(lid).firstChild.nodeValue = i;
					{cid}_attachHandler(document.getElementById(lid));

				}
			}

			function {cid}_attachHandler (object)
			{
				object.onmouseover = {cid}_mouseOver;
				object.onmouseout = {cid}_mouseOut;
				object.onclick = {cid}_click;

				object.style.cursor = \'pointer\';
			}

			function {cid}_detachHandler (object)
			{
				object.onmouseover = \'\';
				object.onmouseout = \'\';

				object.style.cursor = \'\';
			}

			function {cid}_mouseOver (event)
			{
				if (this.id == {cid}_markedCell && {cid}_currentYear == {cid}_markedYear && {cid}_currentMonth == {cid}_markedMonth)
				{
					this.className = "marked";
				} else {
					this.className = "over";
				}
			}

			function {cid}_click (event)
			{
				var year = {cid}_currentYear;
				var month = {cid}_currentMonth;				

				var mDate = new Date(year, month - 1, 1);

				var iRow = 0;
				var iFirstDayOfWeek = '.$this->_oDate->getFirstDayOfWeek().';

				for (var i=1; i < {cid}_GetMonthLength(year, month) + 1; i++)
				{
					mDate.setDate(i);

					var dayOfWeek = mDate.getDay(); 
					var lid = "cal_'.$this->getId().'_" + iRow + "_" + dayOfWeek;
	
					if (dayOfWeek == '.$this->_oDate->getLeapDay().')
					{
						iRow++;
					}

					if (lid == this.id)
					{	
						{cid}_setDefaultDay(year, month, mDate.getDate());
						this.className = "marked";
						{cid}_callBack(year, month, mDate.getDate());
					} else {
						document.getElementById(lid).className ="";
					}
				}			
			}

			function {cid}_attachClickCallback (mfunction)
			{
				{cid}_callBack = mfunction;
			}

			function {cid}_isLeapYear(year) {
			    if ((year/4)   != Math.floor(year/4))   return false;
			    if ((year/100) != Math.floor(year/100)) return true;
			    if ((year/400) != Math.floor(year/400)) return false;
			    return true;
			}

			function {cid}_GetMonthLength(year, month)
			{
				var daysofmonth   = new Array( 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
				var daysofmonthLY = new Array( 31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

				if ({cid}_isLeapYear(year))
				{
					return daysofmonthLY[month-1];
				} else {
					return daysofmonth[month-1];
				}
			
			}

			function {cid}_monthprev ()
			{
				{cid}_currentMonth = parseInt({cid}_currentMonth, 10) - 1;

				if ({cid}_currentMonth < 1)
				{
					{cid}_currentYear = parseInt({cid}_currentYear, 10) - 1;
					{cid}_currentMonth = 12;
				}

				{cid}_setCalendar({cid}_currentYear, {cid}_currentMonth);
			}

			function {cid}_monthnext ()
			{
				{cid}_currentMonth = parseInt({cid}_currentMonth, 10) + 1;

				if ({cid}_currentMonth > 12)
				{
					{cid}_currentYear = parseInt({cid}_currentYear, 10) + 1;
					{cid}_currentMonth = 1;
				}

				{cid}_setCalendar({cid}_currentYear, {cid}_currentMonth);
			}

			function {cid}_mouseOut (event)
			{
				if (this.id == {cid}_markedCell && {cid}_currentYear == {cid}_markedYear && {cid}_currentMonth == {cid}_markedMonth)
				{
					this.className = "marked";
				} else {
					this.className = "";
				}
			}


			function {cid}_getMonthName (iMonth)
			{
				switch (iMonth)
				{
					case 1: return("'.$this->_oDate->getMonthName(1).'"); break;
					case 2: return("'.$this->_oDate->getMonthName(2).'"); break;
					case 3: return("'.$this->_oDate->getMonthName(3).'"); break;
					case 4: return("'.$this->_oDate->getMonthName(4).'"); break;
					case 5: return("'.$this->_oDate->getMonthName(5).'"); break;
					case 6: return("'.$this->_oDate->getMonthName(6).'"); break;
					case 7: return("'.$this->_oDate->getMonthName(7).'"); break;
					case 8: return("'.$this->_oDate->getMonthName(8).'"); break;
					case 9: return("'.$this->_oDate->getMonthName(9).'"); break;
					case 10: return("'.$this->_oDate->getMonthName(10).'"); break;
					case 11: return("'.$this->_oDate->getMonthName(11).'"); break;
					case 12: return("'.$this->_oDate->getMonthName(12).'"); break;
				}
			}


		';
		
		$sScript = str_replace("{cid}", $this->getId(), $sScript);
		$oScript->setContent($sScript);
		
		
		return $oScript;
	}

	function _renderMonthDropdown ()
	{
		$oMonthSelect = new cHTMLSelectElement($this->getId() . "_monthselect");
		$oMonthSelect->setId($this->getId() ."_monthselect");
		
		$aMonths = array();
		for ($i=1;$i<13;$i++)
		{
			$aMonths[$i] = $this->_oDate->getMonthName($i);
		}
		
		$oMonthSelect->autoFill($aMonths);
		$oMonthSelect->setEvent("change", $this->getId() ."_setCalendar(".$this->getId() ."_currentYear, this.value);");

		return ($oMonthSelect);		
	}
	
	function _renderMonthPrev ()
	{
		$oMonthLeft = new cHTMLImage;
		$oMonthLeft->setSrc("images/month_prev.gif");
		$oMonthLeft->setStyle("border: 0px solid black; cursor: pointer;");
		$oMonthLeft->setEvent("click", $this->getId() ."_monthprev();");
		return $oMonthLeft;
	}
	
	function _renderMonthNext ()
	{
		$oMonthLeft = new cHTMLImage;
		$oMonthLeft->setSrc("images/month_next.gif");
		$oMonthLeft->setStyle("border: 0px solid black; cursor: pointer;");
		$oMonthLeft->setEvent("click", $this->getId() ."_monthnext();");
		return $oMonthLeft;		
	}
	
	function _renderYearControl ()
	{
		$oYear = new cHTMLTextbox ($this->getId() ."_yearbox", "" ,4, 4);
		$oYear->setId($this->getId() ."_yearbox");
		$oYear->setEvent("change", $this->getId() ."_setCalendar(this.value, ".$this->getId() ."_currentMonth);");
		
		return $oYear;	
	}
	
	function render ()
	{
		$this->setContent(array($this->_renderHead(), $this->_renderBody(), $this->_renderJS()));
		
		$oSpan = new cHTMLSpan;
		$oSpan->setContent(array('<table cellspacing="0" cellpadding="0"><tr><td>', $this->_renderMonthPrev(), '</td><td>', $this->_renderMonthDropdown(), '</td><td>', $this->_renderYearControl(), '</td><td>', $this->_renderMonthNext(), '</td></tr></table>'));
		
		
		
		
		
		
		
		return '<table><tr><td align="middle">'.$oSpan->render() .'</td></tr><tr><td align="middle">'.parent::render().'</td></tr></table>';
	}	
}
?>