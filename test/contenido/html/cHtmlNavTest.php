<?PHP

/**
 *
 * @author    claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license   https://www.contenido.org/license/LIZENZ.txt
 * @link      https://www.4fb.de
 * @link      https://www.contenido.org
 */
class cHtmlNavTest extends cTestingTestCase
{
    public function testConstruct()
    {
        $nav = new cHTMLNav('testContent', 'testClass', 'testId');
        $this->assertSame('<nav id="testId" class="testClass">testContent</nav>', $nav->toHtml());
        $nav = new cHTMLNav('', '', 'testId2');
        $this->assertSame('<nav id="testId2"></nav>', $nav->toHtml());
    }
}
