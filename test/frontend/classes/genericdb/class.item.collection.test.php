<?php
/**
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
     */
    public function testConstruct() {
        // test construction w/o specified table
        try {
            $col = new ITCollection();
            $this->fail('should have thrown cInvalidArgumentException');
        } catch (cInvalidArgumentException $e) {
            $this->assertEquals('ItemCollection: No table specified. Inherited classes *need* to set a table', $e->getMessage());
        }

        // test construction w/o specified primary key
        try {
            $col = new TITCollection();
            $this->fail('should have thrown cInvalidArgumentException');
        } catch (cInvalidArgumentException $e) {
            $this->assertEquals('No primary key specified. Inherited classes *need* to set a primary key', $e->getMessage());
        }
    }

    /**
     */
    public function testSetEncoding() {
        $encoding = 'UTF-8';
        $this->_collection->setEncoding($encoding);

        // test member _encoding of collection
        $act = PHPUnit_Framework_Assert::readAttribute($this->_collection, '_encoding');
        $this->assertEquals($encoding, $act);

        // test member _sEncoding of driver of collection
        $_driver = PHPUnit_Framework_Assert::readAttribute($this->_collection, '_driver');
        $act = PHPUnit_Framework_Assert::readAttribute($_driver, '_sEncoding');
        $this->assertEquals($encoding, $act);
    }

    /**
     */
    public function testLink() {
        $dogColl = new DogCollection();

        // test linking of known class
        $dogColl->link('DogRfidCollection');
        $_links = PHPUnit_Framework_Assert::readAttribute($dogColl, '_links');
        $this->assertSame(true, is_array($_links));
        $this->assertSame(true, array_key_exists('DogRfidCollection', $_links));
        $this->assertSame(true, $_links['DogRfidCollection'] instanceof DogRfidCollection);

        // test linking of unknown class
        try {
            $dogColl->link('non_existing_class');
            $this->fail('should have thrown cInvalidArgumentException');
        } catch (cInvalidArgumentException $e) {
            $this->assertEquals('Could not find class [non_existing_class] for use with link in class DogCollection', $e->getMessage());
        }
    }

    /**
     */
    public function testSetLimit() {
        $_limitStart = 1;
        $_limitCount = 2;
        $this->_collection->setLimit($_limitStart, $_limitCount);

        // test member _limitStart of collection
        $act = PHPUnit_Framework_Assert::readAttribute($this->_collection, '_limitStart');
        $this->assertEquals($_limitStart, $act);

        // test member _limitCount of collection
        $act = PHPUnit_Framework_Assert::readAttribute($this->_collection, '_limitCount');
        $this->assertEquals($_limitCount, $act);
    }

    /**
     * Test setting a global where condition w/o operator.
     */
    public function testSetWhere() {
        $this->_collection->setWhere('foo', 'bar');
        $act = PHPUnit_Framework_Assert::readAttribute($this->_collection, '_where');
        $exp = array();
        $exp['global'] = array();
        $exp['global']['foo'] = array();
        $exp['global']['foo']['operator'] = '=';
        $exp['global']['foo']['restriction'] = 'bar';
        $exp['groups'] = array();
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));
    }

    /**
     * Test setting a global where condition w/ operator.
     */
    public function testSetWhereOperator() {
        $this->_collection->setWhere('foo', 'bar', 'LIKE');
        $act = PHPUnit_Framework_Assert::readAttribute($this->_collection, '_where');
        $exp = array();
        $exp['global'] = array();
        $exp['global']['foo'] = array();
        $exp['global']['foo']['operator'] = 'LIKE';
        $exp['global']['foo']['restriction'] = 'bar';
        $exp['groups'] = array();
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));
    }

    /**
     * This method tests deleting nonexistant where conditions from a collection
     * which has no conditions at all.
     */
    public function testDeleteWhereUnconditioned() {
        // test deleting a nonexistant where condition
        $this->_collection->deleteWhere('foo', 'bar');
        $act = PHPUnit_Framework_Assert::readAttribute($this->_collection, '_where');
        $exp = array();
        $exp['global'] = array();
        $exp['groups'] = array();
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));

        // test deleting a nonexistant where condition w/ default operator
        $this->_collection->deleteWhere('foo', 'bar', '=');
        $act = PHPUnit_Framework_Assert::readAttribute($this->_collection, '_where');
        $exp = array();
        $exp['global'] = array();
        $exp['groups'] = array();
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));

        // test deleting a nonexistant where condition w/ nondefault operator
        $this->_collection->deleteWhere('foo', 'bar', 'LIKE');
        $act = PHPUnit_Framework_Assert::readAttribute($this->_collection, '_where');
        $exp = array();
        $exp['global'] = array();
        $exp['groups'] = array();
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));
    }

    /**
     * This method tests deleting nonexistant where conditions from a collection
     * which has some conditions defined.
     */
    public function testDeleteWhereConditioned() {
        // test deleting a where condition when there is another condition for
        // this field but w/ another restriction
        $this->_collection->setWhere('foo', 'bar');
        $this->_collection->deleteWhere('foo', 'eggs');
        $act = PHPUnit_Framework_Assert::readAttribute($this->_collection, '_where');
        $exp = array();
        $exp['global'] = array();
        $exp['global']['foo'] = array();
        $exp['global']['foo']['operator'] = '=';
        $exp['global']['foo']['restriction'] = 'bar';
        $exp['groups'] = array();
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));

        // test deleting a where condition when there is another condition for
        // this restriction but w/ another field
        $this->_collection->setWhere('foo', 'bar');
        $this->_collection->deleteWhere('spam', 'bar');
        $act = PHPUnit_Framework_Assert::readAttribute($this->_collection, '_where');
        $exp = array();
        $exp['global'] = array();
        $exp['global']['foo'] = array();
        $exp['global']['foo']['operator'] = '=';
        $exp['global']['foo']['restriction'] = 'bar';
        $exp['groups'] = array();
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));

        // test deleting a where condition when there is another condition for
        // this restriction but w/ another field
        $this->_collection->setWhere('foo', 'bar');
        $this->_collection->deleteWhere('foo', 'bar', 'LIKE');
        $act = PHPUnit_Framework_Assert::readAttribute($this->_collection, '_where');
        $exp = array();
        $exp['global'] = array();
        $exp['global']['foo'] = array();
        $exp['global']['foo']['operator'] = '=';
        $exp['global']['foo']['restriction'] = 'bar';
        $exp['groups'] = array();
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));

        // test deleting a where condition when there is a condition for this
        // field w/ this restriction
        $this->_collection->setWhere('foo', 'bar');
        $this->_collection->deleteWhere('foo', 'bar');
        $act = PHPUnit_Framework_Assert::readAttribute($this->_collection, '_where');
        $exp = array();
        $exp['global'] = array();
        $exp['groups'] = array();
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));
    }

    /**
     */
    public function testSetWhereGroup() {
        $this->_collection->setWhereGroup('myGroup', 'foo', 'bar');
        $act = PHPUnit_Framework_Assert::readAttribute($this->_collection, '_where');
        $exp = array();
        $exp['global'] = array();
        $exp['groups'] = array();
        $exp['groups']['myGroup'] = array();
        $exp['groups']['myGroup']['foo'] = array();
        $exp['groups']['myGroup']['foo']['operator'] = '=';
        $exp['groups']['myGroup']['foo']['restriction'] = 'bar';
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));
    }

    /**
     */
    public function testSetWhereGroupOperator() {
        $this->_collection->setWhereGroup('myGroup', 'foo', 'bar', 'LIKE');
        $act = PHPUnit_Framework_Assert::readAttribute($this->_collection, '_where');
        $exp = array();
        $exp['global'] = array();
        $exp['groups'] = array();
        $exp['groups']['myGroup'] = array();
        $exp['groups']['myGroup']['foo'] = array();
        $exp['groups']['myGroup']['foo']['operator'] = 'LIKE';
        $exp['groups']['myGroup']['foo']['restriction'] = 'bar';
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));
    }

    /**
     * @todo should behave the same way as deleteWhere().
     * @see testDeleteWhereUnconditioned()
     */
    public function testDeleteWhereGroupUnconditioned() {
        try {
            $this->_collection->deleteWhereGroup('myGroup', 'foo', 'bar');
            $this->fail('should have thrown PHPUnit_Framework_Error_Notice');
        } catch (PHPUnit_Framework_Error_Notice $e) {
            $this->assertEquals('Undefined index: myGroup', $e->getMessage());
        }
    }

    /**
     */
    public function testDeleteWhereGroupConditioned() {
        // test deleting a where condition when there is another condition for
        // this field but w/ another restriction
        $this->_collection->setWhereGroup('myGroup', 'foo', 'bar');
        $this->_collection->deleteWhereGroup('myGroup', 'foo', 'eggs');
        $act = PHPUnit_Framework_Assert::readAttribute($this->_collection, '_where');
        $exp = array();
        $exp['global'] = array();
        $exp['groups'] = array();
        $exp['groups']['myGroup'] = array();
        $exp['groups']['myGroup']['foo'] = array();
        $exp['groups']['myGroup']['foo']['operator'] = '=';
        $exp['groups']['myGroup']['foo']['restriction'] = 'bar';
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));

        // test deleting a where condition when there is another condition for
        // this restriction but w/ another field
        $this->_collection->setWhereGroup('myGroup', 'foo', 'bar');
        $this->_collection->deleteWhereGroup('myGroup', 'spam', 'bar');
        $act = PHPUnit_Framework_Assert::readAttribute($this->_collection, '_where');
        $exp = array();
        $exp['global'] = array();
        $exp['groups'] = array();
        $exp['groups']['myGroup'] = array();
        $exp['groups']['myGroup']['foo'] = array();
        $exp['groups']['myGroup']['foo']['operator'] = '=';
        $exp['groups']['myGroup']['foo']['restriction'] = 'bar';
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));

        // test deleting a where condition when there is another condition for
        // this restriction but w/ another field
        $this->_collection->setWhereGroup('myGroup', 'foo', 'bar');
        $this->_collection->deleteWhereGroup('myGroup', 'foo', 'bar', 'LIKE');
        $act = PHPUnit_Framework_Assert::readAttribute($this->_collection, '_where');
        $exp = array();
        $exp['global'] = array();
        $exp['groups'] = array();
        $exp['groups']['myGroup'] = array();
        $exp['groups']['myGroup']['foo'] = array();
        $exp['groups']['myGroup']['foo']['operator'] = '=';
        $exp['groups']['myGroup']['foo']['restriction'] = 'bar';
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));

        // test deleting a where condition when there is a condition for this
        // field w/ this restriction
        $this->_collection->setWhereGroup('myGroup', 'foo', 'bar');
        $this->_collection->deleteWhereGroup('myGroup', 'foo', 'bar');
        $act = PHPUnit_Framework_Assert::readAttribute($this->_collection, '_where');
        $exp = array();
        $exp['global'] = array();
        $exp['groups'] = array();
        $exp['groups']['myGroup'] = array();
        $this->assertEquals(true, is_array($act));
        $this->assertEquals(0, count($this->arrayRecursiveDiff($act, $exp)));
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

    /**
     *
     * @param array $a1
     * @param array $a2
     * @return array
     */
    private function arrayRecursiveDiff(array $a1, array $a2) {
        $ret = array();
        foreach ($a1 as $key => $value) {
            if (!array_key_exists($key, $a2)) {
                $ret[$key] = $value;
            } else if (is_array($value)) {
                $aRecursiveDiff = $this->arrayRecursiveDiff($value, $a2[$key]);
                if (count($aRecursiveDiff)) {
                    $ret[$key] = $aRecursiveDiff;
                }
            } else if ($value != $a2[$key]) {
                $ret[$key] = $value;
            }
        }
        return $ret;
    }
}
