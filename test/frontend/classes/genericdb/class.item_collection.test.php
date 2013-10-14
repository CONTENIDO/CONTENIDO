<?php
/**
 *
 * @version SVN Revision $Rev:$
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

require_once 'mockup/class.sql_item_collection.php';

/**
 *
 * @package Testing
 * @subpackage Test_Validator
 */
class ItemCollectionTest extends PHPUnit_Framework_TestCase {

    /**
     *
     * @var TCollection
     */
    protected $_collection;

    /**
     */
    public function setUp() {
        ini_set('display_errors', true);
        error_reporting(E_ALL);

        global $cfg; // don't use cRegistry!
        $cfg['tab']['con_test'] = 'con_test';
        $cfg['tab']['con_test_dog'] = 'con_test_dog';
        $cfg['tab']['con_test_rfid_dog'] = 'con_test_rfid_dog';

        $this->_collection = new TCollection();
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
     */
    public function tearDown() {
        $sql = SqlItemCollection::getDeleteStatement(array(
            'con_test_dog',
            'con_test_rfid_dog',
            'con_test'
        ));
        cRegistry::getDb()->query($sql);

        global $cfg; // don't use cRegistry!
        unset($cfg['tab']['con_test']);
        unset($cfg['tab']['con_test_dog']);
        unset($cfg['tab']['con_test_rfid_dog']);
    }

    /**
     * @expectedException cInvalidArgumentException
     */
    public function testConstruct() {
        $col = new ITCollection();
    }

    /**
     * @expectedException cInvalidArgumentException
     */
    public function testConstruct2() {
        $col = new TITCollection();
    }

    /**
     */
    public function testSetEncoding() {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     * @expectedException cInvalidArgumentException
     */
    public function testLink() {
        $dogColl = new DogCollection();
        $dogColl->link('DogRfidCollection');
        $dogColl = new DogCollection();
        $dogColl->link('non_existing_class');
    }

    /**
     */
    public function testSetLimit() {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testSetWhere() {
        $ar = array(
            'id' => '1',
            'name' => 'Max',
            'descr' => 'Its distinctive appearance and deep foghorn voice make it stand out in a crowd.',
            'size' => 'medium',
            'date' => '2013-09-26 12:14:28'
        );
        $dogColl = new DogCollection();
        $dogColl->setWhere('name', 'Jake', '=');
        $dogColl->setWhere('size', 'medium', '=');
        $dogColl->deleteWhere('name', 'Jake', '=');
        $dogColl->query();
        $dogColl->setLimit(0, 1);
        $dogColl->query();

        $ref = $dogColl->next();
        // print_r($ref);
        $this->assertEquals($ref->toArray(), $ar);
    }

    /**
     */
    public function testDeleteWhere() {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testSetWhereGroup() {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testDeleteWhereGroup() {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testSetInnerGroupCondition() {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testSetGroupCondition() {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testResetQuery() {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testQuery() {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testSetOrder() {
        $asc = array(
            'id' => '2',
            'name' => 'Jake',
            'descr' => 'It loves human companionship and being part of the group.',
            'size' => 'medium',
            'date' => '2013-09-26 12:14:28'
        );
        $des = array(
            'id' => '1',
            'name' => 'Max',
            'descr' => 'Its distinctive appearance and deep foghorn voice make it stand out in a crowd.',
            'size' => 'medium',
            'date' => '2013-09-26 12:14:28'
        );

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
     */
    public function testAddResultField() {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testRemoveResultField() {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testSelect() {
        $select = $this->_collection->select('', 'ID', '', '5');
        $this->assertEquals(3, $select);

        $select = $this->_collection->select('name=' . "'Herat'");
        $this->assertTrue($select);

        $arr = $this->_collection->next()->toArray();

        $this->assertTrue($arr[0] === '3' && $arr[1] === 'Herat' && $arr[2] === 'AFG' && $arr[3] === 'Herat' && $arr[4] === '186800');
    }

    /**
     */
    public function testFlexSelect() {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testExists() {
        $this->assertEquals(true, $this->_collection->exists(1));
        $this->assertEquals(false, $this->_collection->exists(-1));
        $this->assertEquals(false, $this->_collection->exists(''));
        $this->assertEquals(false, $this->_collection->exists(NULL));
        $this->assertEquals(false, $this->_collection->exists(0));
    }

    /**
     */
    public function testNext() {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testFetchObject() {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testFetchTable() {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testQueryAndFetchStructured() {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testCount() {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testFetchById() {
        $column = $this->_collection->fetchById(1);

        $this->assertSame('1', $column->get('ID'));
        $this->assertSame('Kabul', $column->get('Name'));
    }

    /**
     */
    public function testFetchById2() {
        $ret = $this->_collection->fetchById(1);
        $this->assertTrue($ret->loadByMany(array(
            'ID' => 1,
            'Name' => 'Kabul'
        )));
    }

    /**
     */
    public function testLoadItem() {
        $item = $this->_collection->loadItem(1);
        $ar = array(
            'ID' => '1',
            'Name' => 'Kabul',
            'CountryCode' => 'AFG',
            'District' => 'Kabol',
            'Population' => '1780000'
        );
        $this->assertSame($item->toArray(), $ar);

        $ar = array(
            'ID' => '1',
            'Name' => 'Kabul',
            'CountryCode' => 'AFG',
            'District' => 'Kabol',
            'Population' => '178888'
        );
        $item->set('Population', '178888');
        $this->assertSame($item->toArray(), $ar);
        $this->assertEquals(true, $item->get('ID') == $item->set('ID', '5'));

        $item->store();
        $item = $this->_collection->loadItem(1);
        $this->assertFalse($item->get('ID') === $ar['ID'] && $item->get('Population') === $ar['Population']);
    }

    /**
     * check if exception is thrown in function loaditem when item class
     * is not set in collection class
     *
     * @expectedException cException
     */
    public function testLoadItemNoItemClass() {
        $this->_noItemClassCollection->loadItem(1);
    }

    /**
     */
    public function testCreateNewItem() {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testCopyItem() {
        $ret = $this->_collection->fetchById('1');

        $this->assertEquals(true, 3 === count($this->_collection->getAllIds()));

        $this->_collection->copyItem($ret, array(
            'Name' => 'muuh'
        ));

        $this->assertEquals(true, 4 === count($this->_collection->getAllIds()));
    }

    /**
     */
    public function testGetIdsByWhereClause() {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testGetFieldsByWhereClause() {
        $ar = $this->_collection->getFieldsByWhereClause(array(
            'Name'
        ), 'ID=1');

        $this->assertEquals('Kabul', $ar[0]['Name']);
        $ar = $this->_collection->getFieldsByWhereClause(array(), 'ID=1');
        $this->assertEquals(0, count($ar));
    }

    /**
     */
    public function testGetAllIds() {
        $this->assertEquals(3, count($this->_collection->getAllIds()));
        $this->_collection->delete(1);
        $this->assertEquals(2, count($this->_collection->getAllIds()));
    }

    /**
     */
    public function testDelete() {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testDeleteByWhereClause() {
        $this->markTestIncomplete('incomplete implementation');
    }

    /**
     */
    public function testDeleteBy() {
        $dogColl = new DogCollection();
        $columnsBefore = count($dogColl->getAllIds());
        $this->assertEquals(3, $columnsBefore);
        $dogColl->deleteBy('id', 1);
        $columnsAfter = count($dogColl->getAllIds());
        $this->assertEquals(2, $columnsAfter);
    }

    /**
     */
    public function testFetchArray() {
        $dogColl = new DogCollection();
        $dogColl->setEncoding('UTF-8');
        $dogColl->setWhereGroup('date', 'size', 'medium', '=');
        $dogColl->query();
        $ret = $dogColl->fetchArray('id', array(
            'id',
            'name'
        ));

        $this->assertTrue($ret[1]['id'] == '1');
        $this->assertTrue($ret[1]['name'] == 'Max');
        $this->assertTrue($ret[2]['id'] == '2');
        $this->assertTrue($ret[2]['name'] == 'Jake');
    }

    /**
     *
     * @todo this is no test for a ItemCollection-method!
     */
    public function testSetProperty() {
        $db = cRegistry::getDb();
        $ret = $this->_collection->fetchById(1);
        $this->assertFalse($ret->getProperty('bla', 'muh'));

        $ret->setProperty('bla', 'muh', 'maeh');

        $this->assertEquals('maeh', $ret->getProperty('bla', 'muh'));
        $ret->deleteProperty('bla', 'muh');

        $this->assertFalse($ret->getProperty('bla', 'muh'));
    }
}
