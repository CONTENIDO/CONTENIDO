<?php

/**
 * This file contains the record set and database interaction class.
 *
 * @package    Core
 * @subpackage Database
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains functions for handling record sets and interaction with database in CONTENIDO.
 *
 * @package    Core
 * @subpackage Database
 */
class cDb extends cDbDriverHandler
{

    /**
     * Link ID resource
     *
     * @var NULL|resource|mysqli
     */
    protected $_linkId = NULL;

    /**
     * Query ID resource
     *
     * @var NULL|resource|mysqli_result
     */
    protected $_queryId = NULL;

    /**
     * Active record set data.
     *
     * @var array|false|NULL
     */
    protected $_record = [];

    /**
     * Active row count.
     *
     * @var int
     */
    protected $_row = 0;

    /**
     * Database error number, if available.
     *
     * @var int
     */
    protected $_errorNumber = 0;

    /**
     * Database error message, if available.
     *
     * @var string
     */
    protected $_errorMessage = '';

    /**
     * @inheritdoc
     * @return NULL|resource|mysqli_result
     */
    public function getQueryId()
    {
        return $this->_queryId;
    }

    /**
     * @inheritdoc
     * @param NULL|resource|mysqli_result $queryId
     */
    public function setQueryId($queryId)
    {
        $this->_queryId = $queryId;
    }

    /**
     * @inheritdoc
     * @return NULL|resource|mysqli
     */
    public function getLinkId()
    {
        return $this->_linkId;
    }

    /**
     * @inheritdoc
     * @param NULL|resource|mysqli $linkId
     */
    public function setLinkId($linkId)
    {
        $this->_linkId = $linkId;
    }

    /**
     * @inheritdoc
     */
    public function getRecord()
    {
        return $this->_record;
    }

    /**
     * @inheritdoc
     */
    public function setRecord($record)
    {
        $this->_record = $record;
    }

    /**
     * Return the current row count.
     */
    public function getRow(): int
    {
        return $this->_row;
    }

    /**
     * Sets the current row count.
     * Do not set it manually unless you know what you are doing.
     *
     * @param int $row Current row count
     */
    public function setRow(int $row)
    {
        $this->_row = $row;
    }

    /**
     * Increments current row count by 1.
     * Do not set it manually unless you know what you are doing.
     */
    public function incrementRow()
    {
        $this->_row += 1;
    }

    /**
     * @inheritdoc
     */
    public function getErrorMessage(): string
    {
        return $this->_errorMessage;
    }

    /**
     * @inheritdoc
     */
    public function setErrorMessage(string $errorMessage)
    {
        $this->_errorMessage = $errorMessage;
    }

    /**
     * @inheritdoc
     */
    public function getErrorNumber(): int
    {
        return $this->_errorNumber;
    }

    /**
     * @inheritdoc
     */
    public function setErrorNumber(int $errorNumber)
    {
        $this->_errorNumber = $errorNumber;
    }

    /**
     * Shorter alias for {@see cRegistry::getDbTableName()}.
     * @since CONTENIDO 4.10.2
     */
    public static function getTableName(string $tableName): string
    {
        return cRegistry::getDbTableName($tableName);
    }

}
