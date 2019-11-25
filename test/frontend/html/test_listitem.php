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
class cHtmlListItemTest extends TestCase {

    protected $_cListItem = null;

    protected function setUp(): void {
        $this->_cList = new cHTMLListItem();
    }

    public function testConstruct() {
        $cListItem = new cHTMLListItem('testId', 'testClass');

        $this->assertSame('<li id="testId" class="testClass"></li>', $cListItem->toHtml());
    }

}
?>