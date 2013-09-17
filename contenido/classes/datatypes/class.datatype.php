<?php
/**
 * This file contains the abstract datatype class.
 *
 * @package          Core
 * @subpackage       Datatype
 * @version          SVN Revision $Rev:$
 *
 * @author           unknown
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Abstract datatype class.
 *
 * @package          Core
 * @subpackage       Datatype
 */
class cDatatype {

    /**
     * Effective value
     * @var mixed
     */
    protected $_mValue;

    /**
     * Displayed value
     * @var mixed
     */
    protected $_mDisplayedValue;

    /**
     *
     */
    public function __construct() {
    }

    /**
     * Sets this datatype to a specific value
     *
     * @param  mixed $value
     */
    public function set($value) {
    }

    /**
     * Parses the given value to transfer into the datatype's format
     *
     * @param  mixed $value
     */
    public function parse($value) {
    }

    /**
     * Returns the effective value
     * @return  mixed
     */
    public function get() {
    }

    /**
     * Renders the displayed value
     */
    public function render() {
    }
}

?>