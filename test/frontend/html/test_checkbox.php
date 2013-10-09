<?PHP
/**
 *
 * @version SVN Revision $Rev:$
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlCheckBoxTest extends PHPUnit_Framework_TestCase {

    protected $_cCheckBox = null;

    protected function setUp() {
        $this->_cCheckBox = new cHTMLCheckbox('testName', 'testValue');
    }

    /**
     * @todo This test has not been implemented yet.
     */
    public function testConstruct() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    public function testSetChecked() {
        $this->_cCheckBox->setChecked(true);
        $this->assertSame('checked', $this->_cCheckBox->getAttribute('checked'));

        $this->_cCheckBox->setChecked(false);
        $this->assertSame(NULL, $this->_cCheckBox->getAttribute('checked'));
    }

    public function testSetLabel() {
        $this->_cCheckBox->setLabelText('testLabel');
        $this->assertSame('testLabel', PHPUnit_Framework_Assert::readAttribute($this->_cCheckBox, '_labelText'));

        $this->_cCheckBox->setLabelText('');
        $this->assertSame('', PHPUnit_Framework_Assert::readAttribute($this->_cCheckBox, '_labelText'));
    }

    public function testToHtml() {
        $this->assertSame('<input id="m52" name="testName" type="checkbox" value="testValue" />', $this->_cCheckBox->toHtml(false));
    }

}
?>