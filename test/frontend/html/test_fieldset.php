<?PHP
/**
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlFieldSetTest extends PHPUnit_Framework_TestCase {

    protected $_cFieldSet = null;

    protected function setUp() {
        $this->_cFieldSet = new cHTMLFieldset();
    }

    public function testConstruct() {
        $fieldset = new cHTMLFieldset('testContent', 'testClass', 'testId');
        $this->assertSame('<fieldset id="testId" class="testClass">testContent</fieldset>', $fieldset->toHtml());
        $this->assertSame('<fieldset id=""></fieldset>', $this->_cFieldSet->toHtml());
    }

}
?>