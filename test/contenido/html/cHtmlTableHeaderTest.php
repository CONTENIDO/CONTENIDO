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


