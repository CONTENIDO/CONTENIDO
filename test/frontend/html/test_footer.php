<?PHP
class cHtmlFooterTest extends PHPUnit_Framework_TestCase {

    protected $_cFooter = null;

    protected function setUp() {
        $this->_cFooter = new cHTMLFooter();
    }

    public function testConstruct() {
        $footer = new cHTMLFooter('testContent', 'testClass', 'testId');
        $this->assertSame('<footer id="testId" class="testClass">testContent</footer>', $footer->toHTML());
        $this->assertSame('<footer id=""></footer>', $this->_cFooter->toHTML());
    }

}
?>