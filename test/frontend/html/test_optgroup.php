<?PHP
/**
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlOptGroupTest extends PHPUnit_Framework_TestCase {


    public function testConstruct() {
        $opt = new cHTMLOptgroup('testContent', 'testClass', 'testId');
        $this->assertSame('<optgroups id="testId" class="testClass">testContent</optgroups>', $opt->toHtml());
        $opt = new cHTMLOptgroup();
        $this->assertSame('<optgroups id=""></optgroups>', $opt->toHtml());
    }

}
?>