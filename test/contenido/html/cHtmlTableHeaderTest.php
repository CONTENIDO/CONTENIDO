<?PHP

/**
 *
 * @author    claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license   http://www.contenido.org/license/LIZENZ.txt
 * @link      http://www.4fb.de
 * @link      http://www.contenido.org
 */
class cHtmlTableHeaderTest extends cTestingTestCase
{
    protected $_tableHeader;

    protected function setUp(): void
    {
        $this->_tableHeader = new cHTMLTableHeader();
    }

    public function testConstructor()
    {
        $this->assertSame('thead', $this->_readAttribute($this->_tableHeader, '_tag'));
    }
}


