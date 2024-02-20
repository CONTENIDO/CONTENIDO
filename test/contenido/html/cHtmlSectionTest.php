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
class cHtmlSectionTest extends cTestingTestCase
{
    public function testConstruct()
    {
        $section = new cHTMLSection('testContent', 'testClass', 'testId');
        $this->assertSame('section', $this->_readAttribute($section, '_tag'));
        $this->assertSame(null, $section->getAttribute('_content'));
        $this->assertSame('testClass', $section->getAttribute('class'));
        $this->assertSame('testId', $section->getAttribute('id'));

        $this->assertSame('<section id="testId" class="testClass">testContent</section>', $section->toHtml());
    }
}
