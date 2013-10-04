<?PHP
/**
 *
 * @version SVN Revision $Rev:$
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlAsideTest extends PHPUnit_Framework_TestCase {

    public function testArticle() {
        $cAside = new cHTMLAside('huhu', 'testclass', 'testid');
        $this->assertSame('<aside id="testid" class="testclass">huhu</aside>', $cAside->toHTML());
    }
}
?>