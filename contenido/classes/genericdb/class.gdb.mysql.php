<?php

/**
 * This file contains the MySQL database driver for the generic db.
 *
 * @package    Core
 * @subpackage GenericDB
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * MySQL database driver
 *
 * @package    Core
 * @subpackage GenericDB
 */
class cGenericDbDriverMysql extends cGenericDbDriver
{

    /**
     * @inheritDoc
     */
    public function buildJoinQuery(
        string $destinationTable,
        string $destinationClass,
        string $destinationPrimaryKey,
        string $sourceClass,
        string $primaryKey
    ): array
    {
        // Build a regular LEFT JOIN
        $field = "$destinationClass.$destinationPrimaryKey";
        $tables = "";
        $join = "LEFT JOIN $destinationTable AS $destinationClass ON " . cSecurity::toString($sourceClass . "." . $primaryKey) . " = " . cSecurity::toString($destinationClass . "." . $primaryKey);
        $where = "";

        return [
            "field" => $field,
            "table" => $tables,
            "join" => $join,
            "where" => $where,
        ];
    }

    /**
     * @inheritDoc
     * @throws cInvalidArgumentException
     */
    public function buildOperator(
        string $field,
        string $operator,
        $restriction
    ): string {
        $operator = cString::toLowerCase($operator);
        $field = cSecurity::toString($field);

        $whereStatement = "";

        switch ($operator) {
            case "matchbool":
                $sqlStatement = "MATCH (%s) AGAINST ('%s' IN BOOLEAN MODE)";
                $whereStatement = sprintf($sqlStatement, $field, $this->_prepareValue($restriction));
                break;
            case "match":
                $sqlStatement = "MATCH (%s) AGAINST ('%s')";
                $whereStatement = sprintf($sqlStatement, $field, $this->_prepareValue($restriction));
                break;
            case "like":
                $sqlStatement = "%s LIKE '%%%s%%'";
                $whereStatement = sprintf($sqlStatement, $field, $this->_prepareValue($restriction));
                break;
            case "likeleft":
                $sqlStatement = "%s LIKE '%s%%'";
                $whereStatement = sprintf($sqlStatement, $field, $this->_prepareValue($restriction));
                break;
            case "likeright":
                $sqlStatement = "%s LIKE '%%%s'";
                $whereStatement = sprintf($sqlStatement, $field, $this->_prepareValue($restriction));
                break;
            case "notlike":
                $sqlStatement = "%s NOT LIKE '%%%s%%'";
                $whereStatement = sprintf($sqlStatement, $field, $this->_prepareValue($restriction));
                break;
            case "notlikeleft":
                $sqlStatement = "%s NOT LIKE '%s%%'";
                $whereStatement = sprintf($sqlStatement, $field, $this->_prepareValue($restriction));
                break;
            case "notlikeright":
                $sqlStatement = "%s NOT LIKE '%%%s'";
                $whereStatement = sprintf($sqlStatement, $field, $this->_prepareValue($restriction));
                break;
            case "fulltext":
                break;
            case "in":
                if (is_array($restriction)) {
                    $items = [];
                    foreach ($restriction as $sRestrictionItem) {
                        $items[] = $this->_prepareInConditionValue($sRestrictionItem);
                    }
                    $restriction = implode(", ", $items);
                } else {
                    $restriction = $this->_prepareInConditionValue($restriction);
                }

                $whereStatement = implode(" ", [$field, "IN (" . $restriction . ")"]);
                break;
            case "is":
                if (is_null($restriction)) {
                    $sqlStatement = '%s IS NULL';
                    $whereStatement = sprintf($sqlStatement, $field);
                } else {
                    throw new cInvalidArgumentException(
                        'Only restriction `NULL` is allowed for the `IS` operator.'
                    );
                }
                break;
            case "isnot":
                if (is_null($restriction)) {
                    $sqlStatement = '%s IS NOT NULL';
                    $whereStatement = sprintf($sqlStatement, $field);
                } else {
                    throw new cInvalidArgumentException(
                        'Only restriction `NULL` is allowed for the `IS NOT` operator.'
                    );
                }
                break;
            default:
                if (!is_int($restriction) && !is_float($restriction)) {
                    $restriction = "'" . $this->_prepareValue($restriction) . "'";
                }

                $whereStatement = implode(" ", [$field, $operator, $restriction]);
        }

        return $whereStatement;
    }

    /**
     * Prepares a value for the usage in an 'IN' condition. Integer and float
     * will be returned as it is, NULL will be returned as 'NULL',  everything
     * else will be converted to a string.
     *
     * @param string|int|float|null|mixed $value
     *
     * @return int|float|string
     */
    private function _prepareInConditionValue(mixed $value): float|int|string
    {
        if (is_null($value)) {
            return 'NULL';
        } elseif (is_int($value) || is_float($value)) {
            return $value;
        } else {
            return "'" . $this->_prepareString((string) $value) . "'";
        }
    }

    /**
     * Prepares a value, an integer and float will be returned as it is,
     * everything else will be converted to a string (e.g. NULL to '').
     *
     * @param string|int|float|null|mixed $value
     *
     * @return int|float|string
     */
    private function _prepareValue(mixed $value): float|int|string
    {
        if (is_null($value)) {
            return 'NULL';
        } else if (is_int($value) || is_float($value)) {
            return $value;
        } else {
            return $this->_prepareString((string) $value);
        }
    }

    /**
     * Prepares a string value, filters and escapes it.
     */
    private function _prepareString(string $value): string
    {
        $value = $this->_oItemClassInstance->inFilter($value);
        return $this->_oItemClassInstance->escape($value);
    }

}
