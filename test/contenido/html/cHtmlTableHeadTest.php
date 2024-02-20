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
class cHtmlTableHeadTest extends cTestingTestCase
{
    protected $_tableHead;

    protected function setUp(): void
    {
        $this->_tableHead = new cHTMLTableHead();
    }

    public function testConstructor()
    {
        $this->assertSame('th', $this->_readAttribute($this->_tableHead, '_tag'));
    }

    public function testConstructorWithContent()
    {
        $tableHead = new cHTMLTableHead('Dog');
        $this->assertSame('Dog', $this->_readAttribute($tableHead, '_content'));
    }

}
