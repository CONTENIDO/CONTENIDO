<?PHP
/**
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlLabelTest extends PHPUnit_Framework_TestCase {

    protected $_cLabel = null;

    protected function setUp() {
        $this->_cLabel = new cHTMLLabel('testText', 'testLabel');
    }

    public function testConstruct() {
        $this->assertSame('<label id="" for="testLabel">testText</label>', $this->_cLabel->toHtml());
        $this->_cLabel = new cHTMLLabel('testText', 'testLabel', 'testClass');
        $this->assertSame('<label id="" class="testClass" for="testLabel">testText</label>', $this->_cLabel->toHtml());
    }

    public function testToHtml() {
        $this->assertSame($this->_cLabel->toHtml(), $this->_cLabel->toHtml());
    }

}
?>