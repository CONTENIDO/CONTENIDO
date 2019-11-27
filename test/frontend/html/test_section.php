<?PHP

/**
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlSectionTest extends cTestingTestCase {

    public function testConstruct() {
        $section = new cHTMLSection('testContent', 'testClass', 'testId');
        $this->assertSame('section', $this->_readAttribute($section, '_tag'));
        $this->assertSame(NULL, $section->getAttribute('_content'));
        $this->assertSame('testClass', $section->getAttribute('class'));
        $this->assertSame('testId', $section->getAttribute('id'));

        $this->assertSame('<section id="testId" class="testClass">testContent</section>', $section->toHtml());
    }

}
?>