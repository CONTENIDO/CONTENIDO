<?php
/**
 *
 * @version SVN Revision $Rev:$
 *
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

/**
 * Class to test email validator.
 *
 * Most of the code below s taken over from
 * http://code.google.com/p/php-email-address-validation/
 *
 * @package Testing
 * @subpackage Test_Validator
 */
require_once 'sqlStatements.php';
class ItemCollectionTest extends PHPUnit_Framework_TestCase {

    /**
     * @ collection
     */
    protected $_collection = null;

    /**
     * @ database instance
     */
    protected $_db = null;

    public function setUp() {
        global $cfg;

        $this->_db = cRegistry::getDb();

        $cfg['tab']['con_test'] = 'con_test';
        $cfg['tab']['con_test_dog'] = 'con_test_dog';
        $cfg['tab']['con_test_rfid_dog'] = 'con_test_rfid_dog';

        $this->_collection = new TCollection();
        $this->_noItemClassCollection = new TFCollection();

        $this->_db->query(SqlStatement::getCreateConTestStatement());
        $this->_db->query(SqlStatement::getCreateDogStatement());
        $this->_db->query(SqlStatement::getCreateDogRfidStatement());

        $this->_db->query(SqlStatement::getInserDogStatement());
        $this->_db->query(SqlStatement::getInserDogRfidStatement());
        $this->_db->query(SqlStatement::getInsertConTestStatement());
    }

    public function tearDown() {
        $sql = SqlStatement::getDeleteStatement(array(
            'con_test_dog',
            'con_test_rfid_dog',
            'con_test'
        ));

        $this->_db->query($sql);
        unset($cfg['tab']['con_test']);
        unset($cfg['tab']['con_test_dog']);
        unset($cfg['tab']['con_test_rfid_dog']);
    }

    /**
     * @test
     */
    public function existsTest() {
        $this->assertEquals(true, $this->_collection->exists(1));
        $this->assertEquals(false, $this->_collection->exists(-1));
        $this->assertEquals(false, $this->_collection->exists(''));
        $this->assertEquals(false, $this->_collection->exists(NULL));
        $this->assertEquals(false, $this->_collection->exists(0));
    }

    /**
     * @test
     */
    public function fetchByIdTest() {
        $column = $this->_collection->fetchById(1);

        $this->assertSame('Kabul', $column->get('Name'));
        $this->assertSame('1', $column->get('ID'));
        // check for none existing row
        $this->assertSame('', $column->get('df'));
    }

    /**
     * check if exception is thrown in function loaditem when item class
     * is not set in collection class
     *
     * @test
     * @expectedException cException
     */
    public function LoadItemNoItemClassTest() {
        $this->_noItemClassCollection->loadItem(1);
    }

    /**
     * @test
     */
    public function GetAllIdsTest() {
        $this->assertEquals(3, count($this->_collection->getAllIds()));
        $this->_collection->delete(1);
        $this->assertEquals(2, count($this->_collection->getAllIds()));
    }

    /**
     * @test
     */
    public function selectTest() {
        $select = $this->_collection->select('', 'ID', '', '5');
        $this->assertEquals(3, $select);

        $select = $this->_collection->select('name=' . "'Herat'");
        $this->assertTrue($select);

        $arr = $this->_collection->next()->toArray();

        $this->assertTrue($arr[0] === '3' && $arr[1] === 'Herat' && $arr[2] === 'AFG' && $arr[3] === 'Herat' && $arr[4] === '186800');
    }

    /**
     * @expectedException cInvalidArgumentException
     * @test
     */
    public function ConstructorTest() {
        $col = new ITCollection();
    }

    /**
     * @expectedException cInvalidArgumentException
     * @test
     */
    public function Constructor2Test() {
        $col = new TITCollection();
    }

    /**
     * @test
     */
    public function loadManyTest() {
        $ret = $this->_collection->fetchById(1);
        $this->assertTrue($ret->loadByMany(array(
            'ID' => 1,
            'Name' => 'Kabul'
        )));
    }

    /**
     * @test
     */
    public function copyItemTest() {
        $ret = $this->_collection->fetchById('1');

        $this->assertEquals(true, 3 === count($this->_collection->getAllIds()));

        $this->_collection->copyItem($ret, array(
            'Name' => 'muuh'
        ));

        $this->assertEquals(true, 4 === count($this->_collection->getAllIds()));
    }

    /**
     * @test
     */
    public function getFieldsByWhereClauseTest() {
        $ar = $this->_collection->getFieldsByWhereClause(array(
            'Name'
        ), 'ID=1');

        $this->assertEquals('Kabul', $ar[0]['Name']);
        $ar = $this->_collection->getFieldsByWhereClause(array(), 'ID=1');
        $this->assertEquals(0, count($ar));
    }

    /**
     * @test
     */
    public function setPropertyTest() {
        $db = cRegistry::getDb();
        $ret = $this->_collection->fetchById(1);
        $this->assertFalse($ret->getProperty('bla', 'muh'));

        $ret->setProperty('bla', 'muh', 'maeh');

        $this->assertEquals('maeh', $ret->getProperty('bla', 'muh'));
        $ret->deleteProperty('bla', 'muh');

        $this->assertFalse($ret->getProperty('bla', 'muh'));
    }

    /**
     * @expectedException cInvalidArgumentException
     * @test
     */
    public function linkTest() {
        $dogColl = new DogCollection();
        $dogColl->link('DogRfidCollection');
        $dogColl = new DogCollection();
        $dogColl->link('non_existing_class');
    }

    /**
     * @test
     */
    public function setWhereTest() {
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
     * @test
     */
    public function setOrderTest() {
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

    public function testdeleteBy() {
        $dogColl = new DogCollection();
        $columnsBefore = count($dogColl->getAllIds());
        $this->assertEquals(3, $columnsBefore);
        $dogColl->deleteBy('id', 1);
        $columnsAfter = count($dogColl->getAllIds());
        $this->assertEquals(2, $columnsAfter);
    }

}
