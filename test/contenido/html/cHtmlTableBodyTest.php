<?PHP

/**
 *
 * @author    claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license   https://www.contenido.org/license/LIZENZ.txt
 * @link      https://www.4fb.de
 * @link      https://www.contenido.org
 */
class cHtmlTableBodyTest extends cTestingTestCase
{
    protected $_tableBody;

    protected function setUp(): void
    {
        $this->_tableBody = new cHTMLTableBody();
    }

    public function testConstructor()
    {
        $this->assertSame('tbody', $this->_readAttribute($this->_tableBody, '_tag'));
    }
}
