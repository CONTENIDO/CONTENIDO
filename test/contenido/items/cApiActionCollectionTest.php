<?php

/**
 * This file contains tests for the class cApiActionlogCollection.
 *
 * @package    Testing
 * @subpackage Items
 * @author     marcus.gnass
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

/**
 * This class tests the methods of the class cApiActionlogCollection.
 *
 * Some methods have data providers to keep test cases concise.
 * They return a list of data sets for several testcases.
 * e.g. [
 *     'name of data set' => [<data to be used as input>, <data to be expected as output>],
 *     ...
 * ]
 *
 * @link   https://phpunit.readthedocs.io/en/8.4/annotations.html#dataprovider
 *
 * @author marcus.gnass
 */
class cApiActionCollectionTest extends cTestingTestCase
{
    public function dataCreate()
    {
        // * @param string|int $idarea
        // * @param string|int $name
        // * @param string|int $alt_name [optional]
        // * @param string     $code     [optional]
        // * @param string     $location [optional]
        // * @param int        $relevant [optional]
        // $idarea, $name, $alt_name = '', $code = '', $location = '', $relevant = 1
        return [
            'zeros_default'    => [
                [0, 0, 0, 0, 0, ''],
                [
                    'idarea'      => 0,
                    'name'     => 0,
                    'alt_name'       => 0,
                    'code'     => 0,
                    'location'     => 0,
                    'relevant' => (new DateTime())->format('Y-m-d H:i:s'),
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataCreate()
     *
     * @param array|null $input  data to be used as input
     * @param array|null $output data to be expected as output
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function testCreate(array $input = null, array $output = null)
    {
        list($userId, $idclient, $idlang, $idaction, $idcatart, $logtimestamp) = $input;
        $coll = new cApiActionCollection();
        $act = $coll->create($userId, $idclient, $idlang, $idaction, $idcatart, $logtimestamp);
        $this->assertNotNull($act);
        $this->assertNotEquals(0, $act->getField('idaction'));
        foreach ($output as $key => $value) {
            $this->assertEquals($value, $act->getField($key));
        }
    }
}
