<?PHP

/**
 *
 * @author    claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license   https://www.contenido.org/license/LIZENZ.txt
 * @link      https://www.4fb.de
 * @link      https://www.contenido.org
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
