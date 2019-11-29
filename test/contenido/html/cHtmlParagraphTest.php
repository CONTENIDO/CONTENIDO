<?PHP

/**
 *
 * @author    claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license   http://www.contenido.org/license/LIZENZ.txt
 * @link      http://www.4fb.de
 * @link      http://www.contenido.org
 */
class cHtmlParagraphTest extends cTestingTestCase
{
    public function testConstruct()
    {
        $p = new cHTMLParagraph('testContent', 'testClass');
        $this->assertSame('<p class="testClass">testContent</p>', $p->toHtml());
        $p = new cHTMLParagraph();
        $this->assertSame('<p></p>', $p->toHtml());
        $p = new cHTMLParagraph('testContent', 'testClass');
        $p->setID('testId');
        $this->assertSame('<p class="testClass" id="testId">testContent</p>', $p->toHtml());
    }
}
