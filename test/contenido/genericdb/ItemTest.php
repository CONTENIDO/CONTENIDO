<?php

/**
 *
 * @author    claus.schunk@4fb.de
 * @author    marcus.gnass@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license   http://www.contenido.org/license/LIZENZ.txt
 * @link      http://www.4fb.de
 * @link      http://www.contenido.org
 */

/**
 *
 * @author claus.schunk@4fb.de
 * @author marcus.gnass@4fb.de
 */
class ItemTest extends cTestingTestCase
{
    /**
     *
     * @var DummyItem
     */
    private $_dummyItem;

    /**
     *
     * @var Item
     */
    private $_testItemVirgin;

    /**
     *
     * @var Item
     */
    private $_testItemNonVirgin;

    /**
     * Tables used by this test case
     * @var array
     */
    protected $_tables = ['con_test'];

    /**
     * @throws cDbException
     * @throws cException
     * @throws cTestingException
     */
    protected function setUp(): void
    {
        ini_set('display_errors', true);
        error_reporting(E_ALL);

        $this->setUpTestCaseDbTables();

        // create dummy item of locally defined class
        $this->_dummyItem = new DummyItem();

        // define a virgin
        $this->_testItemVirgin = new TestItem();

        // this is no virgin anymore
        $this->_testItemNonVirgin = new TestItem();
        $this->_testItemNonVirgin->setLoaded(true);
        $this->_testItemNonVirgin->values = [
            'foo'  => 'bar',
            'spam' => 'eggs',
        ];

        $db = cRegistry::getDb();
        $db->query(SqlItem::getCreateConTestStatement());
        $db->query(SqlItem::getInsertConTestStatement());
//        $db->query(SqlItem::getCreateDogStatement());
//        $db->query(SqlItem::getInserDogStatement());
//        $db->query(SqlItem::getCreateDogRfidStatement());
//        $db->query(SqlItem::getInserDogRfidStatement());
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
        // test instanceOf
        $act = $this->_dummyItem;
        $exp = 'DummyItem';
        $this->assertInstanceOf($exp, $act);

        // test name of table
        $act = $this->_readAttribute($this->_dummyItem, 'table');
        $exp = 'table';
        $this->assertSame($exp, $act);

        // test name of primary key
        $act = $this->_dummyItem->getPrimaryKeyName();
        $exp = 'primaryKey';
        $this->assertSame($exp, $act);
    }

    /**
     */
    public function testLoadBy()
    {
        $this->_testItemVirgin->loadBy('ID', '1');
        $this->assertSame(true, $this->_testItemVirgin->isLoaded());
        $this->assertSame('Kabul', $this->_testItemVirgin->get('Name'));
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testLoadByTrue()
    {
        $this->_testItemVirgin->loadBy('ID', '1', true);
        $this->assertSame(true, $this->_testItemVirgin->isLoaded());
        $this->assertSame('Kabul', $this->_testItemVirgin->get('Name'));
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testLoadByFalse()
    {
        $this->_testItemVirgin->loadBy('ID', '1', false);
        $this->assertSame(true, $this->_testItemVirgin->isLoaded());
        $this->assertSame('Kabul', $this->_testItemVirgin->get('Name'));
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testLoadByMany()
    {
        $this->_testItemVirgin->loadByMany(
            [
                'ID'          => '1',
                'CountryCode' => 'AFG',
            ]
        );
        $this->assertSame(true, $this->_testItemVirgin->isLoaded());
        $this->assertSame('Kabul', $this->_testItemVirgin->get('Name'));
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testLoadByManyTrue()
    {
        $this->_testItemVirgin->loadByMany(
            [
                'ID'          => '1',
                'CountryCode' => 'AFG',
            ],
            true
        );
        $this->assertSame(true, $this->_testItemVirgin->isLoaded());
        $this->assertSame('Kabul', $this->_testItemVirgin->get('Name'));
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testLoadByManyFalse()
    {
        $this->_testItemVirgin->loadByMany(
            [
                'ID'          => '1',
                'CountryCode' => 'AFG',
            ],
            false
        );
        $this->assertSame(true, $this->_testItemVirgin->isLoaded());
        $this->assertSame('Kabul', $this->_testItemVirgin->get('Name'));
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     *
     * @todo missing implementation
     */
    public function testLoadByPrimaryKey()
    {
        $this->markTestIncomplete('missing implementation');
    }

    /**
     *
     * @todo missing implementation
     */
    public function testLoadByRecordSet()
    {
        $this->markTestIncomplete('missing implementation');
    }

    /**
     *
     * @todo missing implementation
     */
    public function testIsLoaded()
    {
        $this->markTestIncomplete('missing implementation');
    }

    /**
     * Test getting field of virgin item.
     */
    public function testGetFieldVirgin()
    {
        $act = $this->_testItemVirgin->getField('foo');
        $exp = false;
        $this->assertSame($act, $exp);
    }

    /**
     * Test getting field of non virgin item.
     */
    public function testGetFieldNonVirgin()
    {
        $act = $this->_testItemNonVirgin->getField('foo');
        $exp = 'bar';
        $this->assertSame($act, $exp);
    }

    /**
     * Test getting none existing field of non virgin item.
     */
    public function testGetFieldNonVirginMissing()
    {
        // TODO This should work but it doesn't
        //$this->expectNoticeMessage('Undefined index: bar');
        $this->expectNotice();
        $this->_testItemNonVirgin->getField('bar');
    }

    /**
     * Test getting field of item.
     */
    public function testGet()
    {
        $this->markTestSkipped('this is just an alias for getField');
    }

    /**
     *
     * @todo missing implementation
     */
    public function testSetField()
    {
        $this->markTestIncomplete('missing implementation');
    }

    /**
     * Test getting field of item.
     */
    public function testSet()
    {
        $this->markTestSkipped('this is just an alias for setField');
    }

    /**
     *
     * @todo missing implementation
     */
    public function testStore()
    {
        $this->markTestIncomplete('missing implementation');
    }

    /**
     *
     * @todo missing implementation
     */
    public function testToArray()
    {
        $this->markTestIncomplete('missing implementation');
    }

    /**
     *
     * @todo missing implementation
     */
    public function testToObject()
    {
        $this->markTestIncomplete('missing implementation');
    }

    /**
     *
     * @todo missing implementation
     */
    public function testSetProperty()
    {
        $this->markTestIncomplete('missing implementation');
    }

    /**
     *
     * @todo missing implementation
     */
    public function testGetProperty()
    {
        $this->markTestIncomplete('missing implementation');
    }

    /**
     *
     * @todo missing implementation
     */
    public function testDeleteProperty()
    {
        $this->markTestIncomplete('missing implementation');
    }

    /**
     *
     * @todo missing implementation
     */
    public function testDeletePropertyById()
    {
        $this->markTestIncomplete('missing implementation');
    }

    /**
     *
     * @todo missing implementation
     */
    public function testDelete()
    {
        $this->markTestSkipped('method is commented');
    }

    /**
     *
     * @todo missing implementation
     */
    public function testSetFilters()
    {
        $this->markTestIncomplete('missing implementation');
    }

    /**
     *
     * @todo missing implementation
     */
    public function test_InFilter()
    {
        $this->markTestIncomplete('missing implementation');
    }

    /**
     *
     * @todo missing implementation
     */
    public function testGetMetaObject()
    {
        $this->markTestIncomplete('missing implementation');
    }
}

/**
 *
 * @author marcus.gnass@4fb.de
 */
class DummyItem extends Item
{
    /**
     *
     */
    public function __construct()
    {
        parent::__construct('table', 'primaryKey');
    }
}

