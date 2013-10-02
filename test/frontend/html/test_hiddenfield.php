<?PHP
class cHtmlHiddenFieldTest extends PHPUnit_Framework_TestCase {

    protected $_cHiddenField = null;

    protected function setUp() {
        $this->_cHiddenField = new cHTMLHiddenField('testName', 'testValue', 'testId');
    }

    public function testConstruct() {
        $this->assertSame('<input id="testId" name="testName" type="hidden" value="testValue" />', $this->_cHiddenField->toHTML());
    }

    public function testSetValue() {
        $this->_cHiddenField->setValue('testValue2');
        $this->assertSame('<input id="testId" name="testName" type="hidden" value="testValue2" />', $this->_cHiddenField->toHTML());
        $this->_cHiddenField->setValue('');
        $this->assertSame('<input id="testId" name="testName" type="hidden" value="" />', $this->_cHiddenField->toHTML());
    }

}
?>