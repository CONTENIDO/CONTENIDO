<?php
//$path = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../ItemCollection/'));
//require_once ($path . '/sqlStatements.php');
//require_once ('sqlStatements.php');
class ItemTest extends PHPUnit_Framework_TestCase {

    /**
     * @ collection
     */
    protected $_collection = null;

    protected $_item = null;

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
        $this->_item = new TItem();
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
    public function testGet() {
        $column = $this->_collection->fetchById(1);
        $this->assertSame('Kabul', $column->get('Name'));
        $this->assertSame('1', $column->get('ID'));
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
     * @test
     */
    public function LoadItemTest() {
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
     * @test
     * @expectedException cException
     */
    public function LoadItemNoItemClassTest() {
        $this->_noItemClassCollection->loadItem(1);
    }

}
