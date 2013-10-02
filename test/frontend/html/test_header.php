<?PHP
class cHtmlHeaderTest extends PHPUnit_Framework_TestCase {

    protected $_cHeader = null;

    protected function setUp() {
        $this->_cHeader = new cHTMLHeader();
    }

    public function testConstruct() {
        $header = new cHTMLHeader('testContent', 'testClass', 'testId');
        $this->assertSame('<header id="testId" class="testClass">testContent</header>', $header->toHTML());
        $this->assertSame('<header id=""></header>', $this->_cHeader->toHTML());
    }

}
?>