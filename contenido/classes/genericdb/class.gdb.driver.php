<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Root Driver for GenericDB
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package CONTENIDO Backend Classes
 * @version 1.3
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}
class cGenericDbDriver {

    protected $_sEncoding;

    protected $_oItemClassInstance;

    public function setEncoding($sEncoding) {
        $this->_sEncoding = $sEncoding;
    }

    public function setItemClassInstance($oInstance) {
        $this->_oItemClassInstance = $oInstance;
    }

    public function buildJoinQuery($destinationTable, $destinationClass, $destinationPrimaryKey, $sourceClass, $primaryKey) {
    }

    public function buildOperator($sField, $sOperator, $sRestriction) {
    }

}

class gdbDriver extends cGenericDbDriver {

    /**
     * @deprecated Class was renamed to cGenericDbDriver
     */
    public function __construct() {
        cDeprecated('Class was renamed to cGenericDbDriver');
    }

    /**
     * @deprecated Use __construct()
     */
    public function gdbDriver() {
        cDeprecated('Use __construct()');
        $this->__construct();
    }

}