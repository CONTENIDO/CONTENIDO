<?PHP
/**
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlSpanTest extends PHPUnit_Framework_TestCase {

    public function testConstruct() {
        $span = new cHTMLSpan('testContent', 'testClass');
        $this->assertSame('span', PHPUnit_Framework_Assert::readAttribute($span, '_tag'));
        $this->assertSame(NULL, $span->getAttribute('_content'));
        $this->assertSame('testClass', $span->getAttribute('class'));

        $this->assertSame('<span id="" class="testClass">testContent</span>', $span->toHtml());
    }

}
?>