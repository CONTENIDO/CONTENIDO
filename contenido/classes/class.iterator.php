<?php
/*****************************************
* File      :   $RCSfile: class.iterator.php,v $
* Project   :   Contenido
* Descr     :   Iterator class
* Modified  :   $Date: 2005/01/06 15:43:03 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.iterator.php,v 1.2 2005/01/06 15:43:03 timo.hummel Exp $
******************************************/

/**
 * cIterator: A class which represents the C/C++/JAVA Iterator support.
 *
 * Iterating items is a mechanism to "step" trough a list of defined items.
 * Basically, the iterator is similar to an array, but provides easy functions
 * to step trough the list.
 *
 * An instance of an iterator is usually created by a class returning multiple
 * items and automatically filled using the $aItems parameter of the constructor,
 * and then returned to the caller.
 * 
 * The caller receives the iterator object and can step trough all items using
 * the "next" method.
 *
 * @todo Add more stepping methods, as well as retrieving items
 *
 */
class cIterator
{
    /**
     * Holds the items which should be iterated
     * @var array
     */	
	var $_aIteratorItems;
	
	/**
     * Iterator constructor
     *
     * This function initializes the constructor, adds the passed items
     * and moves the iterator to the first element.
     *
     * @param $aItems array Items to add
     * @return none
     */	
	function cIterator ($aItems)
	{
		if (is_array($aItems))
		{
			$this->_aIteratorItems = $aItems;	
		} else {
			$this->_aIteratorItems = array();	
		}
		
		$this->reset();
	}

	/**
     * reset: Resets the iterator to the first element
     *
     * This function moves the iterator to the first element
	 * 
     * @return none
     */		
	function reset ()
	{
		reset($this->_aIteratorItems);	
	}

	/**
     * next: Returns the next item in the iterator
     *
     * This function returns the item, or false if no
     * items are left.
	 * 
     * @return mixed item or false if nothing was found
     */			
	function next ()
	{
		$item = each($this->_aIteratorItems);

		if ($item === false)
		{
			return false;
		} else {
			return $item["value"];	
		}
	}

	/**
     * count: Returns the number of items in the iterator
	 * 
     * @return int Number of items
     */		
	function count ()
	{
		return count($this->_aIteratorItems);
	}
}
?>