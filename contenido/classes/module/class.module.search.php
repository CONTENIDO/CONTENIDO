<?php

/**
 * This file contains the module search class.
 *
 * @todo refactor documentation
 *
 * @package    Core
 * @subpackage Backend
 * @author     Rusmir Jusufovic
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class performs the module search.
 *
 * @package    Core
 * @subpackage Backend
 */
class cModuleSearch extends cModuleHandler {

    /**
     * Items/Element per page.
     *
     * @var int
     */
    protected $_elementPerPage = '';

    /**
     * Order by.
     *
     * @var string
     */
    protected $_orderBy = '';

    /**
     * Sort order.
     *
     * @var string
     */
    protected $_sortOrder = '';

    /**
     * Module type.
     *
     * @var string
     */
    protected $_moduleType = '';

    /**
     * Filter string.
     *
     * @var string
     */
    protected $_filter = '';

    /**
     * Where should be searched (all, description, type, input, output).
     *
     * @var string
     */
    protected $_searchIn = '';

    /**
     * Selected page.
     *
     * @var int
     */
    protected $_selectedPage = 1;

    /**
     * Page.
     *
     * @var int
     */
    protected $_page = 1;

    /**
     * Result saved in a array.
     *
     * @var array
     */
    protected $_result = [];

    /**
     * Db table name.
     *
     * @var string
     */
    protected $_table = '';

    /**
     * Id of client to search for modules.
     *
     * @var int
     */
    protected $_client = 0;

    /**
     * Constructor to create an instance of this class.
     *
     * @param array $searchOptions
     *
     * @throws cException
     */
    public function __construct($searchOptions) {
        parent::__construct();

        $this->_elementPerPage = $searchOptions['elementPerPage'];
        $this->_orderBy = $searchOptions['orderBy'];
        $this->_sortOrder = $searchOptions['sortOrder'];
        $this->_moduleType = $searchOptions['moduleType'];
        $this->_filter = $searchOptions['filter'];
        $this->_searchIn = $searchOptions['searchIn'];
        $this->_selectedPage = $searchOptions['selectedPage'];

        $this->_client = !empty($searchOptions['client']) ? $searchOptions['client'] : cRegistry::getClientId();
        $this->_table = cRegistry::getConfig()['tab']['mod'];
    }

    /**
     * Print a array.
     *
     * @param array $arg
     */
    private function _echo($arg) {
        echo '<pre>' . print_r($arg) . '</pre>';
    }

    /**
     * Count result.
     *
     * @return int
     *         count in result
     */
    public function getModulCount() {
        return count($this->_result);
    }

    /**
     * Search for modules in db columns and in filesystem (input and
     * output files).
     *
     * @return array
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function searchForAllModules() {
        $db = cRegistry::getDb();

        // first fetch all modules for client
        // then apply _filter on input and output from files
        // then use the whitelisted id's and search for additional filter matches on database
        $sql = "SELECT * FROM `%s` WHERE idclient = %d";
        $db->query($sql, $this->_table, $this->_client);
        $moduleIds = [];

        // filter modules based on input and output
        while ($db->nextRecord()) {
            $this->initWithDatabaseRow($db);
            if (cString::getStringLength(stripslashes($this->_filter)) === 0
                    || cString::findFirstPos($this->readInput(), stripslashes($this->_filter)) !== false
                    || cString::findFirstPos($this->readOutput(), stripslashes($this->_filter)) !== false) {
                    $moduleIds[] = $db->f('idmod');
            }
        }

        // build query using whitelisted id's
        $idFilter = "";
        foreach ($moduleIds as $moduleId) {
            $idFilter .= " OR idmod=" . (int) $moduleId;
        }
        $sql = "SELECT * FROM `%s` WHERE idclient = %d AND (
                    type LIKE '%s'
                    AND type LIKE '%s'
                    OR description LIKE '%s'
                    OR name LIKE  '%s'" . $idFilter . "
                ) ORDER BY %s %s";

        $db->query($sql,
            $this->_table, $this->_client, $this->_moduleType, '%' . $this->_filter . '%', '%' . $this->_filter . '%',
            '%' . $this->_filter . '%', $this->_orderBy, $this->_sortOrder
        );
        $result = [];

        while ($db->nextRecord()) {
            $this->initWithDatabaseRow($db);
            $result[$db->f('idmod')] = $this->_getModuleResultRow($db);
        }

        return $result;
    }

    /**
     * Main method for the class. Search for modules in db and in input and
     * outputs files.
     *
     * @return array
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function getModules() {
        $modules = [];

        switch ($this->_searchIn) {
            case 'all':
                $modules = $this->searchForAllModules();
                break;
            case 'name':
                $modules = $this->findeModulWithName();
                break;
            case 'description':
                $modules = $this->findModuleWithDescription();
                break;
            case 'type':
                $modules = $this->findModuleWithType();
                break;
            case 'input': // Search for modulname_input.php
                $modules = $this->findModulWithInput();
                break;
            case 'output': // Search fro modulname_output.php
                $modules = $this->findModulWithOutput();
                break;
        }

        $this->_result = $modules;
        if ($this->_elementPerPage > 0) {
            if (count($this->_result) < (($this->_page - 1) * $this->_elementPerPage)) {
                $this->_page = 1;
            }

            if ($this->_elementPerPage * ($this->_page) >= count($this->_result) + $this->_elementPerPage && $this->_page != 1) {
                $this->_page--;
            }
            return array_slice($modules, $this->_elementPerPage * ($this->_selectedPage - 1), $this->_elementPerPage, true);
        } else {
            return $modules;
        }
    }

    /**
     * Search for modules in "name" column of module.
     *
     * @return array
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function findeModulWithName() {
        $db = cRegistry::getDb();

        $sql = "SELECT * FROM `%s` WHERE idclient = %d AND (type LIKE '%s' AND name LIKE '%s') ORDER BY %s %s ";
        $db->query(
            $sql, $this->_table, $this->_client, $this->_moduleType, '%' . $this->_filter . '%',
            $this->_orderBy, $this->_sortOrder
        );
        $result = [];

        while ($db->nextRecord()) {
            $this->initWithDatabaseRow($db);
            $result[$db->f('idmod')] = $this->_getModuleResultRow($db);
        }

        return $result;
    }

    /**
     * Search for modules in input file of the module.
     *
     * @return array
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function findModulWithInput() {
        $db = cRegistry::getDb();

        $sql = "SELECT * FROM `%s` WHERE idclient = %d AND type LIKE '%s' ORDER BY %s %s";
        $db->query($sql, $this->_table, $this->_client, $this->_moduleType, $this->_orderBy, $this->_sortOrder);
        $result = [];

        while ($db->nextRecord()) {
            $this->initWithDatabaseRow($db);
            if (cString::getStringLength(stripslashes($this->_filter)) === 0
                || cString::findFirstPos($this->readInput(), stripslashes($this->_filter)) !== false) {
                $result[$db->f('idmod')] = $this->_getModuleResultRow($db);
            }
        }

        return $result;
    }

    /**
     * Search for modules in output of modules of current client.
     *
     * @return array
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function findModulWithOutput() {
        $db = cRegistry::getDb();

        $sql = "SELECT * FROM `%s` WHERE idclient = %d AND type LIKE '%s' ORDER BY %s %s";
        $db->query($sql, $this->_table, $this->_client, $this->_moduleType, $this->_orderBy, $this->_sortOrder);
        $result = [];

        while ($db->nextRecord()) {
            $this->initWithDatabaseRow($db);
            if (cString::getStringLength(stripslashes($this->_filter)) === 0
                || cString::findFirstPos($this->readOutput(), stripslashes($this->_filter)) !== false) {
                $result[$db->f('idmod')] = $this->_getModuleResultRow($db);
            }
        }

        return $result;
    }

    /**
     * Search for modules in type column.
     *
     * @return array
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function findModuleWithType() {
        $db = cRegistry::getDb();

        $sql = "SELECT * FROM `%s` WHERE idclient = %d AND (type LIKE '%s' AND type LIKE '%s') ORDER BY %s %s";
        $db->query(
            $sql, $this->_table, $this->_client, $this->_moduleType, '%' . $this->_filter . '%',
            $this->_orderBy, $this->_sortOrder
        );
        $result = [];

        while ($db->nextRecord()) {
            $this->initWithDatabaseRow($db);
            $result[$db->f('idmod')] = $this->_getModuleResultRow($db);
        }

        return $result;
    }

    /**
     * Search for modules in description column of modules.
     *
     * @return array
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function findModuleWithDescription() {
        $db = cRegistry::getDb();

        $sql = "SELECT * FROM `%s` WHERE idclient = %d AND (type LIKE '%s' AND description LIKE '%s') ORDER BY %s %s";
        $db->query(
            $sql, $this->_table, $this->_client, $this->_moduleType, '%' . $this->_filter . '%',
            $this->_orderBy, $this->_sortOrder
        );
        $result = [];

        while ($db->nextRecord()) {
            $this->initWithDatabaseRow($db);
            $result[$db->f('idmod')] = $this->_getModuleResultRow($db);
        }

        return $result;
    }

    /**
     * Returns module table query result row
     * @param cDb $db
     * @return array
     * @throws cInvalidArgumentException
     */
    protected function _getModuleResultRow($db) {
        return [
            'name' => $db->f('name'),
            'description' => $db->f('description') ?? '',
            'error' => $db->f('error'),
            'input' => $this->readInput(),
            'output' => $this->readOutput()
        ];
    }
}
