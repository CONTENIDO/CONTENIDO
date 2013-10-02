<?PHP
class cHtmlCanvasTest extends PHPUnit_Framework_TestCase {

    protected $_cCanvas = null;

    protected function setUp() {
        $this->_cCanvas = new cHTMLCanvas();
    }

    public function testConstruct() {
        $canvas = new cHTMLCanvas();
        $this->assertSame('<canvas id=""></canvas>', $canvas->toHtml());
        $canvas = new cHTMLCanvas('testContent', 'testClass', 'testId');
        $this->assertSame('<canvas id="testId" class="testClass">testContent</canvas>', $canvas->toHtml());
    }

    public function testSetHeight() {
        $this->_cCanvas->setHeight(200);
        $this->assertSame(200, $this->_cCanvas->getAttribute('height'));
        $this->assertSame('<canvas id="" height="200"></canvas>', $this->_cCanvas->toHTML());
        $this->_cCanvas->setHeight('');
        $this->assertSame('', $this->_cCanvas->getAttribute('height'));
        $this->assertSame('<canvas id="" height=""></canvas>', $this->_cCanvas->toHTML());
    }

    public function testSetWidth() {
        $this->_cCanvas->setWidth(200);
        $this->assertSame(200, $this->_cCanvas->getAttribute('width'));
        $this->assertSame('<canvas id="" width="200"></canvas>', $this->_cCanvas->toHTML());
        $this->_cCanvas->setWidth('');
        $this->assertSame('', $this->_cCanvas->getAttribute('width'));
        $this->assertSame('<canvas id="" width=""></canvas>', $this->_cCanvas->toHTML());
    }

}
?>