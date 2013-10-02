<?PHP
class cHtmlFieldSetTest extends PHPUnit_Framework_TestCase {

    protected $_cFieldSet = null;

    protected function setUp() {
        $this->_cFieldSet = new cHTMLFieldset();
    }

    public function testConstruct() {
        $fieldset = new cHTMLFieldset('testContent', 'testClass', 'testId');
        $this->assertSame('<fieldset id="testId" class="testClass">testContent</fieldset>', $fieldset->toHTML());
        $this->assertSame('<fieldset id=""></fieldset>', $this->_cFieldSet->toHTML());
    }

}
?>