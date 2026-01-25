<?php

/**
 * This file contains the root database driver for the generic db.
 *
 * @package    Core
 * @subpackage GenericDB
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Root database driver.
 *
 * @package    Core
 * @subpackage GenericDB
 */
abstract class cGenericDbDriver
{

    /**
     * @var string
     */
    protected $_sEncoding;

    /**
     * @var Item
     */
    protected $_oItemClassInstance;

    /**
     * @param string $sEncoding
     */
    public function setEncoding($sEncoding)
    {
        $this->_sEncoding = $sEncoding;
    }

    /**
     * @param Item $oInstance
     */
    public function setItemClassInstance($oInstance)
    {
        $this->_oItemClassInstance = $oInstance;
    }

    /**
     * @param string $destinationTable
     * @param string $destinationClass
     * @param string $destinationPrimaryKey
     * @param string $sourceClass
     * @param string $primaryKey
     * @return array
     */
    abstract public function buildJoinQuery(
        string $destinationTable,
        string $destinationClass,
        string $destinationPrimaryKey,
        string $sourceClass,
        string $primaryKey
    ): array;

    /**
     * @param string $field
     * @param string $operator
     * @param mixed $restriction
     * @return string
     */
    abstract public function buildOperator(
        string $field,
        string $operator,
        $restriction
    ): string;
}
