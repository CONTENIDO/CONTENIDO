<?php

/**
 *
 * @author    claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license   http://www.contenido.org/license/LIZENZ.txt
 * @link      http://www.4fb.de
 * @link      http://www.contenido.org
 */

/**
 *
 * @package    Testing
 * @subpackage Test_Validator
 */
class ItemCollectionTest extends cTestingTestCase
{
    /**
     * @var TCollection
     */
    protected $_collection;

    /**
     * @var TFCollection
     */
    protected $_noItemClassCollection;

    /**
     * Tables used by this test case
     * @var array
     */
    protected $_tables = ['con_test_dog', 'con_test_rfid_dog', 'con_test'];

    /**
     * @throws cDbException
     * @throws cInvalidArgumentException
     * @throws cTestingException
     */
    protected function setUp(): void
    {
        ini_set('display_errors', true);
        error_reporting(E_ALL);

        $this->setUpTestCaseDbTables();

        $this->_collection            = new TCollection();
        $this->_noItemClassCollection = new TFCollection();

        $db = cRegistry::getDb();
        $db->query(SqlItemCollection::getCreateConTestStatement());
        $db->query(SqlItemCollection::getInsertConTestStatement());
        $db->query(SqlItemCollection::getCreateDogStatement());
        $db->query(SqlItemCollection::getInserDogStatement());
        $db->query(SqlItemCollection::getCreateDogRfidStatement());
        $db->query(SqlItemCollection::getInserDogRfidStatement());
    }

    /**
     * @throws cDbException
     * @throws cTestingException
     */
    protected function tearDown(): void
    {
        $this->tearDownTestCaseDbTables();
    }

    /**
     */
    public function testConstruct()
    {
        // test construction w/o specified table
        try {
            new ITCollection();
            $this->fail('should have thrown cInvalidArgumentException');
        } catch (cInvalidArgumentException $e) {
            $this->assertEquals(
                'ItemCollection: No table specified. Inherited classes *need* to set a table',
                $e->getMessage()
            );
        }

        // test construction w/o specified primary key
        try {
            new TITCollection();
            $this->fail('should have thrown cInvalidArgumentException');
        } catch (cInvalidArgumentException $e) {
            $this->assertEquals(
                'No primary key specified. Inherited classes *need* to set a primary key',
                $e->getMessage()
            );
        }
    }

    /**
     */
    public function testSetEncoding()
    {
        $encoding = 'UTF-8';
        $this->_collection->setEncoding($encoding);

        // test member _encoding of collection
        $act = $this->_readAttribute($this->_collection, '_encoding');
        $this->assertEquals($encoding, $act);

        // test member _sEncoding of driver of collection
        $_driver = $this->_readAttribute($this->_collection, '_driver');
        $act     = $this->_readAttribute($_driver, '_sEncoding');
        $this->assertEquals($encoding, $act);
    }

    /**
     * @throws cInvalidArgumentException
     */
    public function testLink()
    {
        $dogColl = new DogCollection();

        // test linking of known class
        $dogColl->link('DogRfidCollection');
        $_links = $this->_readAttribute($dogColl, '_links');
        $this->assertSame(true, is_array($_links));
        $this->assertSame(true, array_key_exists('DogRfidCollection', $_links));
        $this->assertSame(true, $_links['DogRfidCollection'] instanceof DogRfidCollection);

        // test linking of unknown class
        try {
            $dogColl->link('non_existing_class');
            $this->fail('should have thrown cInvalidArgumentException');
        } catch (cInvalidArgumentException $e) {
            $this->assertEquals(
                'Could not find class [non_existing_class] for use with link in class DogCollection',
                $e->getMessage()
            );
        }
    }

    /**
     */
    public function testSetLimit()
    {
        $_limitStart = 1;
        $_limitCount = 2;
        $this->_collection->setLimit($_limitStart, $_limitCount);

        // test member _limitStart of collection
        $act = $this->_readAttribute($this->_collection, '_limitStart');
        $this->assertEquals($_limitStart, $act);

        // test member _limitCount of collection
        $act = $this->_readAttribute($this->_collection, '_limitCount');
        $this->assertEquals($_limitCount, $act);
    }

    /**
     * Test setting a global where condition w/o operator.
     */
    public function testSetWhere()
    {
        $this->_collection->setWhere('foo', 'bar');
        $act                                 = $this->_readAttribute($this->_collection, '_where');
        $exp                                 = [];
        $exp['global']                       = [];
        $exp['global']['foo']                = [];
        $exp['global']['foo']['operator']    = '=';
        $exp['global']['foo']['restriction'] = 'bar';
        $exp['groups']                       = [];
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));
    }

    /**
     * Test setting a global where condition w/ operator.
     */
    public function testSetWhereOperator()
    {
        $this->_collection->setWhere('foo', 'bar', 'LIKE');
        $act                                 = $this->_readAttribute($this->_collection, '_where');
        $exp                                 = [];
        $exp['global']                       = [];
        $exp['global']['foo']                = [];
        $exp['global']['foo']['operator']    = 'LIKE';
        $exp['global']['foo']['restriction'] = 'bar';
        $exp['groups']                       = [];
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));
    }

    /**
     * This method tests deleting nonexistant where conditions from a collection
     * which has no conditions at all.
     */
    public function testDeleteWhereUnconditioned()
    {
        // test deleting a nonexistant where condition
        $this->_collection->deleteWhere('foo', 'bar');
        $act           = $this->_readAttribute($this->_collection, '_where');
        $exp           = [];
        $exp['global'] = [];
        $exp['groups'] = [];
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));

        // test deleting a nonexistant where condition w/ default operator
        $this->_collection->deleteWhere('foo', 'bar', '=');
        $act           = $this->_readAttribute($this->_collection, '_where');
        $exp           = [];
        $exp['global'] = [];
        $exp['groups'] = [];
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));

        // test deleting a nonexistant where condition w/ nondefault operator
        $this->_collection->deleteWhere('foo', 'bar', 'LIKE');
        $act           = $this->_readAttribute($this->_collection, '_where');
        $exp           = [];
        $exp['global'] = [];
        $exp['groups'] = [];
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));
    }

    /**
     * This method tests deleting nonexistant where conditions from a collection
     * which has some conditions defined.
     */
    public function testDeleteWhereConditioned()
    {
        // test deleting a where condition when there is another condition for
        // this field but w/ another restriction
        $this->_collection->setWhere('foo', 'bar');
        $this->_collection->deleteWhere('foo', 'eggs');
        $act                                 = $this->_readAttribute($this->_collection, '_where');
        $exp                                 = [];
        $exp['global']                       = [];
        $exp['global']['foo']                = [];
        $exp['global']['foo']['operator']    = '=';
        $exp['global']['foo']['restriction'] = 'bar';
        $exp['groups']                       = [];
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));

        // test deleting a where condition when there is another condition for
        // this restriction but w/ another field
        $this->_collection->setWhere('foo', 'bar');
        $this->_collection->deleteWhere('spam', 'bar');
        $act                                 = $this->_readAttribute($this->_collection, '_where');
        $exp                                 = [];
        $exp['global']                       = [];
        $exp['global']['foo']                = [];
        $exp['global']['foo']['operator']    = '=';
        $exp['global']['foo']['restriction'] = 'bar';
        $exp['groups']                       = [];
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));

        // test deleting a where condition when there is another condition for
        // this restriction but w/ another field
        $this->_collection->setWhere('foo', 'bar');
        $this->_collection->deleteWhere('foo', 'bar', 'LIKE');
        $act                                 = $this->_readAttribute($this->_collection, '_where');
        $exp                                 = [];
        $exp['global']                       = [];
        $exp['global']['foo']                = [];
        $exp['global']['foo']['operator']    = '=';
        $exp['global']['foo']['restriction'] = 'bar';
        $exp['groups']                       = [];
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));

        // test deleting a where condition when there is a condition for this
        // field w/ this restriction
        $this->_collection->setWhere('foo', 'bar');
        $this->_collection->deleteWhere('foo', 'bar');
        $act           = $this->_readAttribute($this->_collection, '_where');
        $exp           = [];
        $exp['global'] = [];
        $exp['groups'] = [];
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));
    }

    /**
     */
    public function testSetWhereGroup()
    {
        $this->_collection->setWhereGroup('myGroup', 'foo', 'bar');
        $act                                            = $this->_readAttribute($this->_collection, '_where');
        $exp                                            = [];
        $exp['global']                                  = [];
        $exp['groups']                                  = [];
        $exp['groups']['myGroup']                       = [];
        $exp['groups']['myGroup']['foo']                = [];
        $exp['groups']['myGroup']['foo']['operator']    = '=';
        $exp['groups']['myGroup']['foo']['restriction'] = 'bar';
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));
    }

    /**
     */
    public function testSetWhereGroupOperator()
    {
        $this->_collection->setWhereGroup('myGroup', 'foo', 'bar', 'LIKE');
        $act                                            = $this->_readAttribute($this->_collection, '_where');
        $exp                                            = [];
        $exp['global']                                  = [];
        $exp['groups']                                  = [];
        $exp['groups']['myGroup']                       = [];
        $exp['groups']['myGroup']['foo']                = [];
        $exp['groups']['myGroup']['foo']['operator']    = 'LIKE';
        $exp['groups']['myGroup']['foo']['restriction'] = 'bar';
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));
    }

    /**
     * @todo should behave the same way as deleteWhere().
     * @see  testDeleteWhereUnconditioned()
     */
    public function testDeleteWhereGroupUnconditioned()
    {
        try {
            $this->_collection->deleteWhereGroup('myGroup', 'foo', 'bar');
            $this->fail('should have thrown PHPUnit\Framework\Error\Notice');
        } catch (PHPUnit\Framework\Error\Notice $e) {
            $this->assertEquals('Undefined index: myGroup', $e->getMessage());
        }
    }

    /**
     */
    public function testDeleteWhereGroupConditioned()
    {
        // test deleting a where condition when there is another condition for
        // this field but w/ another restriction
        $this->_collection->setWhereGroup('myGroup', 'foo', 'bar');
        $this->_collection->deleteWhereGroup('myGroup', 'foo', 'eggs');
        $act                                            = $this->_readAttribute($this->_collection, '_where');
        $exp                                            = [];
        $exp['global']                                  = [];
        $exp['groups']                                  = [];
        $exp['groups']['myGroup']                       = [];
        $exp['groups']['myGroup']['foo']                = [];
        $exp['groups']['myGroup']['foo']['operator']    = '=';
        $exp['groups']['myGroup']['foo']['restriction'] = 'bar';
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));

        // test deleting a where condition when there is another condition for
        // this restriction but w/ another field
        $this->_collection->setWhereGroup('myGroup', 'foo', 'bar');
        $this->_collection->deleteWhereGroup('myGroup', 'spam', 'bar');
        $act                                            = $this->_readAttribute($this->_collection, '_where');
        $exp                                            = [];
        $exp['global']                                  = [];
        $exp['groups']                                  = [];
        $exp['groups']['myGroup']                       = [];
        $exp['groups']['myGroup']['foo']                = [];
        $exp['groups']['myGroup']['foo']['operator']    = '=';
        $exp['groups']['myGroup']['foo']['restriction'] = 'bar';
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));

        // test deleting a where condition when there is another condition for
        // this restriction but w/ another field
        $this->_collection->setWhereGroup('myGroup', 'foo', 'bar');
        $this->_collection->deleteWhereGroup('myGroup', 'foo', 'bar', 'LIKE');
        $act                                            = $this->_readAttribute($this->_collection, '_where');
        $exp                                            = [];
        $exp['global']                                  = [];
        $exp['groups']                                  = [];
        $exp['groups']['myGroup']                       = [];
        $exp['groups']['myGroup']['foo']                = [];
        $exp['groups']['myGroup']['foo']['operator']    = '=';
        $exp['groups']['myGroup']['foo']['restriction'] = 'bar';
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));

        // test deleting a where condition when there is a condition for this
        // field w/ this restriction
        $this->_collection->setWhereGroup('myGroup', 'foo', 'bar');
        $this->_collection->deleteWhereGroup('myGroup', 'foo', 'bar');
        $act                      = $this->_readAttribute($this->_collection, '_where');
        $exp                      = [];
        $exp['global']            = [];
        $exp['groups']            = [];
        $exp['groups']['myGroup'] = [];
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));
    }

    /**
     */
    public function testSetInnerGroupCondition()
    {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testSetGroupCondition()
    {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testResetQuery()
    {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testQuery()
    {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     * @throws cException
     */
    public function testSetOrder()
    {
        $asc = [
            'id'    => '2',
            'name'  => 'Jake',
            'descr' => 'It loves human companionship and being part of the group.',
            'size'  => 'medium',
            'date'  => '2013-09-26 12:14:28',
        ];
        $des = [
            'id'    => '1',
            'name'  => 'Max',
            'descr' => 'Its distinctive appearance and deep foghorn voice make it stand out in a crowd.',
            'size'  => 'medium',
            'date'  => '2013-09-26 12:14:28',
        ];

        $dogColl = new DogCollection();
        $dogColl->setWhereGroup('date', 'size', 'medium', '=');
        $dogColl->setOrder('id DESC');
        $dogColl->query();
        $ref = $dogColl->next();

        $this->assertEquals($ref->toArray(), $asc);

        $dogColl = new DogCollection();
        $dogColl->setWhereGroup('date', 'size', 'medium', '=');
        $dogColl->setOrder('id ASC');
        $dogColl->query();
        $ref = $dogColl->next();

        $this->assertEquals($ref->toArray(), $des);
    }

    /**
     * Test {@see ItemCollection::addResultField()}
     */
    public function testAddResultField()
    {
        // Initial status
        $dogColl = new DogCollection();
        $resultFields = $this->_readAttribute($dogColl, '_resultFields');
        $this->assertEmpty($resultFields, 'Result fields are not empty');

        // Add single result field
        $dogColl = new DogCollection();
        $dogColl->addResultField('name');
        $resultFields = $this->_readAttribute($dogColl, '_resultFields');
        $this->assertEquals($resultFields, ['name']);

        // Add single result field in upper-case
        $dogColl = new DogCollection();
        $dogColl->addResultField('NAME');
        $resultFields = $this->_readAttribute($dogColl, '_resultFields');
        $this->assertEquals($resultFields, ['name']);

        // Add several result fields
        $dogColl = new DogCollection();
        $dogColl->addResultField('name');
        $dogColl->addResultField('descr');
        $resultFields = $this->_readAttribute($dogColl, '_resultFields');
        $this->assertEquals($resultFields, ['name', 'descr']);
    }

    /**
     * Test {@see ItemCollection::removeResultField()}
     */
    public function testRemoveResultField()
    {
        // Remove a result field
        $dogColl = new DogCollection();
        $dogColl->addResultField('name');
        $dogColl->removeResultField('name');
        $resultFields = $this->_readAttribute($dogColl, '_resultFields');
        $this->assertEquals($resultFields, []);

        // Remove an invalid result field
        $dogColl = new DogCollection();
        $dogColl->addResultField('name');
        $dogColl->removeResultField('invalid name');
        $resultFields = $this->_readAttribute($dogColl, '_resultFields');
        $this->assertEquals($resultFields, ['name']);

        // Remove multiple result fields
        $dogColl = new DogCollection();
        $dogColl->addResultField('name');
        $dogColl->addResultField('descr');
        $dogColl->removeResultField('name');
        $dogColl->removeResultField('descr');
        $resultFields = $this->_readAttribute($dogColl, '_resultFields');
        $this->assertEquals($resultFields, []);
    }

    /**
     * Test {@see ItemCollection::addResultFields()}
     */
    public function testAddResultFields()
    {
        // Add single result field
        $dogColl = new DogCollection();
        $dogColl->addResultFields(['name']);
        $resultFields = $this->_readAttribute($dogColl, '_resultFields');
        $this->assertEquals($resultFields, ['name']);

        // Add single result field in upper-case
        $dogColl = new DogCollection();
        $dogColl->addResultFields(['NAME']);
        $resultFields = $this->_readAttribute($dogColl, '_resultFields');
        $this->assertEquals($resultFields, ['name']);

        // Add several result fields
        $dogColl = new DogCollection();
        $dogColl->addResultFields(['name', 'descr']);
        $resultFields = $this->_readAttribute($dogColl, '_resultFields');
        $this->assertEquals($resultFields, ['name', 'descr']);
    }

    /**
     * Test {@see ItemCollection::removeResultFields()}
     */
    public function testRemoveResultFields()
    {
        // Remove a result field
        $dogColl = new DogCollection();
        $dogColl->addResultField('name');
        $dogColl->removeResultFields(['name']);
        $resultFields = $this->_readAttribute($dogColl, '_resultFields');
        $this->assertEquals($resultFields, []);

        // Remove an invalid result field
        $dogColl = new DogCollection();
        $dogColl->addResultField('name');
        $dogColl->removeResultFields(['invalid name']);
        $resultFields = $this->_readAttribute($dogColl, '_resultFields');
        $this->assertEquals($resultFields, ['name']);

        // Remove multiple result fields
        $dogColl = new DogCollection();
        $dogColl->addResultFields(['name', 'descr']);
        $dogColl->removeResultFields(['name', 'descr']);
        $resultFields = $this->_readAttribute($dogColl, '_resultFields');
        $this->assertEquals($resultFields, []);
    }

    /**
     * @throws cDbException
     * @throws cException
     */
    public function testSelect()
    {
        $select = $this->_collection->select('', 'ID', '', '5');
        $this->assertEquals(3, $select);

        $select = $this->_collection->select('name=' . "'Herat'");
        $this->assertTrue($select);

        $arr = $this->_collection->next()->toArray();

        $this->assertTrue(
            $arr[0] === '3' && $arr[1] === 'Herat' && $arr[2] === 'AFG' && $arr[3] === 'Herat' && $arr[4] === '186800'
        );
    }

    /**
     */
    public function testFlexSelect()
    {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     * @throws cDbException
     */
    public function testExists()
    {
        $this->assertEquals(true, $this->_collection->exists(1));
        $this->assertEquals(false, $this->_collection->exists(-1));
        $this->assertEquals(false, $this->_collection->exists(''));
        $this->assertEquals(false, $this->_collection->exists(null));
        $this->assertEquals(false, $this->_collection->exists(0));
    }

    /**
     */
    public function testNext()
    {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testFetchObject()
    {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testFetchTable()
    {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testQueryAndFetchStructured()
    {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testCount()
    {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     * @throws cException
     */
    public function testFetchById()
    {
        $column = $this->_collection->fetchById(1);

        $this->assertSame('1', $column->get('ID'));
        $this->assertSame('Kabul', $column->get('Name'));
    }

    /**
     * @throws cException
     */
    public function testFetchById2()
    {
        $ret = $this->_collection->fetchById(1);
        $this->assertTrue(
            $ret->loadByMany(
                [
                    'ID'   => 1,
                    'Name' => 'Kabul',
                ]
            )
        );
    }

    /**
     * @throws cException
     */
    public function testLoadItem()
    {
        $item = $this->_collection->loadItem(1);
        $ar   = [
            'ID'          => '1',
            'Name'        => 'Kabul',
            'CountryCode' => 'AFG',
            'District'    => 'Kabol',
            'Population'  => '1780000',
        ];
        $this->assertSame($item->toArray(), $ar);

        $ar = [
            'ID'          => '1',
            'Name'        => 'Kabul',
            'CountryCode' => 'AFG',
            'District'    => 'Kabol',
            'Population'  => '178888',
        ];
        $item->set('Population', '178888');
        $this->assertSame($item->toArray(), $ar);
        $this->assertEquals(true, $item->get('ID') == $item->set('ID', '5'));

        $item->store();
        $item = $this->_collection->loadItem(1);
        $this->assertFalse($item->get('ID') === $ar['ID'] && $item->get('Population') === $ar['Population']);
    }

    /**
     * check if exception is thrown in function loadItem when item class
     * is not set in collection class
     */
    public function testLoadItemNoItemClass()
    {
        $this->expectException(cException::class);
        $this->_noItemClassCollection->loadItem(1);
    }

    /**
     */
    public function testCreateNewItem()
    {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     * @throws cException
     */
    public function testCopyItem()
    {
        $ret = $this->_collection->fetchById('1');

        $this->assertEquals(true, 3 === count($this->_collection->getAllIds()));

        $this->_collection->copyItem(
            $ret,
            [
                'Name' => 'muuh',
            ]
        );

        $this->assertEquals(true, 4 === count($this->_collection->getAllIds()));
    }

    /**
     * Test {@see ItemCollection::getIdsWhere()}.
     */
    public function testGetIdsWhere()
    {
        $dogColl = new DogCollection();
        $ids = $dogColl->getIdsWhere('name', 'Jake');
        $expected = ['2'];
        $this->assertEquals($expected, $ids);

        $dogColl = new DogCollection();
        $ids = $dogColl->getIdsWhere('size', 'medium');
        $expected = ['1', '2'];
        $this->assertEquals($expected, $ids);

        $dogColl = new DogCollection();
        $ids = $dogColl->getIdsWhere('size', 'medium');
        $expected = ['1', '2'];
        $this->assertEquals($expected, $ids);

        $dogColl = new DogCollection();
        $ids = $dogColl->getIdsWhere('descr', 'strong', 'LIKE');
        $expected = ['3'];
        $this->assertEquals($expected, $ids);

        $dogColl = new DogCollection();
        $ids = $dogColl->getIdsWhere('id', 1, '>');
        $expected = ['2', '3'];
        $this->assertEquals($expected, $ids);
    }


    /**
     */
    public function testGetIdsByWhereClause()
    {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     * @throws cDbException
     */
    public function testGetFieldsByWhereClause()
    {
        $ar = $this->_collection->getFieldsByWhereClause(
            [
                'Name',
            ],
            'ID=1'
        );

        $this->assertEquals('Kabul', $ar[0]['Name']);
        $ar = $this->_collection->getFieldsByWhereClause([], 'ID=1');
        $this->assertEquals(0, count($ar));
    }

    /**
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function testGetAllIds()
    {
        $this->assertEquals(3, count($this->_collection->getAllIds()));
        $this->_collection->delete(1);
        $this->assertEquals(2, count($this->_collection->getAllIds()));
    }

    /**
     */
    public function testDelete()
    {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testDeleteByWhereClause()
    {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function testDeleteBy()
    {
        $dogColl       = new DogCollection();
        $columnsBefore = count($dogColl->getAllIds());
        $this->assertEquals(3, $columnsBefore);
        $dogColl->deleteBy('id', 1);
        $columnsAfter = count($dogColl->getAllIds());
        $this->assertEquals(2, $columnsAfter);
    }

    /**
     * @throws cDbException
     * @throws cException
     */
    public function testFetchArray()
    {
        $dogColl = new DogCollection();
        $dogColl->setEncoding('UTF-8');
        $dogColl->setWhereGroup('date', 'size', 'medium', '=');
        $dogColl->query();
        $ret = $dogColl->fetchArray(
            'id',
            [
                'id',
                'name',
            ]
        );

        $this->assertTrue($ret[1]['id'] == '1');
        $this->assertTrue($ret[1]['name'] == 'Max');
        $this->assertTrue($ret[2]['id'] == '2');
        $this->assertTrue($ret[2]['name'] == 'Jake');
    }

    /**
     * @todo this is no test for a ItemCollection-method!
     *
     * @throws cException
     */
    public function testSetProperty()
    {
        // $db = cRegistry::getDb();
        $ret = $this->_collection->fetchById(1);
        $this->assertFalse($ret->getProperty('bla', 'muh'));

        $ret->setProperty('bla', 'muh', 'maeh');

        $this->assertEquals('maeh', $ret->getProperty('bla', 'muh'));
        $ret->deleteProperty('bla', 'muh');

        $this->assertFalse($ret->getProperty('bla', 'muh'));
    }

    /**
     *
     * @param array $a1
     * @param array $a2
     *
     * @return array
     */
    private function arrayRecursiveDiff(array $a1, array $a2)
    {
        $ret = [];
        foreach ($a1 as $key => $value) {
            if (!array_key_exists($key, $a2)) {
                $ret[$key] = $value;
            } else {
                if (is_array($value)) {
                    $aRecursiveDiff = $this->arrayRecursiveDiff($value, $a2[$key]);
                    if (count($aRecursiveDiff)) {
                        $ret[$key] = $aRecursiveDiff;
                    }
                } else {
                    if ($value != $a2[$key]) {
                        $ret[$key] = $value;
                    }
                }
            }
        }

        return $ret;
    }
}
