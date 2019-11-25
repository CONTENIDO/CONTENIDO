<?PHP

use PHPUnit\Framework\TestCase;

/**
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlListTest extends TestCase {

    protected $_cList = null;

    protected function setUp(): void {
        $this->_cList = new cHTMLList();
    }

    public function testConstruct() {
        $cList = new cHTMLList('ul', 'testId', 'testclass', array(
            '<li>bla</li>',
            '<li>haa</li>'
        ));
        $this->assertSame('<ul id="testId" class="testclass"><li>bla</li><li>haa</li></ul>', $cList->toHtml());
        $cList = new cHTMLList('ul', 'testId', 'testclass', array());
        $this->assertSame('<ul id="testId" class="testclass"></ul>', $cList->toHtml());
        $cListItem = new cHTMLListItem('testId', 'testClass');
        $cList = new cHTMLList('ul', 'testId', 'testclass', array(
            $cListItem
        ));
        $this->assertSame('<ul id="testId" class="testclass"><li id="testId" class="testClass"></li></ul>', $cList->toHtml());
    }

}
?>