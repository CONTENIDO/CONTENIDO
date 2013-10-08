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
class cHTMLSelectElementTest extends PHPUnit_Framework_TestCase {

    protected $_select = null;

    public function setUp() {
        $this->_select = new cHTMLSelectElement('testName');
    }

    public function testConstruct() {
        // $section = new cHTMLSection('testContent', 'testClass', 'testId');
        $this->assertSame('select', PHPUnit_Framework_Assert::readAttribute($this->_select, '_tag'));
        $this->assertSame(false, PHPUnit_Framework_Assert::readAttribute($this->_select, '_contentlessTag'));
        $this->assertSame(2, count($this->_select->getAttributes()));
        $this->assertSame('testName', $this->_select->getAttribute('name'));
        $this->assertSame(NULL, $this->_select->getAttribute('class'));
        $this->assertSame(NULL, $this->_select->getAttribute('disabled'));

        $this->_select = new cHTMLSelectElement('testName', 100);
        $this->assertSame('select', PHPUnit_Framework_Assert::readAttribute($this->_select, '_tag'));
        $this->assertSame(false, PHPUnit_Framework_Assert::readAttribute($this->_select, '_contentlessTag'));
        $this->assertSame(2, count($this->_select->getAttributes()));
        $this->assertSame('testName', $this->_select->getAttribute('name'));
        $this->assertSame(NULL, $this->_select->getAttribute('class'));
        $ret = PHPUnit_Framework_Assert::readAttribute($this->_select, '_styleDefinitions');
        $this->assertSame(100, $ret['width']);

        $this->_select = new cHTMLSelectElement('testName', 100, 'testId');
        $this->assertSame('select', PHPUnit_Framework_Assert::readAttribute($this->_select, '_tag'));
        $this->assertSame(false, PHPUnit_Framework_Assert::readAttribute($this->_select, '_contentlessTag'));
        $this->assertSame(2, count($this->_select->getAttributes()));
        $this->assertSame('testName', $this->_select->getAttribute('name'));
        $this->assertSame(NULL, $this->_select->getAttribute('class'));
        $ret = PHPUnit_Framework_Assert::readAttribute($this->_select, '_styleDefinitions');
        $this->assertSame(100, $ret['width']);
        $this->assertSame('testId', $this->_select->getAttribute('id'));

        $this->_select = new cHTMLSelectElement('testName', 100, 'testId', true);
        $this->assertSame('select', PHPUnit_Framework_Assert::readAttribute($this->_select, '_tag'));
        $this->assertSame(false, PHPUnit_Framework_Assert::readAttribute($this->_select, '_contentlessTag'));
        $this->assertSame(3, count($this->_select->getAttributes()));
        $this->assertSame('testName', $this->_select->getAttribute('name'));
        $this->assertSame(NULL, $this->_select->getAttribute('class'));
        $ret = PHPUnit_Framework_Assert::readAttribute($this->_select, '_styleDefinitions');
        $this->assertSame(100, $ret['width']);
        $this->assertSame('testId', $this->_select->getAttribute('id'));
        $this->assertSame('disabled', $this->_select->getAttribute('disabled'));

        $this->_select = new cHTMLSelectElement('testName', 100, 'testId', false, null, '', 'testClass');
        $this->assertSame('select', PHPUnit_Framework_Assert::readAttribute($this->_select, '_tag'));
        $this->assertSame(false, PHPUnit_Framework_Assert::readAttribute($this->_select, '_contentlessTag'));
        $this->assertSame(3, count($this->_select->getAttributes()));
        $this->assertSame('testName', $this->_select->getAttribute('name'));
        $ret = PHPUnit_Framework_Assert::readAttribute($this->_select, '_styleDefinitions');
        $this->assertSame(100, $ret['width']);
        $this->assertSame('testClass', $this->_select->getAttribute('class'));
    }

    public function testSetSize() {
        $this->assertSame(NULL, $this->_select->getAttribute('size'));
        $this->_select->setSize(100);
        $this->assertSame(100, $this->_select->getAttribute('size'));
    }

    public function testToHtml() {
        $this->assertSame($this->_select->toHtml(), $this->_select->toHTML());
    }

    public function testAutoFill() {
        $this->_select = new cHTMLSelectElement('testName', 100, 'testId');
        $stuff = array(
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3'
        );
        $this->_select->autoFill($stuff);
        $this->assertSame(3, count(PHPUnit_Framework_Assert::readAttribute($this->_select, '_options')));
        $ret = PHPUnit_Framework_Assert::readAttribute($this->_select, '_options');
        $this->assertSame('value1', PHPUnit_Framework_Assert::readAttribute($ret['key1'], '_title'));
        $this->assertSame('value2', PHPUnit_Framework_Assert::readAttribute($ret['key2'], '_title'));
        $this->assertSame('value3', PHPUnit_Framework_Assert::readAttribute($ret['key3'], '_title'));
    }

//     public function testAddOptionElement() {
//         $this->_select->addOptionElement(new cHTMLOptionElement('testTitle', 'testValue'));
//         $ret = (object) PHPUnit_Framework_Assert::readAttribute($this->_select, '_options');
//         $this->assertSame('testTitle', PHPUnit_Framework_Assert::readAttribute($ret[0], '_title'));
//         $ar = PHPUnit_Framework_Assert::readAttribute($ret[0], '_attributes');
//         $this->assertSame('testValue', $ar['value']);
//     }

//     public function testAppendOptionElement() {
//         $this->_select->appendOptionElement(new cHTMLOptionElement('testTitle', 'testValue'));
//         $ret = (object) PHPUnit_Framework_Assert::readAttribute($this->_select, '_options');
//         $this->assertSame('testTitle', PHPUnit_Framework_Assert::readAttribute($ret[0], '_title'));
//         $ar = PHPUnit_Framework_Assert::readAttribute($ret[0], '_attributes');
//         $this->assertSame('testValue', $ar['value']);
//     }

//     public function testSetMultiSelect() {
//         $this->assertSame(NULL, $this->_select->getAttribute('multiple'));
//         $this->_select->setMultiselect();
//         $this->assertSame('multiple', $this->_select->getAttribute('multiple'));
//     }

    /**
     *
     * @todo
     *
     */
    public function testSetDefault() {
    }

    /**
     *
     * @todo
     *
     */
    public function testGetDefault() {
    }

}
?>
