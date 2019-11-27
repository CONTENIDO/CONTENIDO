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
class cHtmlLabelTest extends TestCase {

    public function testConstruct() {
        $cLabel = new cHTMLLabel('testText', 'testLabel', '', 'testId');
        $this->assertSame('<label id="testId" for="testLabel">testText</label>', $cLabel->toHtml());
        $cLabel = new cHTMLLabel('testText', 'testLabel', 'testClass', 'testId2');
        $this->assertSame('<label id="testId2" class="testClass" for="testLabel">testText</label>', $cLabel->toHtml());
    }

}
?>