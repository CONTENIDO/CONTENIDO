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
class cHtmlNavTest extends PHPUnit_Framework_TestCase {


    public function testConstruct() {
        $nav = new cHTMLNav('testContent', 'testClass', 'testId');
        $this->assertSame('<nav id="testId" class="testClass">testContent</nav>', $nav->toHTML());
        $nav = new cHTMLNav();
        $this->assertSame('<nav id=""></nav>', $nav->toHTML());
    }

}
?>