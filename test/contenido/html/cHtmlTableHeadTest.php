<?PHP

/**
 *
 * @author    claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license   http://www.contenido.org/license/LIZENZ.txt
 * @link      http://www.4fb.de
 * @link      http://www.contenido.org
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
