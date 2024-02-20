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
class cHtmlHeaderHgroupTest extends cTestingTestCase
{
    public function testConstruct()
    {
        $hgroup = new cHTMLHgroup('testContent', 'testClass', 'testId');
        $this->assertSame('<hgroup id="testId" class="testClass">testContent</hgroup>', $hgroup->toHtml());
        $hgroup = new cHTMLHgroup('', '', 'testId2');
        $this->assertSame('<hgroup id="testId2"></hgroup>', $hgroup->toHtml());
    }
}
