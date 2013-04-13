<?php
/**
 * This file contains the root database driver for the generic db.
 *
 * @package          Core
 * @subpackage       GenericDB
 * @version          SVN Revision $Rev:$
 *
 * @author           Timo Hummel
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Root database driver
 *
 * @package    Core
 * @subpackage GenericDB
 */
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
