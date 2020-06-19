<?PHP

/**
 *
 * @author    claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license   http://www.contenido.org/license/LIZENZ.txt
 * @link      http://www.4fb.de
 * @link      http://www.contenido.org
 */
class cHtmlTableRowTest extends cTestingTestCase
{
    protected $_tableRow;

    protected function setUp(): void
    {
        $this->_tableRow = new cHTMLTableRow();
    }

    public function testConstructor()
    {
        $this->assertSame('tr', $this->_readAttribute($this->_tableRow, '_tag'));
    }
}


