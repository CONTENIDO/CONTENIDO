<?php

/**
 * This file contains tests for the class cGenericDbDriverMysql.
 *
 * @package    Testing
 * @subpackage GenericDb
 * @author     Murat Purç <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

/**
 * Class to test cGenericDbDriverMysql
 * @package    Testing
 * @subpackage GenericDb
 */
class cGenericDbDriverMysqlTest extends cTestingTestCase
{

    /**
     * @var cGenericDbDriverMysql
     */
    protected $driver;

    public function setUp(): void
    {
        // Driver needs a Item class to use its filter/escape functions.
        $itemClass = new cApiArticleLanguage();
        $this->driver = new cGenericDbDriverMysql();
        $this->driver->setItemClassInstance($itemClass);
    }

    /**
     * Test {@see cGenericDbDriverMysql::buildJoinQuery()}.
     */
    public function testBuildJoinQuery()
    {
        $destinationTable = 'table_a';
        $destinationClass = 'cApiA';
        $destinationPrimaryKey = 'ida';
        $sourceClass = 'table_b';
        $primaryKey = 'ida';
        $result = $this->driver->buildJoinQuery(
            $destinationTable, $destinationClass, $destinationPrimaryKey,
            $sourceClass, $primaryKey
        );
        $expected = [
            'field' => 'cApiA.ida',
            'table' => '',
            'join' => 'LEFT JOIN table_a AS cApiA ON table_b.ida = cApiA.ida',
            'where' => '',
        ];
        $this->assertEquals($expected, $result);
    }

    public function dataBuildOperator()
    {
        // @TODO Add more data to test
        return [
            'field = int' => ['field', '=', 1, "field = 1"],
            'field = float' => ['field', '=', 1.23, "field = 1.23"],
            'field = string' => ['field', '=', 'value', "field = 'value'"],
            'field = string escaped' => ['field', '=', "Rock 'n' Roll", "field = 'Rock \\'n\\' Roll'"],
            'field = NULL' => ['field', '=', NULL, "field = ''"],
            'field > int' => ['field', '>', 1, "field > 1"],
            'field > float' => ['field', '>', 1.23, "field > 1.23"],
            'field > string' => ['field', '>', 'value', "field > 'value'"],
            'field > NULL' => ['field', '>', NULL, "field > ''"],
            'field IS NULL' => ['field', 'IS', NULL, "field IS NULL"],
            'field IS NOT NULL' => ['field', 'ISNOT', NULL, "field IS NOT NULL"],
            'field IN (int)' => ['field', 'IN', 1, "field IN (1)"],
            'field IN (float)' => ['field', 'IN', 1.23, "field IN (1.23)"],
            'field IN (string)' => ['field', 'IN', 'value', "field IN ('value')"],
            'field IN (NULL)' => ['field', 'IN', NULL, "field IN (NULL)"],
            'field IN (int, float, string, NULL)' => ['field', 'IN', [1, 1.23, 'value', NULL], "field IN (1, 1.23, 'value', NULL)"],
            'field LIKE int' => ['field', 'LIKE', 1, "field LIKE '%1%'"],
            'field LIKE float' => ['field', 'LIKE', 1.23, "field LIKE '%1.23%'"],
            'field LIKE string' => ['field', 'LIKE', 'value', "field LIKE '%value%'"],
            'field LIKE string escaped' => ['field', 'LIKE', "Rock 'n' Roll", "field LIKE '%Rock \\'n\\' Roll%'"],
            'field LIKE NULL' => ['field', 'LIKE', NULL, "field LIKE '%%'"],
            'field MATCHBOOL int' => ['field', 'matchbool', 1, "MATCH (field) AGAINST ('1' IN BOOLEAN MODE)"],
            'field MATCHBOOL float' => ['field', 'matchbool', 1.23, "MATCH (field) AGAINST ('1.23' IN BOOLEAN MODE)"],
            'field MATCHBOOL string' => ['field', 'matchbool', 'value', "MATCH (field) AGAINST ('value' IN BOOLEAN MODE)"],
            'field MATCHBOOL string escaped' => ['field', 'matchbool', "Rock 'n' Roll", "MATCH (field) AGAINST ('Rock \\'n\\' Roll' IN BOOLEAN MODE)"],
            'field MATCHBOOL NULL' => ['field', 'matchbool', NULL, "MATCH (field) AGAINST ('' IN BOOLEAN MODE)"],
        ];
    }

    /**
     * Test {@see cGenericDbDriverMysql::buildOperator()}.
     *
     * @dataProvider dataBuildOperator()
     *
     * @param string $field
     * @param string $operator
     * @param mixed $restriction
     * @param string $expected
     */
    public function testBuildOperator($field, $operator, $restriction, $expected)
    {
        $result = $this->driver->buildOperator($field, $operator, $restriction);
        $this->assertEquals($expected, $result);
    }

    public function dataBuildExceptionOperator()
    {
        // @TODO Add more data to test
        return [
            'field IS int' => ['field', 'IS', 1],
            'field IS float' => ['field', 'IS', 1.23],
            'field IS string' => ['field', 'IS', 'value'],
            'field IS MOT int' => ['field', 'ISNOT', 1],
            'field IS MOT float' => ['field', 'ISNOT', 1.23],
            'field IS MOT string' => ['field', 'ISNOT', 'value'],
        ];
    }

    /**
     * Test {@see cGenericDbDriverMysql::buildOperator()}.
     *
     * @dataProvider dataBuildExceptionOperator()
     *
     * @param string $field
     * @param string $operator
     * @param mixed $restriction
     */
    public function testBuildOperatorException($field, $operator, $restriction)
    {
        $this->expectException(cInvalidArgumentException::class);
        $result = $this->driver->buildOperator($field, $operator, $restriction);
    }

}