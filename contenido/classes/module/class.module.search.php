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
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class performs the module search.
 *
 * @package    Core
 * @subpackage Backend
 */
class cModuleSearch extends cModuleHandler
{
    /**
     * @var cDb
     */
    protected $db;

    /**
     * @var int Items/Element per page.
     */
    protected $elementPerPage;

    /**
     * @var string Order by clause
     */
    protected $orderBy = '';

    /**
     * Sort order.
     *
     * @var string
     */
    protected $sortOrder = '';

    /**
     * @var string Filter string.
     */
    protected $filter = '';

    /**
     * @var string Where should be searched (all, description, type, input, output).
     */
    protected $searchIn = '';

    /**
     * @var int Selected page.
     */
    protected $selectedPage = 1;

    /**
     * @var int Page
     */
    protected $page = 1;

    /**
     * @var array Search result list.
     */
    protected $searchResult = [];

    /**
     * @var string Database table name.
     */
    protected $tableName = '';

    /**
     * Constructor to create an instance of this class.
     *
     * @param array $searchOptions
     * @throws cException
     */
    public function __construct(array $searchOptions)
    {
        parent::__construct();

        $this->elementPerPage = cSecurity::toInteger($searchOptions['elementPerPage']);
        $this->orderBy = $searchOptions['orderBy'];
        $this->sortOrder = $searchOptions['sortOrder'];
        $this->moduleType = $searchOptions['moduleType'];
        $this->filter = $searchOptions['filter'];
        $this->searchIn = $searchOptions['searchIn'];
        $this->selectedPage = cSecurity::toInteger($searchOptions['selectedPage']);
        $this->clientId = cSecurity::toInteger(
            !empty($searchOptions['client']) ? $searchOptions['client'] : cRegistry::getClientId()
        );
        $this->tableName = cDb::getTableName('mod');
        $this->db = cRegistry::getDb();
    }

    /**
     * Returns the result count.
     */
    public function getModulCount(): int
    {
        return count($this->searchResult);
    }

    /**
     * Search for modules in db columns and in filesystem (input and output files).
     *
     * @throws cDbException|cInvalidArgumentException
     */
    public function searchForAllModules(): array
    {
        // first fetch all modules for a client,
        // then apply filter on input and output from files,
        // then use the whitelisted id's and search for additional filter matches on a database
        $sql = "SELECT * FROM `%s` WHERE idclient = %d";
        $this->db->query($sql, $this->tableName, $this->clientId);
        $moduleIds = [];

        // filter modules based on input and output
        while ($this->db->nextRecord()) {
            $this->initWithDatabaseRow($this->db);
            if (
                cString::getStringLength(stripslashes($this->filter)) === 0
                || cString::findFirstPos($this->readInput(), stripslashes($this->filter)) !== false
                || cString::findFirstPos($this->readOutput(), stripslashes($this->filter)) !== false
            ) {
                $moduleIds[] = cSecurity::toInteger($this->db->f('idmod'));
            }
        }

        // build query using whitelisted id's
        $idFilter = '';
        if (count($moduleIds)) {
            $idFilter = sprintf(' OR `idmod` IN (%s)', implode(',', $moduleIds));
        }

        $this->db->query(
            "SELECT * FROM `%s` WHERE `idclient` = %d AND (
                    `type` LIKE '%s' OR `description` LIKE '%s' OR `name` LIKE  '%s' " . $idFilter . "
                ) ORDER BY %s %s",
            $this->tableName,
            $this->clientId,
            $this->moduleType,
            '%' . $this->filter . '%',
            '%' . $this->filter . '%',
            '%' . $this->filter . '%',
            $this->orderBy,
            $this->sortOrder
        );
        $result = [];

        while ($this->db->nextRecord()) {
            $this->initWithDatabaseRow($this->db);
            $result[cSecurity::toInteger($this->db->f('idmod'))] = $this->getModuleResultRow($this->db);
        }

        return $result;
    }

    /**
     * Main method for the class. Search for modules in db and in input and outputs files.
     *
     * @throws cDbException|cInvalidArgumentException
     */
    public function getModules(): array
    {
        $modules = [];

        switch ($this->searchIn) {
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
            case 'output': // Search for modulname_output.php
                $modules = $this->findModulWithOutput();
                break;
        }

        $this->searchResult = $modules;
        if ($this->elementPerPage > 0) {
            if (count($this->searchResult) < (($this->page - 1) * $this->elementPerPage)) {
                $this->page = 1;
            }

            if (
                $this->elementPerPage * ($this->page) >= count($this->searchResult) + $this->elementPerPage
                && $this->page != 1
            ) {
                $this->page--;
            }

            return array_slice(
                $modules,
                $this->elementPerPage * ($this->selectedPage - 1),
                $this->elementPerPage,
                true
            );
        } else {
            return $modules;
        }
    }

    /**
     * Search for modules in "name" column of module.
     *
     * @throws cDbException|cInvalidArgumentException
     */
    public function findeModulWithName(): array
    {
        $this->db->query(
            "SELECT * FROM `%s` WHERE `idclient` = %d AND (`type` LIKE '%s' AND `name` LIKE '%s') ORDER BY %s %s"
            ,
            $this->tableName,
            $this->clientId,
            $this->moduleType,
            '%' . $this->filter . '%',
            $this->orderBy,
            $this->sortOrder
        );
        $result = [];

        while ($this->db->nextRecord()) {
            $this->initWithDatabaseRow($this->db);
            $result[cSecurity::toInteger($this->db->f('idmod'))] = $this->getModuleResultRow($this->db);
        }

        return $result;
    }

    /**
     * Search for modules in an input file of the module.
     *
     * @throws cDbException|cInvalidArgumentException
     */
    public function findModulWithInput(): array
    {
        $this->db->query(
            "SELECT * FROM `%s` WHERE `idclient` = %d AND `type` LIKE '%s' ORDER BY %s %s",
            $this->tableName,
            $this->clientId,
            $this->moduleType,
            $this->orderBy,
            $this->sortOrder
        );
        $result = [];

        while ($this->db->nextRecord()) {
            $this->initWithDatabaseRow($this->db);
            if (
                cString::getStringLength(stripslashes($this->filter)) === 0
                || cString::findFirstPos($this->readInput(), stripslashes($this->filter)) !== false
            ) {
                $result[cSecurity::toInteger($this->db->f('idmod'))] = $this->getModuleResultRow($this->db);
            }
        }

        return $result;
    }

    /**
     * Search for modules in the output of the modules for the current client.
     *
     * @throws cDbException|cInvalidArgumentException
     */
    public function findModulWithOutput(): array
    {
        $this->db->query(
            "SELECT * FROM `%s` WHERE `idclient` = %d AND `type` LIKE '%s' ORDER BY %s %s",
            $this->tableName,
            $this->clientId,
            $this->moduleType,
            $this->orderBy,
            $this->sortOrder
        );
        $result = [];

        while ($this->db->nextRecord()) {
            $this->initWithDatabaseRow($this->db);
            if (
                cString::getStringLength(stripslashes($this->filter)) === 0
                || cString::findFirstPos($this->readOutput(), stripslashes($this->filter)) !== false
            ) {
                $result[cSecurity::toInteger($this->db->f('idmod'))] = $this->getModuleResultRow($this->db);
            }
        }

        return $result;
    }

    /**
     * Search for modules in the type column.
     *
     * @throws cDbException|cInvalidArgumentException
     */
    public function findModuleWithType(): array
    {
        $this->db->query(
            "SELECT * FROM `%s` WHERE `idclient` = %d AND (`type` LIKE '%s' AND `type` LIKE '%s') ORDER BY %s %s",
            $this->tableName,
            $this->clientId,
            $this->moduleType,
            '%' . $this->filter . '%',
            $this->orderBy,
            $this->sortOrder
        );
        $result = [];

        while ($this->db->nextRecord()) {
            $this->initWithDatabaseRow($this->db);
            $result[cSecurity::toInteger($this->db->f('idmod'))] = $this->getModuleResultRow($this->db);
        }

        return $result;
    }

    /**
     * Search for modules in the description column of modules.
     *
     * @throws cDbException|cInvalidArgumentException
     */
    public function findModuleWithDescription(): array
    {
        $this->db->query(
            "SELECT * FROM `%s` WHERE `idclient` = %d AND (`type` LIKE '%s' AND `description` LIKE '%s') ORDER BY %s %s",
            $this->tableName,
            $this->clientId,
            $this->moduleType,
            '%' . $this->filter . '%',
            $this->orderBy,
            $this->sortOrder
        );
        $result = [];

        while ($this->db->nextRecord()) {
            $this->initWithDatabaseRow($this->db);
            $result[cSecurity::toInteger($this->db->f('idmod'))] = $this->getModuleResultRow($this->db);
        }

        return $result;
    }

    /**
     * Returns module table query result row.
     *
     * @return array{
     *     name: string,
     *     description: string,
     *     error: string,
     *     input: string|false,
     *     output: string|false,
     * }
     * @throws cInvalidArgumentException
     */
    protected function getModuleResultRow(cDb $db): array
    {
        return [
            'name' => $db->f('name'),
            'description' => $db->f('description') ?? '',
            'error' => $db->f('error'),
            'input' => $this->readInput(),
            'output' => $this->readOutput()
        ];
    }
}
