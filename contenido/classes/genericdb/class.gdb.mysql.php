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
class cGenericDbDriverMysql extends cGenericDbDriver {

    /**
     * @see cGenericDbDriver::buildJoinQuery()
     * @param string $destinationTable
     * @param string $destinationClass
     * @param string $destinationPrimaryKey
     * @param string $sourceClass
     * @param string $primaryKey
     * @return array
     */
    public function buildJoinQuery($destinationTable, $destinationClass, $destinationPrimaryKey, $sourceClass, $primaryKey) {
        // Build a regular LEFT JOIN
        $field = "$destinationClass.$destinationPrimaryKey";
        $tables = "";
        $join = "LEFT JOIN $destinationTable AS $destinationClass ON " . cSecurity::toString($sourceClass . "." . $primaryKey) . " = " . cSecurity::toString($destinationClass . "." . $primaryKey);
        $where = "";

        return [
            "field" => $field,
            "table" => $tables,
            "join"  => $join,
            "where" => $where,
        ];
    }

    /**
     * @see cGenericDbDriver::buildOperator()
     * @param string $sField
     * @param string $sOperator
     * @param string $sRestriction
     * @return string
     */
    public function buildOperator($sField, $sOperator, $sRestriction) {
        $sOperator = cString::toLowerCase($sOperator);
        $sField = cSecurity::toString($sField);

        $sWhereStatement = "";

        switch ($sOperator) {
            case "matchbool":
                $sqlStatement = "MATCH (%s) AGAINST ('%s' IN BOOLEAN MODE)";
                $sWhereStatement = sprintf($sqlStatement, $sField, $this->_prepareValue($sRestriction));
                break;
            case "match":
                $sqlStatement = "MATCH (%s) AGAINST ('%s')";
                $sWhereStatement = sprintf($sqlStatement, $sField, $this->_prepareValue($sRestriction));
                break;
            case "like":
                $sqlStatement = "%s LIKE '%%%s%%'";
                $sWhereStatement = sprintf($sqlStatement, $sField, $this->_prepareValue($sRestriction));
                break;
            case "likeleft":
                $sqlStatement = "%s LIKE '%s%%'";
                $sWhereStatement = sprintf($sqlStatement, $sField, $this->_prepareValue($sRestriction));
                break;
            case "likeright":
                $sqlStatement = "%s LIKE '%%%s'";
                $sWhereStatement = sprintf($sqlStatement, $sField, $this->_prepareValue($sRestriction));
                break;
            case "notlike":
                $sqlStatement = "%s NOT LIKE '%%%s%%'";
                $sWhereStatement = sprintf($sqlStatement, $sField, $this->_prepareValue($sRestriction));
                break;
            case "notlikeleft":
                $sqlStatement = "%s NOT LIKE '%s%%'";
                $sWhereStatement = sprintf($sqlStatement, $sField, $this->_prepareValue($sRestriction));
                break;
            case "notlikeright":
                $sqlStatement = "%s NOT LIKE '%%%s'";
                $sWhereStatement = sprintf($sqlStatement, $sField, $this->_prepareValue($sRestriction));
                break;
            case "fulltext":

                break;
            case "in":
                if (is_array($sRestriction)) {
                    $items = [];
                    foreach ($sRestriction as $key => $sRestrictionItem) {
                        $items[] = $this->_prepareInConditionValue($sRestrictionItem);
                    }
                    $sRestriction = implode(", ", $items);
                } else {
                    $sRestriction = $this->_prepareInConditionValue($sRestriction);
                }

                $sWhereStatement = implode(" ", [$sField, "IN (" . $sRestriction . ")"]);
                break;
            case "is":
                if (is_null($sRestriction)) {
                    $sqlStatement = '%s IS NULL';
                    $sWhereStatement = sprintf($sqlStatement, $sField);
                } else {
                    throw new cInvalidArgumentException(
                        'Only restriction `NULL` is allowed for the `IS` operator.'
                    );
                }
                break;
            case "isnot":
                if (is_null($sRestriction)) {
                    $sqlStatement = '%s IS NOT NULL';
                    $sWhereStatement = sprintf($sqlStatement, $sField);
                } else {
                    throw new cInvalidArgumentException(
                        'Only restriction `NULL` is allowed for the `IS NOT` operator.'
                    );
                }
                break;
            default:
                if (!is_int($sRestriction) && !is_float($sRestriction)) {
                    $sRestriction = "'" . $this->_prepareValue($sRestriction) . "'";
                }

                $sWhereStatement = implode(" ", [$sField, $sOperator, $sRestriction]);
        }

        return $sWhereStatement;
    }

    /**
     * Prepares a value for the usage in a 'IN' condition. Integer and float
     * will be returned as it is, NULL will be returned as 'NULL',  everything
     * else will be converted to a string.
     *
     * @param string|int|float|null|mixed $value
     *
     * @return int|float|string
     */
    private function _prepareInConditionValue($value) {
        if (is_null($value)) {
            return 'NULL';
        } elseif (is_int($value) || is_float($value)) {
            return $value;
        } else {
            return "'" . $this->_prepareString($value) . "'";
        }
    }

    /**
     * Prepares a value, a integer and float will be returned as it is,
     * everything else will be converted to a string (e.g. NULL to '').
     *
     * @param string|int|float|null|mixed $value
     *
     * @return int|float|string
     */
    private function _prepareValue($value) {
        // It should return 'NULL' for a NULL value but we should stay downwards
        // compatible for now.
        if (is_int($value) || is_float($value)) {
            return $value;
        } else {
            return $this->_prepareString($value);
        }
    }

    /**
     * Prepares a string value, filters and escapes it.
     *
     * @param string $value
     * @return string
     */
    private function _prepareString($value) {
        $value = $this->_oItemClassInstance->inFilter($value);
        return $this->_oItemClassInstance->escape($value);
    }

}
