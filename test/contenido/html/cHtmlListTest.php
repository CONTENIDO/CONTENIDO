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
class cHtmlListTest extends cTestingTestCase
{
    protected $_cList = null;

    protected function setUp(): void
    {
        $this->_cList = new cHTMLList();
    }

    public function testConstruct()
    {
        $cList = new cHTMLList(
            'ul', 'testId', 'testclass', [
                '<li>bla</li>',
                '<li>haa</li>',
            ]
        );
        $this->assertSame('<ul id="testId" class="testclass"><li>bla</li><li>haa</li></ul>', $cList->toHtml());
        $cList = new cHTMLList('ul', 'testId', 'testclass', []);
        $this->assertSame('<ul id="testId" class="testclass"></ul>', $cList->toHtml());
        $cListItem = new cHTMLListItem('testId', 'testClass');
        $cList = new cHTMLList(
            'ul', 'testId', 'testclass', [
                $cListItem,
            ]
        );
        $this->assertSame(
            '<ul id="testId" class="testclass"><li id="testId" class="testClass"></li></ul>',
            $cList->toHtml()
        );
    }
}
