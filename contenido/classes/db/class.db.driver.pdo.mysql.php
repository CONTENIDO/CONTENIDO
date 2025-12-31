<?php

/**
 * This file contains the PDO MySQL database driver class.
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
 * This class contains functions for database interaction based on PDO in CONTENIDO.
 *
 * @experimental This class is still in an experimental state, don't use it on a production environment!
 *
 * Configurable via global $cfg['db']['connection'] configuration as follows:
 * <pre>
 * - host (string) Hostname or ip
 * - database (string) Database name
 * - user (string) User name
 * - password (string) User password
 * - charset (string) Optional, connection charset
 * </pre>
 *
 * @package    Core
 * @subpackage Database
 */
class cDbDriverPdoMysql extends cDbDriverAbstract
{
    /**
     * @var ?PDO
     */
    protected $_pdo;

    /**
     * @inheritdoc
     */
    public function check(): bool
    {
        return class_exists('PDO') && in_array('mysql', PDO::getAvailableDrivers());
    }

    /**
     * @inheritdoc
     * @throws cDbException
     */
    public function connect(): ?PDO
    {
        if (isset($this->_dbCfg['connection'])) {
            $connectConfig = $this->_dbCfg['connection'];
        }

        if (empty($connectConfig) || !isset($connectConfig['host']) || !isset($connectConfig['user']) || !isset($connectConfig['password'])) {
            $this->_handler->halt('Database connection settings incomplete');
            return NULL;
        }

        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $connectConfig['host'], $connectConfig['database'], $connectConfig['charset'] ?? 'utf8');

        try {
            $this->_pdo = new PDO($dsn, $connectConfig['user'], $connectConfig['password']);
            $this->_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $this->_handler->halt('Connection failed: ' . $e->getMessage());
            return NULL;
        }

        return $this->_pdo;
    }

    /**
     * @inheritdoc
     */
    public function buildInsert(string $tableName, array $fields): string
    {
        $fieldList = implode(', ', array_map(function($field) { return "`$field`"; }, array_keys($fields)));
        $valueList = implode(', ', array_map(function($value) { return is_null($value) ? 'NULL' : "'" . $this->escape($value) . "'"; }, $fields));
        return sprintf('INSERT INTO `%s` (%s) VALUES (%s)', $tableName, $fieldList, $valueList);
    }

    /**
     * @inheritdoc
     */
    public function buildUpdate(string $tableName, array $fields, array $whereClauses): string
    {
        $updateList = implode(', ', array_map(function($field, $value) {
            return "`$field` = " . (is_null($value) ? 'NULL' : "'" . $this->escape($value) . "'");
        }, array_keys($fields), $fields));

        $whereList = implode(' AND ', array_map(function($field, $value) {
            return "`$field` " . (is_null($value) ? 'IS NULL' : "= '" . $this->escape($value) . "'");
        }, array_keys($whereClauses), $whereClauses));

        return sprintf('UPDATE `%s` SET %s WHERE %s', $tableName, $updateList, $whereList);
    }

    /**
     * @inheritdoc
     * @return PDOStatement|false
     */
    public function query(string $statement): bool
    {
        /** @var PDOStatement $queryId */
        $queryId = $this->_pdo->query($statement);

        $this->_handler->setQueryId($queryId);
        $this->_handler->setRow(0);
        $this->_handler->setErrorNumber($this->getErrorNumber());
        $this->_handler->setErrorMessage($this->getErrorMessage());

        return $queryId;
    }

    /**
     * @inheritdoc
     */
    public function nextRecord(): bool
    {
        /** @var PDOStatement $queryId */
        $queryId = $this->_handler->getQueryId();
        $record = $queryId->fetch(PDO::FETCH_BOTH);

        $this->_handler->setRecord($record);
        $this->_handler->incrementRow();
        $this->_handler->setErrorNumber($this->getErrorNumber());
        $this->_handler->setErrorMessage($this->getErrorMessage());

        return is_array($record);
    }

    /**
     * @inheritdoc
     * @return false|null|object|stdClass|string
     */
    public function getResultObject(?string $className = NULL)
    {
        $result = NULL;
        /** @var PDOStatement $queryId */
        $queryId = $this->_handler->getQueryId();

        if ($queryId) {
            $result = $queryId->fetchObject($className);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function affectedRows(): int
    {
        return $this->numRows();
    }

    /**
     * @inheritdoc
     */
    public function numRows(): int
    {
        /** @var PDOStatement $queryId */
        $queryId = $this->_handler->getQueryId();
        return ($queryId) ? $queryId->rowCount() : 0;
    }

    /**
     * @inheritdoc
     */
    public function numFields(): int
    {
        /** @var PDOStatement $queryId */
        $queryId = $this->_handler->getQueryId();
        return ($queryId) ? $queryId->columnCount() : 0;
    }

    /**
     * @inheritdoc
     */
    public function free()
    {
        /** @var PDOStatement $queryId */
        $queryId = $this->_handler->getQueryId();
        if ($queryId) {
            $queryId->closeCursor();
            $this->_handler->setQueryId(NULL);
        }
    }

    /**
     * @inheritdoc
     */
    public function escape($string)
    {
        if (is_string($string)) {
            return $this->_pdo->quote($string);
        } else {
            return $string;
        }
    }

    /**
     * @inheritdoc
     */
    public function seek(int $pos = 0): int
    {
        // PDO does not support seeking in the same way as MySQLi.
        // This method can be left unimplemented or throw an exception.
        // throw new Exception('Seek operation is not supported in PDO.');
        // TODO Implement seek for PDO or remove usage of seek() for all!
        return 0;
    }

    /**
     * @inheritdoc
     * @throws cDbException
     */
    public function getMetaData(string $tableName, bool $full = false): array
    {
        $res = [];
        $this->query(sprintf('SELECT * FROM `%s` LIMIT 1', $tableName));

        /** @var PDOStatement $queryId */
        $queryId = $this->_handler->getQueryId();
        if (!$queryId) {
            $this->_handler->halt('Metadata query failed.');
            return [];
        }

        $count = $queryId->columnCount();
        for ($i = 0; $i < $count; $i++) {
            $fieldInfo = $queryId->getColumnMeta($i);
            $res[$i]['table'] = $tableName;
            $res[$i]['name'] = $fieldInfo['name'];
            $res[$i]['type'] = $fieldInfo['native_type'];
            $res[$i]['len'] = $fieldInfo['len'];
            $res[$i]['flags'] = $fieldInfo['flags'];
            if ($full) {
                $res['meta'][$res[$i]['name']] = $i;
            }
        }
        if ($full) {
            $res['num_fields'] = $count;
        }

        $this->free();

        return $res;
    }

    /**
     * @inheritdoc
     */
    public function getTableNames(): array
    {
        $return = [];

        $result = $this->query('SHOW TABLES');
        while ($record = $result->fetch(PDO::FETCH_NUM)) {
            $return[] = [
                'table_name' => $record[0],
                'tablespace_name' => $this->_dbCfg['connection']['database'],
                'database' => $this->_dbCfg['connection']['database']
            ];
        }
        return $return;
    }

    /**
     * @since CONTENIDO 4.10.2
     * @inheritdoc
     */
    public function getTableFieldDataType(string $table, string $field): ?string
    {
        $return = null;

        $sql = "
            SELECT
                DATA_TYPE
            FROM
                INFORMATION_SCHEMA.COLUMNS
            WHERE
                TABLE_SCHEMA = :database
                AND TABLE_NAME = :table
                AND COLUMN_NAME = :field
        ";

        $stmt = $this->_pdo->prepare($sql);
        $stmt->execute([
            ':database' => $this->_dbCfg['connection']['database'],
            ':table' => $table,
            ':field' => $field
        ]);

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $return = $row['DATA_TYPE'];
        }

        $stmt->closeCursor();

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function getServerInfo(): ?array
    {
        return [
            'description' => $this->_pdo->getAttribute(PDO::ATTR_SERVER_INFO),
            'version' => $this->_pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getErrorNumber(): int
    {
        return $this->_pdo->errorCode();
    }

    /**
     * @inheritdoc
     */
    public function getErrorMessage(): string
    {
        return implode("\n", $this->_pdo->errorInfo());
    }

    /**
     * @inheritdoc
     */
    public function disconnect()
    {
        $this->_pdo = null;
    }
}
