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
class cHtmlDivTest extends TestCase {

    public function testConstruct() {
        $div = new cHTMLDiv('testContent', 'testClass', 'testId');
        $this->assertSame('<div id="testId" class="testClass">testContent</div>', $div->toHtml());

        $div = new cHTMLDiv('', '', 'testId2');
        $this->assertSame('<div id="testId2"></div>', $div->toHtml());
    }

}
?>