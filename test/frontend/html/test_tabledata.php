<?PHP
/**
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlTableDateTest extends PHPUnit_Framework_TestCase {

    protected $_tableData;

    public function setUp() {
        $this->_tableData = new cHTMLTableData();
    }

    public function testConstructor() {
        $this->assertSame('td', PHPUnit_Framework_Assert::readAttribute($this->_tableData, '_tag'));
    }

    public function testSetWidth() {
        $this->assertSame(NULL, $this->_tableData->getAttribute('width'));
        $this->_tableData->setWidth(100);
        $this->assertSame(100, $this->_tableData->getAttribute('width'));
    }

    public function testSetHeight() {
        $this->assertSame(NULL, $this->_tableData->getAttribute('height'));
        $this->_tableData->setHeight(100);
        $this->assertSame(100, $this->_tableData->getAttribute('height'));
    }

    public function testSetAlignment() {
        $this->assertSame(NULL, $this->_tableData->getAttribute('align'));
        $this->_tableData->setAlignment(100);
        $this->assertSame(100, $this->_tableData->getAttribute('align'));
    }

    public function testSetVerticalAlignment() {
        $this->assertSame(NULL, $this->_tableData->getAttribute('valign'));
        $this->_tableData->setVerticalAlignment(100);
        $this->assertSame(100, $this->_tableData->getAttribute('valign'));
    }

    public function testSetBackgroundColor() {
        $this->assertSame(NULL, $this->_tableData->getAttribute('bgcolor'));
        $this->_tableData->setBackgroundColor(100);
        $this->assertSame(100, $this->_tableData->getAttribute('bgcolor'));
    }

    public function testSetColspan() {
        $this->assertSame(NULL, $this->_tableData->getAttribute('colspan'));
        $this->_tableData->setColspan(100);
        $this->assertSame(100, $this->_tableData->getAttribute('colspan'));
    }

}
?>
