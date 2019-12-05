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
    /** @var cApiAction */
    private $_item;

    /**
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function tearDown(): void
    {
        // delete aggregated item after every test
        if ($this->_item && $this->_item->isLoaded()) {
            $pkey = $this->_item->getPrimaryKeyName();
            $coll = new cApiActionCollection();
            $coll->delete($this->_item->getField($pkey));
        }
    }

    /**
     * Parameters:
     * string|int $idarea
     * string|int $name
     * string|int $alt_name [optional]
     * string     $code     [optional]
     * string     $location [optional]
     * int        $relevant [optional]
     *
     * Signature:
     * create($idarea, $name, $alt_name = '', $code = '', $location = '', $relevant = 1)
     *
     * @return array
     */
    public function dataCreate()
    {
        return [
            'default' => [
                [0, '', '', '', '', 1],
                [
                    'idarea'   => 0,
                    'name'     => '',
                    'alt_name' => '',
                    'code'     => '',
                    'location' => '',
                    'relevant' => 1,
                ],
            ],
            'empty'   => [
                [0, '', '', '', '', 0],
                [
                    'idarea'   => 0,
                    'name'     => '',
                    'alt_name' => '',
                    'code'     => '',
                    'location' => '',
                    'relevant' => 0,
                ],
            ],
            'valid'   => [
                [0, 'name', 'alternative name', 'this is my code', 'my location', 1],
                [
                    'idarea'   => 0,
                    'name'     => 'name',
                    'alt_name' => 'alternative name',
                    'code'     => 'this is my code',
                    'location' => 'my location',
                    'relevant' => 1,
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
        // create item from input
        $coll = new cApiActionCollection();
        list($idarea, $name, $alt_name, $code, $location, $relevant) = $input;
        $this->_item = $coll->create($idarea, $name, $alt_name, $code, $location, $relevant);

        // assertions
        $this->assertNotNull($this->_item);
        $this->assertEquals(true, $this->_item->isLoaded());
        $this->assertNotEquals(0, $this->_item->getField($this->_item->getPrimaryKeyName()));
        foreach ($output as $key => $value) {
            $this->assertEquals($value, $this->_item->getField($key));
        }
    }
}
