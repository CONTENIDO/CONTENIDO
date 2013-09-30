<?php
/**
 * This file contains the root database driver for the generic db.
 *
 * @package Core
 * @subpackage GenericDB
 * @version SVN Revision $Rev:$
 *
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Root database driver.
 *
 * @package Core
 * @subpackage GenericDB
 */
class cGenericDbDriver {

    /**
     *
     * @var string
     */
    protected $_sEncoding;

    /**
     *
     * @var Item
     */
    protected $_oItemClassInstance;

    /**
     *
     * @param string $sEncoding
     */
    public function setEncoding($sEncoding) {
        $this->_sEncoding = $sEncoding;
    }

    /**
     *
     * @param Item $oInstance
     */
    public function setItemClassInstance($oInstance) {
        $this->_oItemClassInstance = $oInstance;
    }

    /**
     *
     * @param string $destinationTable
     * @param string $destinationClass
     * @param string $destinationPrimaryKey
     * @param string $sourceClass
     * @param string $primaryKey
     */
    public function buildJoinQuery($destinationTable, $destinationClass, $destinationPrimaryKey, $sourceClass, $primaryKey) {
    }

    /**
     *
     * @param string $sField
     * @param string $sOperator
     * @param string $sRestriction
     */
    public function buildOperator($sField, $sOperator, $sRestriction) {
    }
}
