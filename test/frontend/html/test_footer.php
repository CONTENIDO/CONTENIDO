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
class cHtmlFooterTest extends TestCase {

    protected $_cFooter = null;

    protected function setUp(): void {
        $this->_cFooter = new cHTMLFooter();
    }

    public function testConstruct() {
        $footer = new cHTMLFooter('testContent', 'testClass', 'testId');
        $this->assertSame('<footer id="testId" class="testClass">testContent</footer>', $footer->toHtml());
        $this->assertSame('<footer id=""></footer>', $this->_cFooter->toHtml());
    }

}
?>