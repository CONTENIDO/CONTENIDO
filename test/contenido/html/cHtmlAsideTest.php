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
class cHtmlAsideTest extends cTestingTestCase
{
    public function testArticle()
    {
        $cAside = new cHTMLAside('huhu', 'testclass', 'testid');
        $this->assertSame('<aside id="testid" class="testclass">huhu</aside>', $cAside->toHtml());
    }
}
