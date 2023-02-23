<?php

/**
 * @package    Testing
 * @subpackage GUI_HTML
 * @author     claus.schunk@4fb.de
 * @author     marcus.gnass@4fb.de
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

/**
 * @author claus.schunk@4fb.de
 * @author marcus.gnass@4fb.de
 */
class cHTMLAlignmentTableTest extends cTestingTestCase
{
    /**
     * @var cHTML
     */
    private $_element;

    /**
     * @var cHTMLAlignmentTable
     */
    private $_tableEmpty;

    /**
     * @var cHTMLAlignmentTable
     */
    private $_tableInt;

    /**
     * @var cHTMLAlignmentTable
     */
    private $_tableFloat;

    /**
     * @var cHTMLAlignmentTable
     */
    private $_tableFloatAsString;

    /**
     * @var cHTMLAlignmentTable
     */
    private $_tableEmptyString;

    /**
     * @var cHTMLAlignmentTable
     */
    private $_tableString;

    /**
     * @var cHTMLAlignmentTable
     */
    private $_tableBool;

    /**
     * @var cHTMLAlignmentTable
     */
    private $_tableNull;

    /**
     * @var cHTMLAlignmentTable
     */
    private $_tableObject;

    /**
     * @var cHTMLAlignmentTable
     */
    private $_tableData;

    /**
     * Creates tables with values of different datatypes.
     */
    protected function setUp(): void
    {
        ini_set('display_errors', true);
        error_reporting(E_ALL);

        $this->_element = new cHTML();
        $this->_element->setTag('foobar');

        $this->_tableEmpty         = new cHTMLAlignmentTable();
        $this->_tableInt           = new cHTMLAlignmentTable(0);
        $this->_tableFloat         = new cHTMLAlignmentTable(1.0);
        $this->_tableFloatAsString = new cHTMLAlignmentTable('1.23');
        $this->_tableEmptyString   = new cHTMLAlignmentTable('');
        $this->_tableString        = new cHTMLAlignmentTable(' foo ');
        $this->_tableBool          = new cHTMLAlignmentTable(true);
        $this->_tableNull          = new cHTMLAlignmentTable(null);
        $this->_tableObject        = new cHTMLAlignmentTable($this->_element);
        $this->_tableData          = new cHTMLAlignmentTable(0, 1.0, '1.0', '', ' foo ', true, null, $this->_element);
    }

    /**
     * Test constructor which sets the member $_tag.
     * Is already tested by test of parent class!
     */
    public function testConstructTag()
    {
        $act = $this->_readAttribute($this->_tableEmpty, '_tag');
        $exp = 'table';
        $this->assertSame($exp, $act);
    }

    /**
     * Test constructor which sets the member $_contentlessTag.
     */
    public function testConstructContentlessTag()
    {
        $act = $this->_readAttribute($this->_tableEmpty, '_contentlessTag');
        $exp = false;
        $this->assertSame($exp, $act);
    }

    /**
     * Test constructor which sets the member $_data.
     */
    public function testConstructData()
    {
        $act = $this->_readAttribute($this->_tableEmpty, '_data');
        $this->assertSame(true, is_array($act));
        $this->assertEmpty(array_diff([], $act));

        $act = $this->_readAttribute($this->_tableInt, '_data');
        $this->assertSame(true, is_array($act));
        $this->assertEmpty(array_diff([0], $act));

        $act = $this->_readAttribute($this->_tableFloat, '_data');
        $this->assertSame(true, is_array($act));
        $this->assertEmpty(array_diff([1.0], $act));

        $act = $this->_readAttribute($this->_tableFloatAsString, '_data');
        $this->assertSame(true, is_array($act));
        $this->assertEmpty(array_diff(['1.23'], $act));

        $act = $this->_readAttribute($this->_tableEmptyString, '_data');
        $this->assertSame(true, is_array($act));
        $this->assertEmpty(array_diff([''], $act));

        $act = $this->_readAttribute($this->_tableString, '_data');
        $this->assertSame(true, is_array($act));
        $this->assertEmpty(array_diff([' foo '], $act));

        $act = $this->_readAttribute($this->_tableBool, '_data');
        $this->assertSame(true, is_array($act));
        $this->assertEmpty(array_diff([true], $act));

        $act = $this->_readAttribute($this->_tableNull, '_data');
        $this->assertSame(true, is_array($act));
        $this->assertEmpty(array_diff([null], $act));

        $act = $this->_readAttribute($this->_tableObject, '_data');
        $this->assertSame(true, is_array($act));
        $this->assertEquals([$this->_element], $act);

        $act = $this->_readAttribute($this->_tableData, '_data');
        $this->assertSame(true, is_array($act));

        // Usage of json_decode/json_encode is to prevent error that object of class cHTML could not be converted to string!
        $act = json_decode(json_encode($act), true);
        $exp = json_decode(json_encode([0, 1.0, '', ' foo ', true, null, $this->_element]), true);
        $this->assertTrue($exp == $act);
    }

    /**
     * Tests rendering of empty table.
     */
    public function testRenderEmpty()
    {
        // $table = new cHTMLAlignmentTable();
        // $this->assertSame($table->render(), $table->toHtml());
        $act = $this->_tableEmpty->render();
        $exp = '<table cellpadding="0" cellspacing="0"><tr></tr></table>';
        $this->assertSame($exp, $act);
    }

    /**
     * Tests rendering of table w/ int value.
     */
    public function testRenderInt()
    {
        $act = $this->_tableInt->render();
        $exp = '<table cellpadding="0" cellspacing="0"><tr><td>0</td></tr></table>';
        $this->assertSame($exp, $act);
    }

    /**
     * Tests rendering of table w/ float value.
     */
    public function testRenderFloat()
    {
        $act = $this->_tableFloat->render();
        $exp = '<table cellpadding="0" cellspacing="0"><tr><td>1.0</td></tr></table>';
        $this->assertSame($exp, $act);
    }

    /**
     * Tests rendering of table w/ float in string representation value.
     */
    public function testRenderFloatAsString()
    {
        $act = $this->_tableFloatAsString->render();
        $exp = '<table cellpadding="0" cellspacing="0"><tr><td>1.23</td></tr></table>';
        $this->assertSame($exp, $act);
    }

    /**
     * Tests rendering of table w/ empty string.
     */
    public function testRenderEmptyString()
    {
        $act = $this->_tableEmptyString->render();
        $exp = '<table cellpadding="0" cellspacing="0"><tr><td></td></tr></table>';
        $this->assertSame($exp, $act);
    }

    /**
     * Tests rendering of table w/ string value.
     */
    public function testRenderString()
    {
        $act = $this->_tableString->render();
        $exp = '<table cellpadding="0" cellspacing="0"><tr><td> foo </td></tr></table>';
        $this->assertSame($exp, $act);
    }

    /**
     * Tests rendering of table w/ bool value.
     */
    public function testRenderBool()
    {
        $act = $this->_tableBool->render();
        $exp = '<table cellpadding="0" cellspacing="0"><tr><td>1</td></tr></table>';
        $this->assertSame($exp, $act);
    }

    /**
     * Tests rendering of table w/ NULL value.
     */
    public function testRenderNull()
    {
        $act = $this->_tableNull->render();
        $exp = '<table cellpadding="0" cellspacing="0"><tr><td></td></tr></table>';
        $this->assertSame($exp, $act);
    }

    /**
     * Tests rendering of table w/ object.
     */
    public function testRenderObject()
    {
        $act = $this->_tableObject->render();
        $exp = '<table cellpadding="0" cellspacing="0"><tr><td><foobar /></td></tr></table>';
        $this->assertSame($exp, $act);
    }

    /**
     * Tests rendering of table w/ all values.
     */
    public function testRenderData()
    {
        $act = $this->_tableData->render();
        $exp = '<table cellpadding="0" cellspacing="0"><tr><td>0</td><td>1.0</td><td>1.23</td><td></td><td> foo </td><td>1</td><td></td><td><foobar /></td></tr></table>';
        $this->assertSame($exp, $act);
    }
}

