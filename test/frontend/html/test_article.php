<?PHP
/**
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlArticleTest extends PHPUnit_Framework_TestCase {

    public function testArticle() {
        $cArticle = new cHTMLArticle('huhuhuhuhu', 'testclass', 'testid');
        $this->assertSame('<article id="testid" class="testclass">huhuhuhuhu</article>', $cArticle->toHtml());
    }
}
?>