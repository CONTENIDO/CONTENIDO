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
class cHtmlHeaderTest extends cTestingTestCase
{
    public function testConstruct()
    {
        $header = new cHTMLHeader('testContent', 'testClass', 'testId');
        $this->assertSame('<header id="testId" class="testClass">testContent</header>', $header->toHtml());
        $header = new cHTMLHeader('', '', 'testId2');
        $this->assertSame('<header id="testId2"></header>', $header->toHtml());
    }
}
