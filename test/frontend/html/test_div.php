<?PHP
class cHtmlDivTest extends PHPUnit_Framework_TestCase {

    protected $_cDiv = null;

    protected function setUp() {
        $this->_cDiv = new cHTMLDiv();
    }

    public function testConstruct() {
        $div = new cHTMLDiv('testContent', 'testClass', 'testId');
        $this->assertSame('<div id="testId" class="testClass">testContent</div>', $div->toHTML());
        $this->assertSame('<div id=""></div>', $this->_cDiv->toHTML());
    }

}
?>