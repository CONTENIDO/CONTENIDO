<?PHP
/**
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlHiddenFieldTest extends PHPUnit_Framework_TestCase {
    /**
     * @var cHTMLHiddenField
     */
    protected $_cHiddenField = null;

    protected function setUp() {
        $this->_cHiddenField = new cHTMLHiddenField('testName', 'testValue', 'testId');
    }

    public function testConstruct() {
        $this->assertSame('<input id="testId" name="testName" type="hidden" value="testValue" />', $this->_cHiddenField->toHtml());
    }

    public function testSetValue() {
        $this->_cHiddenField->setValue('testValue2');
        $this->assertSame('<input id="testId" name="testName" type="hidden" value="testValue2" />', $this->_cHiddenField->toHtml());
        $this->_cHiddenField->setValue('');
        $this->assertSame('<input id="testId" name="testName" type="hidden" value="" />', $this->_cHiddenField->toHtml());
    }

}
?>