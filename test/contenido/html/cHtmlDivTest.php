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
class cHtmlDivTest extends cTestingTestCase
{
    public function testConstruct()
    {
        $div = new cHTMLDiv('testContent', 'testClass', 'testId');
        $this->assertSame('<div id="testId" class="testClass">testContent</div>', $div->toHtml());

        $div = new cHTMLDiv('', '', 'testId2');
        $this->assertSame('<div id="testId2"></div>', $div->toHtml());
    }
}
