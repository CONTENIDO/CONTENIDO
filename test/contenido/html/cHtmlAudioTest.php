<?PHP

/**
 * @package    Testing
 * @subpackage GUI_HTML
 * @author     claus.schunk@4fb.de
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */
class cHtmlAudioTest extends cTestingTestCase
{
    /**
     * @var cHTMLAudio
     */
    protected $_cAudio;

    protected function setUp(): void
    {
        $this->_cAudio = new cHTMLAudio('testcontent', 'testClass', 'testId', 'testSrc');
    }

    public function testConstruct()
    {
        $cAudio = new cHTMLAudio('', '', 'm25');
        $this->assertSame('<audio id="m25" src=""></audio>', $cAudio->toHtml());
        $this->assertSame(
            '<audio id="testId" class="testClass" src="testSrc">testcontent</audio>',
            $this->_cAudio->toHtml()
        );
    }

    public function testSetSrc()
    {
        $this->_cAudio->setSrc('testSrc1');
        $this->assertSame(
            '<audio id="testId" class="testClass" src="testSrc1">testcontent</audio>',
            $this->_cAudio->toHtml()
        );
    }

    public function testSetControls()
    {
        $this->assertSame(
            '<audio id="testId" class="testClass" src="testSrc">testcontent</audio>',
            $this->_cAudio->toHtml()
        );
        $this->_cAudio->setControls(true);
        $this->assertSame(
            '<audio id="testId" class="testClass" src="testSrc" controls="controls">testcontent</audio>',
            $this->_cAudio->toHtml()
        );

        $this->_cAudio->setControls(false);
        $this->assertSame(
            '<audio id="testId" class="testClass" src="testSrc">testcontent</audio>',
            $this->_cAudio->toHtml()
        );
    }

    public function testSetAutoplay()
    {
        $this->assertSame(
            '<audio id="testId" class="testClass" src="testSrc">testcontent</audio>',
            $this->_cAudio->toHtml()
        );
        $this->_cAudio->setAutoplay(true);
        $this->assertSame(
            '<audio id="testId" class="testClass" src="testSrc" autoplay="autoplay">testcontent</audio>',
            $this->_cAudio->toHtml()
        );

        $this->_cAudio->setAutoplay(false);
        $this->assertSame(
            '<audio id="testId" class="testClass" src="testSrc">testcontent</audio>',
            $this->_cAudio->toHtml()
        );
    }
}
