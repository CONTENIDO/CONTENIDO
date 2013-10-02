<?PHP
class cHtmlHeaderHgroupTest extends PHPUnit_Framework_TestCase {

    protected $_cHeaderHgroup = null;

    protected function setUp() {
        $this->_cHeaderHgroup = new cHTMLHgroup();
    }

    public function testConstruct() {
        $hgroup = new cHTMLHgroup('testContent', 'testClass', 'testId');
        $this->assertSame('<hgroup id="testId" class="testClass">testContent</hgroup>', $hgroup->toHTML());
        $this->assertSame('<hgroup id=""></hgroup>', $this->_cHeaderHgroup->toHTML());
    }

}
?>