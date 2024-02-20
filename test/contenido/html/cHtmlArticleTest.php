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
class cHtmlArticleTest extends cTestingTestCase
{
    public function testArticle()
    {
        $cArticle = new cHTMLArticle('huhuhuhuhu', 'testclass', 'testid');
        $this->assertSame('<article id="testid" class="testclass">huhuhuhuhu</article>', $cArticle->toHtml());
    }
}
