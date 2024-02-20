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
class cHtmlSpanTest extends cTestingTestCase
{
    public function testConstruct()
    {
        $span = new cHTMLSpan('testContent', 'testClass');
        $this->assertSame('span', $this->_readAttribute($span, '_tag'));
        $this->assertSame(null, $span->getAttribute('_content'));
        $this->assertSame('testClass', $span->getAttribute('class'));

        $this->assertSame('<span class="testClass">testContent</span>', $span->toHtml());
    }
}
