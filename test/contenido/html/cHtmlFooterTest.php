<?PHP

/**
 *
 * @author    claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license   http://www.contenido.org/license/LIZENZ.txt
 * @link      http://www.4fb.de
 * @link      http://www.contenido.org
 */
class cHtmlFooterTest extends cTestingTestCase
{
    public function testConstruct()
    {
        $footer = new cHTMLFooter('testContent', 'testClass', 'testId');
        $this->assertSame('<footer id="testId" class="testClass">testContent</footer>', $footer->toHtml());
        $footer = new cHTMLFooter('', '', 'testId2');
        $this->assertSame('<footer id="testId2"></footer>', $footer->toHtml());
    }
}
