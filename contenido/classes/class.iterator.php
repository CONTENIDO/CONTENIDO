<?php
/**
 * This file contains the iterator class.
 *
 * @package Core
 * @subpackage Util
 * @author Unknown
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * A class which represents the C/C++/JAVA Iterator support.
 *
 * Iterating items is a mechanism to "step" trough a list of defined items.
 * Basically, the iterator is similar to an array, but provides easy functions
 * to step trough the list.
 *
 * An instance of an iterator is usually created by a class returning multiple
 * items and automatically filled using the $aItems parameter of the
 * constructor, and then returned to the caller.
 *
 * The caller receives the iterator object and can step trough all items using
 * the "next" method.
 *
 * @package Core
 * @subpackage Util
 */
class cIterator {

    /**
     * Holds the items to iterate.
     *
     * @var array
     */
    protected $_aIteratorItems;

    /**
     * Holds the keys of the array which should be iterated
     *
     * @var array
     */
    protected $_keys;

    /**
     * Constructor to create an instance of this class.
     *
     * This function initializes the constructor, adds the passed items
     * and moves the iterator to the first element.
     *
     * @param array $aItems
     *         Items to add
     */
    public function __construct($aItems) {
        $this->_aIteratorItems = is_array($aItems) ? $aItems : array();
        $this->reset();
    }

    /**
     * Resets the iterator to the first element.
     *
     * This function moves the iterator to the first element
     */
    public function reset() {
        $this->_keys = array_keys($this->_aIteratorItems);
    }

    /**
     * Returns the next item in the iterator.
     *
     * This function returns the item, or false if no items are left.
     *
     * @return mixed
     *         item or false if no items are left
     */
    public function next() {
        $key = array_shift($this->_keys);
        return isset($this->_aIteratorItems[$key]) ? $this->_aIteratorItems[$key] : false;
    }

    /**
     * Returns the number of items in the iterator.
     *
     * @return int
     *         number of items
     */
    public function count() {
        return count($this->_aIteratorItems);
    }
}
