<?PHP
class cHtmlLabelTest extends PHPUnit_Framework_TestCase {

    protected $_cLabel = null;

    protected function setUp() {
        $this->_cLabel = new cHTMLLabel('testText', 'testLabel');
    }

    public function testConstruct() {
        $this->assertSame('<label id="" for="testLabel">testText</label>', $this->_cLabel->toHTML());
        $this->_cLabel = new cHTMLLabel('testText', 'testLabel','testClass');
        $this->assertSame('<label id="" class="testClass" for="testLabel">testText</label>', $this->_cLabel->toHTML());
    }
    public function testToHtml(){
        $this->assertSame($this->_cLabel->toHtml(), $this->_cLabel->toHTML());
    }

}
?>