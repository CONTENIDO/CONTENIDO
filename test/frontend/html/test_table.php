<?PHP
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
class cHtmlTableTest extends PHPUnit_Framework_TestCase {

    protected $_table;

    public function setUp() {
        $this->_table = new cHTMLTable();
    }

    public function testConstructor() {
        $this->assertSame('table', PHPUnit_Framework_Assert::readAttribute($this->_table, '_tag'));
        $this->assertSame(0, $this->_table->getAttribute('cellpadding'));
        $this->assertSame(0, $this->_table->getAttribute('cellspacing'));
        $this->assertSame(NULL, $this->_table->getAttribute('border'));
        $this->assertSame(NULL, $this->_table->getAttribute('width'));
    }

    public function testSetCellSpacing() {
        $this->_table->setCellSpacing(100);
        $this->assertSame(100, $this->_table->getAttribute('cellspacing'));
    }

    /**
     * @todo This test has not been implemented yet.
     */
    public function testSetSpacing() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    public function testSetCellPadding() {
        $this->_table->setCellPadding(100);
        $this->assertSame(100, $this->_table->getAttribute('cellpadding'));
    }

    /**
     * @todo This test has not been implemented yet.
     */
    public function testSetPadding() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    public function testSetBorder() {
        $this->_table->setBorder(100);
        $this->assertSame(100, $this->_table->getAttribute('border'));
    }
    public function testSetWidth() {
        $this->_table->setWidth(100);
        $this->assertSame(100, $this->_table->getAttribute('width'));
    }

}
?>