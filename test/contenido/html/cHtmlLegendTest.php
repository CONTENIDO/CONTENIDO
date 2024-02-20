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
class cHtmlLegendTest extends cTestingTestCase
{
    public function testConstruct()
    {
        $cLegend = new cHTMLLegend('testContent', 'testClass', 'testId');
        $this->assertSame('<legend id="testId" class="testClass">testContent</legend>', $cLegend->toHtml());
        $cLegend = new cHTMLLegend('', '', 'testId2');
        $this->assertSame('<legend id="testId2"></legend>', $cLegend->toHtml());
    }
}
