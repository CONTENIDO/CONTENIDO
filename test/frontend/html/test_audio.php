<?PHP
/**
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlAudioTest extends PHPUnit_Framework_TestCase {

    protected $_cAudio = null;

    protected function setUp() {
        $this->_cAudio = new cHTMLAudio('testcontent', 'testClass', 'testId', 'testSrc');
    }

    public function testConstruct() {
        $cAudio = new cHTMLAudio();
        $this->assertSame('<audio id="" src=""></audio>', $cAudio->toHtml());
        $this->assertSame('<audio id="testId" class="testClass" src="testSrc">testcontent</audio>', $this->_cAudio->toHtml());
    }

    public function testSetSrc() {
        $this->_cAudio->setSrc('testSrc1');
        $this->assertSame('<audio id="testId" class="testClass" src="testSrc1">testcontent</audio>', $this->_cAudio->toHtml());
    }

    public function testSetControls() {
        $this->assertSame('<audio id="testId" class="testClass" src="testSrc">testcontent</audio>', $this->_cAudio->toHtml());
        $this->_cAudio->setControls(true);
        $this->assertSame('<audio id="testId" class="testClass" src="testSrc" controls="controls">testcontent</audio>', $this->_cAudio->toHtml());

        $this->_cAudio->setControls(false);
        $this->assertSame('<audio id="testId" class="testClass" src="testSrc">testcontent</audio>', $this->_cAudio->toHtml());
    }

    public function testSetAutoplay() {
        $this->assertSame('<audio id="testId" class="testClass" src="testSrc">testcontent</audio>', $this->_cAudio->toHtml());
        $this->_cAudio->setAutoplay(true);
        $this->assertSame('<audio id="testId" class="testClass" src="testSrc" autoplay="autoplay">testcontent</audio>', $this->_cAudio->toHtml());

         $this->_cAudio->setAutoplay(false);
         $this->assertSame('<audio id="testId" class="testClass" src="testSrc">testcontent</audio>', $this->_cAudio->toHtml());
    }

}
?>