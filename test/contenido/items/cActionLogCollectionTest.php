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
class cActionLogCollectionTest extends cTestingTestCase
{
    public function dataCreate()
    {
        return [
            'zeros'    => [[0, 0, 0, 0, 0, '1971-06-01 12:34:56'], [0, 0, 0, 0, 0, '1971-06-01 12:34:56']],
            'nonzeros' => [[1, 2, 3, 4, 5, '1971-06-01 12:34:56'], [1, 2, 3, 4, 5, '1971-06-01 12:34:56']],
        ];
    }

    /**
     * @dataProvider dataCreate()
     *
     * @param array|null $input  data to be used as input
     * @param array|null $output data to be expected as output
     */
    public function testCreate(array $input = null, array $output = null)
    {
        $coll = new cApiActionlogCollection();

        // w/o optional logtimestamp
        try {
            list($userId, $idclient, $idlang, $idaction, $idcatart) = $input;
            $act = $coll->create($userId, $idclient, $idlang, $idaction, $idcatart);

            list($userId, $idclient, $idlang, $idaction, $idcatart) = $output;
            $this->assertNotNull($act);
            $this->assertNotEquals(0, $act->getField('idlog'));
            $this->assertEquals($userId, $act->getField('user_id'));
            $this->assertEquals($idclient, $act->getField('idclient'));
            $this->assertEquals($idlang, $act->getField('idlang'));
            $this->assertEquals($idaction, $act->getField('idaction'));
            $this->assertEquals($idcatart, $act->getField('idcatart'));
            $this->assertEquals((new DateTime())->format('Y-m-d H:i:s'), $act->getField('logtimestamp'));
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }

        // w/ optional logtimestamp
        try {
            list($userId, $idclient, $idlang, $idaction, $idcatart, $logtimestamp) = $input;
            $act = $coll->create($userId, $idclient, $idlang, $idaction, $idcatart, $logtimestamp);

            list($userId, $idclient, $idlang, $idaction, $idcatart, $logtimestamp) = $output;
            $this->assertNotNull($act);
            $this->assertNotEquals(0, $act->getField('idlog'));
            $this->assertEquals($userId, $act->getField('user_id'));
            $this->assertEquals($idclient, $act->getField('idclient'));
            $this->assertEquals($idlang, $act->getField('idlang'));
            $this->assertEquals($idaction, $act->getField('idaction'));
            $this->assertEquals($idcatart, $act->getField('idcatart'));
            $this->assertEquals($logtimestamp, $act->getField('logtimestamp'));
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }
    }
}
