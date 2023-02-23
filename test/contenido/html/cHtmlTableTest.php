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
class cHtmlTableTest extends cTestingTestCase
{
    /**
     *
     * @var cHTMLTable
     */
    protected $_table;

    /**
     */
    protected function setUp(): void
    {
        $this->_table = new cHTMLTable();
    }

    /**
     */
    public function testConstructor()
    {
        // test member $_tag
        $act = $this->_readAttribute($this->_table, '_tag');
        $exp = 'table';
        $this->assertSame($exp, $act);
        // test attribute cellpadding
        $act = $this->_table->getAttribute('cellpadding');
        $exp = 0;
        $this->assertSame($exp, $act);
        // test attribute cellspacing
        $act = $this->_table->getAttribute('cellspacing');
        $exp = 0;
        $this->assertSame($exp, $act);
        // test attribute border
        $act = $this->_table->getAttribute('border');
        $exp = null;
        $this->assertSame($exp, $act);
    }

    /**
     */
    public function testSetCellSpacing()
    {
        $this->_table->setCellSpacing(100);
        $act = $this->_table->getAttribute('cellspacing');
        $exp = 100;
        $this->assertSame($exp, $act);
    }

    /**
     */
    public function testSetSpacing()
    {
        $this->_table->setSpacing(100);
        $act = $this->_table->getAttribute('cellspacing');
        $exp = 100;
        $this->assertSame($exp, $act);
    }

    /**
     */
    public function testSetCellPadding()
    {
        $this->_table->setCellPadding(100);
        $act = $this->_table->getAttribute('cellpadding');
        $exp = 100;
        $this->assertSame($exp, $act);
    }

    /**
     */
    public function testSetPadding()
    {
        $this->_table->setPadding(100);
        $act = $this->_table->getAttribute('cellpadding');
        $exp = 100;
        $this->assertSame($exp, $act);
    }

    /**
     */
    public function testSetBorder()
    {
        $this->_table->setBorder(100);
        $act = $this->_table->getAttribute('border');
        $exp = 100;
        $this->assertSame($exp, $act);
    }

    /**
     */
    public function testSetWidth()
    {
        $this->_table->setWidth(100);
        $act = $this->_table->getAttribute('width');
        $exp = 100;
        $this->assertSame($exp, $act);
    }
}

