<?PHP

/**
 * @package    Testing
 * @subpackage GUI_HTML
 * @author     claus.schunk@4fb.de
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */
class cHtmlTableDateTest extends cTestingTestCase
{
    /**
     * @var cHTMLTableData
     */
    protected $_tableData;

    protected function setUp(): void
    {
        $this->_tableData = new cHTMLTableData();
    }

    public function testConstructor()
    {
        $this->assertSame('td', $this->_readAttribute($this->_tableData, '_tag'));
    }

    public function testSetWidth()
    {
        $this->assertSame(null, $this->_tableData->getAttribute('width'));
        $this->_tableData->setWidth(100);
        $this->assertSame(100, $this->_tableData->getAttribute('width'));
    }

    public function testSetHeight()
    {
        $this->assertSame(null, $this->_tableData->getAttribute('height'));
        $this->_tableData->setHeight(100);
        $this->assertSame(100, $this->_tableData->getAttribute('height'));
    }

    public function testSetAlignment()
    {
        $this->assertSame(null, $this->_tableData->getAttribute('align'));
        $this->_tableData->setAlignment(100);
        $this->assertSame(100, $this->_tableData->getAttribute('align'));
    }

    public function testSetVerticalAlignment()
    {
        $this->assertSame(null, $this->_tableData->getAttribute('valign'));
        $this->_tableData->setVerticalAlignment(100);
        $this->assertSame(100, $this->_tableData->getAttribute('valign'));
    }

    public function testSetBackgroundColor()
    {
        $this->assertSame(null, $this->_tableData->getAttribute('bgcolor'));
        $this->_tableData->setBackgroundColor(100);
        $this->assertSame(100, $this->_tableData->getAttribute('bgcolor'));
    }

    public function testSetColspan()
    {
        $this->assertSame(null, $this->_tableData->getAttribute('colspan'));
        $this->_tableData->setColspan(100);
        $this->assertSame(100, $this->_tableData->getAttribute('colspan'));
    }
}

