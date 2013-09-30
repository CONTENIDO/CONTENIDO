<?php
/**
 * This file contains the MySQL database driver for the generic db.
 *
 * @package Core
 * @subpackage GenericDB
 * @version SVN Revision $Rev:$
 *
 * @author Bjoern Behrens
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * MySQL database driver
 *
 * @package Core
 * @subpackage GenericDB
 */
class cGenericDbDriverMysql extends cGenericDbDriver {

    /**
     * (non-PHPdoc)
     *
     * @see cGenericDbDriver::buildJoinQuery()
     */
    public function buildJoinQuery($destinationTable, $destinationClass, $destinationPrimaryKey, $sourceClass, $primaryKey) {
        // Build a regular LEFT JOIN
        $field = "$destinationClass.$destinationPrimaryKey";
        $tables = "";
        $join = "LEFT JOIN $destinationTable AS $destinationClass ON " . cSecurity::toString($sourceClass . "." . $primaryKey) . " = " . cSecurity::toString($destinationClass . "." . $primaryKey);
        $where = "";

        return array(
            "field" => $field,
            "table" => $tables,
            "join" => $join,
            "where" => $where
        );
    }

    /**
     * (non-PHPdoc)
     *
     * @see cGenericDbDriver::buildOperator()
     */
    public function buildOperator($sField, $sOperator, $sRestriction) {
        $sOperator = strtolower($sOperator);

        $sWhereStatement = "";

        switch ($sOperator) {
            case "matchbool":
                $sqlStatement = "MATCH (%s) AGAINST ('%s' IN BOOLEAN MODE)";
                $sWhereStatement = sprintf($sqlStatement, $sField, $this->_oItemClassInstance->_inFilter($sRestriction));
                break;
            case "match":
                $sqlStatement = "MATCH (%s) AGAINST ('%s')";
                $sWhereStatement = sprintf($sqlStatement, $sField, $this->_oItemClassInstance->_inFilter($sRestriction));
                break;
            case "like":
                $sqlStatement = "%s LIKE '%%%s%%'";
                $sWhereStatement = sprintf($sqlStatement, cSecurity::toString($sField), $this->_oItemClassInstance->_inFilter($sRestriction));
                break;
            case "likeleft":
                $sqlStatement = "%s LIKE '%s%%'";
                $sWhereStatement = sprintf($sqlStatement, cSecurity::toString($sField), $this->_oItemClassInstance->_inFilter($sRestriction));
                break;
            case "likeright":
                $sqlStatement = "%s LIKE '%%%s'";
                $sWhereStatement = sprintf($sqlStatement, cSecurity::toString($sField), $this->_oItemClassInstance->_inFilter($sRestriction));
                break;
            case "notlike":
                $sqlStatement = "%s NOT LIKE '%%%s%%'";
                $sWhereStatement = sprintf($sqlStatement, cSecurity::toString($sField), $this->_oItemClassInstance->_inFilter($sRestriction));
                break;
            case "notlikeleft":
                $sqlStatement = "%s NOT LIKE '%s%%'";
                $sWhereStatement = sprintf($sqlStatement, cSecurity::toString($sField), $this->_oItemClassInstance->_inFilter($sRestriction));
                break;
            case "notlikeright":
                $sqlStatement = "%s NOT LIKE '%%%s'";
                $sWhereStatement = sprintf($sqlStatement, cSecurity::toString($sField), $this->_oItemClassInstance->_inFilter($sRestriction));
                break;
            case "fulltext":

                break;
            case "in":
                if (is_array($sRestriction)) {
                    $items = array();

                    foreach ($sRestriction as $key => $sRestrictionItem) {
                        $items[] = "'" . $this->_oItemClassInstance->_inFilter($sRestrictionItem) . "'";
                    }

                    $sRestriction = implode(", ", $items);
                } else {
                    $sRestriction = "'" . $sRestriction . "'";
                }

                $sWhereStatement = implode(" ", array(
                    $sField,
                    "IN (",
                    $sRestriction,
                    ")"
                ));
                break;
            default:
                $sRestriction = "'" . $this->_oItemClassInstance->_inFilter($sRestriction) . "'";

                $sWhereStatement = implode(" ", array(
                    $sField,
                    $sOperator,
                    $sRestriction
                ));
        }

        return $sWhereStatement;
    }
}
