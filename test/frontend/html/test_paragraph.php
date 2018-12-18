<?PHP
/**
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlParagraphTest extends PHPUnit_Framework_TestCase {


    public function testConstruct() {
        $p = new cHTMLParagraph('testContent', 'testClass');
        $this->assertSame('<p id="" class="testClass">testContent</p>', $p->toHtml());
        $p = new cHTMLParagraph();
        $this->assertSame('<p id=""></p>', $p->toHtml());
        $p = new cHTMLParagraph('testContent', 'testClass');
        $p->setID('testId');
        $this->assertSame('<p id="testId" class="testClass">testContent</p>', $p->toHtml());
    }

}
?>