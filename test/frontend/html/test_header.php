<?PHP
/**
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlHeaderTest extends PHPUnit_Framework_TestCase {

    protected $_cHeader = null;

    protected function setUp() {
        $this->_cHeader = new cHTMLHeader();
    }

    public function testConstruct() {
        $header = new cHTMLHeader('testContent', 'testClass', 'testId');
        $this->assertSame('<header id="testId" class="testClass">testContent</header>', $header->toHtml());
        $this->assertSame('<header id=""></header>', $this->_cHeader->toHtml());
    }

}
?>