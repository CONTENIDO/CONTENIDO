<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Foldable pager for menus
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.2
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2005-05-11
 *   
 *   $Id: class.widgets.pager.php,v 1.2 2005/05/11 13:28:17 timo.hummel Exp $
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


cInclude("classes", "widgets/class.widgets.foldingrow.php");

class cObjectPager extends cFoldingRow
{
	var $_pagerLink;
	var $_parameterToAdd;
	
	function cObjectPager ($uuid, $items, $itemsperpage, $currentpage, $link, $parameterToAdd, $id='')
	{
      if ((int) $currentpage == 0) {
        $currentpage = 1;
      }
    
	  if($id == '')
	  {
		  cFoldingRow::cFoldingRow($uuid, i18n("Paging"));
	  }
	  else
	  {
      cFoldingRow::cFoldingRow($uuid, i18n("Paging"), $id);
	  }
		
		if (!is_object($link))
		{
			cError(__FILE__, __LINE__, "Parameter link is not an object");
			return false;	
		}
		$this->_cPager = new cPager($items, $itemsperpage, $currentpage);
		$this->_pagerLink = $link;
		$this->_parameterToAdd = $parameterToAdd;
		
	}
	
	function render ($bContentOnly = 0)
	{
        #Do not display Page navigation if there is only one Page and we are not in newsletter section
        if ($this->_cPager->getMaxPages() == 1) {
            $this->_headerRow->setStyle("display:none");
            $this->_contentRow->setStyle("display:none");
        }
        
		$items = $this->_cPager->getPagesInRange();
		//echo '<pre>';
        //print_r($items);
        //echo '</pre>';
		$link = $this->_pagerLink;
		
		if (!$this->_cPager->isFirstPage())
		{
			$img = new cHTMLImage("images/paging/first.gif");
            
			$link->setAlt(i18n("First page"));
			$link->setContent($img);
            $link->setCustom($this->_parameterToAdd, 1);
			$output .= $link->render();
			$output .= " ";
			
			$img = new cHTMLImage("images/paging/previous.gif");
			$link->setAlt(i18n("Previous page"));
			$link->setContent($img);
           
            $link->setCustom($this->_parameterToAdd, $this->_cPager->_currentPage - 1);
			
			$output .= $link->render();
			$output .= " ";			
		} else {
			$output .= '<img src="images/spacer.gif" width="8"> ';	
			$output .= '<img src="images/spacer.gif" width="8">';
		}
		foreach ($items as $key => $item)
		{
			$link->setContent($key);
			$link->setAlt(sprintf(i18n("Page %s"), $key));
            $link->setCustom($this->_parameterToAdd, $key);
			
			switch ($item)
			{
				case "|": 		$output .= "..."; break;
				case "current":	$output .= '<span class="cpager_currentitem">'.$key."</span>"; break;
				default:		$output .= $link->render();
			}
			
			$output .= " ";	
		}
		
		if (!$this->_cPager->isLastPage())
		{
			$img = new cHTMLImage("images/paging/next.gif");
			$link->setAlt(i18n("Next page"));
			$link->setContent($img);
            $link->setCustom($this->_parameterToAdd, $this->_cPager->_currentPage + 1);
			
			$output .= $link->render();
			$output .= " ";
			
			$img = new cHTMLImage("images/paging/last.gif");
			
            $link->setCustom($this->_parameterToAdd, $this->_cPager->getMaxPages());
			$link->setAlt(i18n("Last page"));
			$link->setContent($img);
			
			$output .= $link->render();
			$output .= " ";			
		} else {
			$output .= '<img src="images/spacer.gif" width="8"> ';	
			$output .= '<img src="images/spacer.gif" width="8">';
		}
		
		$this->_contentData->setAlignment("center");
		$this->_contentData->setClass("foldingrow_content");
        
        #Do not display Page navigation if there is only one Page and we are not in newsletter section
        if ($this->_cPager->getMaxPages() == 1) {
            $output = '';
        }
        
		$this->_contentData->setContent($output);
		
        if ($bContentOnly) {
            return $output;
        } else {
            return cFoldingRow::render();
        }
		
	}	
}

/**
 * cPager
 * Basic pager class without presentation logic
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cPager
{
	/**
	 * Amount of items
     * @var integer
     * @access private
	 */	
	var $_items;
	
	/**
	 * Item padding (before and after the current item)
     * @var integer
     * @access private
	 */	
	var $_itemPadding;
	
	/**
	 * Items on the left side
     * @var integer
     * @access private
	 */	
	var $_previousItems;
	
	/**
	 * Items on the right side
     * @var integer
     * @access private
	 */	
	var $_nextItems;
	
	/**
	 * Current page
     * @var integer
     * @access private
	 */	
	var $_currentPage;
	
	/**
	 * Items per page
     * @var integer
     * @access private
	 */	
	var $_itemsPerPage;
	
	/**
     * Constructor Function
	 * Initializes the pager
	 *
     * @param $items 		int Amount of items
	 * @param $itemsPerPage int Items displayed per page
	 * @param $currentPage	int Defines the current page
     */		
	function cPager ($items, $itemsPerPage, $currentPage)
	{
        
		$this->_items = $items;
		$this->_itemsPerPage = $itemsPerPage;
		$this->_currentPage = $currentPage;
		
		/* Default values. */
		$this->_itemPadding = 2;
		$this->_previousItems = 2;
		$this->_nextItems = 2;
	}

	/**
     * Returns if the currentPage pointer is the first page.
	 *
	 * @return boolean True if we're on the first page.
     */				
	function isFirstPage ()
	{
		if ($this->_currentPage == 1)
		{
			return true;
		}
		
		return false;
	}

	/**
     * Returns if the currentPage pointer is the last page.
	 *
	 * @return boolean True if we're on the last page.
     */		
	function isLastPage ()
	{
		if ($this->_currentPage == $this->getMaxPages())
		{
			return true;
		}
		
		return false;
	}
	
	/**
     * Returns the amount of pages.
	 *
	 * @return int Page count
     */		
	function getMaxPages ()
	{
		if ($this->_items == 0){
			return 1;	
		} else if ($this->_itemsPerPage == 0) {
            return 1;
        } else {
			return (ceil($this->_items / $this->_itemsPerPage));
		}	
	}

	/**
     * Returns an array with the pager structure.
	 *
	 * Array format:
	 * Key  : Page Number
	 * Value: | for "...", "current" for the current item, page number otherwise
	 *
	 * @return array Pager structure
     */		
	function getPagesInRange ()
	{
		$items = array();

		$maxPages = $this->getMaxPages();
		
		if (($this->_itemPadding * 3) + $this->_previousItems + $this->_nextItems > $maxPages)
		{
			/* Disable item padding */
			for ($i = 1; $i < $this->getMaxPages() + 1; $i++)
			{
				$items[$i] = $i;	
			}
		} else {
			for ($i=1;$i<$this->_previousItems+1; $i++)
			{
				if ($i <= $maxPages && $i >= 1)
				{ 
					$items[$i] = $i;
				}
				
				if ($i+1 <= $maxPages && $i >= 2)
				{
					$items[$i+1] = "|";
				}
			}
	
			for ($i = $this->_currentPage - $this->_itemPadding; $i< $this->_currentPage + $this->_itemPadding + 1; $i++)
			{
				if ($i <= $maxPages && $i >= 1)
				{ 			
	    			$items[$i] = $i;
				}
				
				if ($i+1 <= $maxPages && $i >= 2)
				{
					$items[$i+1] = "|";
				}
			}		
			
			for ($i=($this->getMaxPages()-$this->_nextItems)+1; $i < $this->getMaxPages()+1; $i++)
			{
				if ($i <= $maxPages && $i >= 2)
				{
					$items[$i] = $i;
				}	
			}
		}		
		
		$items[$this->_currentPage] = 'current';
		
		return ($items);
	}
	
}
?>
