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
