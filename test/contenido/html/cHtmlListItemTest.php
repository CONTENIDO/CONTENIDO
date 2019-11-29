<?PHP

/**
 *
 * @author    claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license   http://www.contenido.org/license/LIZENZ.txt
 * @link      http://www.4fb.de
 * @link      http://www.contenido.org
 */
class cHtmlListItemTest extends cTestingTestCase
{
    protected $_cListItem = null;

    protected function setUp(): void
    {
        $this->_cListItem = new cHTMLListItem();
    }

    public function testConstruct()
    {
        $cListItem = new cHTMLListItem('testId', 'testClass');

        $this->assertSame('<li id="testId" class="testClass"></li>', $cListItem->toHtml());
    }
}
