<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 *
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.0
 * @author
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

class cDatatype
{
    /* Effective value */
    protected $_mValue;

    /* Displayed value */
    protected $_mDisplayedValue;

    public function __construct()
    {
    }

    /**
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cDatatype()
    {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }

    /**
     * Sets this datatype to a specific value
     * @param  mixed  $value
     */
    public function set($value)
    {
    }

    /**
     * Parses the given value to transfer into the datatype's format
     * @param  mixed  $value
     */
    public function parse($value)
    {
    }

    /**
     * Returns the effective value
     * @return  mixed
     */
    public function get()
    {
    }

    /**
     * Renders the displayed value
     */
    public function render()
    {
    }
}

?>