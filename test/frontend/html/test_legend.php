<?PHP
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