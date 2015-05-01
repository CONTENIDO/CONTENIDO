<?php

/**
 * This file contains the record set and database interaction class.
 *
 * @package Core
 * @subpackage Database
 * @version SVN Revision $Rev:$
 *
 * @author Dominik Ziegler
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains functions for handling record sets and interaction with
 * database in CONTENIDO.
 *
 * @package Core
 * @subpackage Database
 */
class cDb extends cDbDriverHandler {

    /**
     * Link ID resource
     *
     * @var resource
     */
    protected $_linkId = NULL;

    /**
     * Query ID resource
     *
     * @var resource
     */
    protected $_queryId = NULL;

    /**
     * Active record set data.
     *
     * @var array
     */
    protected $_record = array();

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
     * Returns the query ID resource.
     *
     * @return NULL|resource
     */
    public function getQueryId() {
        return $this->_queryId;
    }

    /**
     * Sets the query ID resource.
     * Do not set it manually unless you know what you are doing.
     *
     * @param resource $queryId
     *         query ID resource
     */
    public function setQueryId($queryId) {
        $this->_queryId = $queryId;
    }

    /**
     * Returns the link ID resource.
     *
     * @return NULL|resource
     */
    public function getLinkId() {
        return $this->_linkId;
    }

    /**
     * Sets the link ID resource.
     * Do not set it manually unless you know what you are doing.
     *
     * @param resource $linkId
     *         link ID resource
     */
    public function setLinkId($linkId) {
        $this->_linkId = $linkId;
    }

    /**
     * Returns the current record data.
     *
     * @return array
     */
    public function getRecord() {
        return $this->_record;
    }

    /**
     * Sets the current record data set.
     * Do not set it manually unless you know what you are doing.
     *
     * @param array $record
     *         current record set data
     */
    public function setRecord($record) {
        $this->_record = $record;
    }

    /**
     * Return the current row count.
     *
     * @return int
     */
    public function getRow() {
        return $this->_row;
    }

    /**
     * Sets the current row count.
     * Do not set it manually unless you know what you are doing.
     *
     * @param int $row
     *         current row count
     */
    public function setRow($row) {
        $this->_row = (int) $row;
    }

    /**
     * Increments current row count by 1.
     * Do not set it manually unless you know what you are doing.
     */
    public function incrementRow() {
        $this->_row += 1;
    }

    /**
     * Returns error message of last occurred error from database.
     *
     * @return string
     *         database error message
     */
    public function getErrorMessage() {
        return $this->_errorMessage;
    }

    /**
     * Sets the current error message from database.
     *
     * @param string $errorMessage
     *         current error message
     */
    public function setErrorMessage($errorMessage) {
        $this->_errorMessage = $errorMessage;
    }

    /**
     * Returns error code of last occurred error from database.
     *
     * @return int
     *         database error code
     */
    public function getErrorNumber() {
        return $this->_errorNumber;
    }

    /**
     * Sets the current error number from database.
     *
     * @param int $errorNumber
     *         current error number
     */
    public function setErrorNumber($errorNumber) {
        $this->_errorNumber = (int) $errorNumber;
    }
}
