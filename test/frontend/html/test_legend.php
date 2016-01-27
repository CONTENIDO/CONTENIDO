<?PHP
/**
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlLegendTest extends PHPUnit_Framework_TestCase {

    protected $_cLegend = null;

    protected function setUp() {
        $this->_cLegend = new cHTMLLegend();
    }

    public function testConstruct() {
        $cLegend = new cHTMLLegend('testContent', 'testClass', 'testId');
        $this->assertSame('<legend id="testId" class="testClass">testContent</legend>', $cLegend->toHTML());
        $this->assertSame('<legend id=""></legend>', $this->_cLegend->toHTML());
    }

}
?>