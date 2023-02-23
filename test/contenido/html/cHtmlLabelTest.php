<?PHP

/**
 *
 * @author    claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license   https://www.contenido.org/license/LIZENZ.txt
 * @link      https://www.4fb.de
 * @link      https://www.contenido.org
 */
class cHtmlLabelTest extends cTestingTestCase
{
    public function testConstruct()
    {
        $cLabel = new cHTMLLabel('testText', 'testLabel', '', 'testId');
        $this->assertSame('<label id="testId" for="testLabel">testText</label>', $cLabel->toHtml());
        $cLabel = new cHTMLLabel('testText', 'testLabel', 'testClass', 'testId2');
        $this->assertSame('<label id="testId2" class="testClass" for="testLabel">testText</label>', $cLabel->toHtml());
    }
}
