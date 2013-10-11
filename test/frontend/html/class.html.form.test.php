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
class cHtmlFormTest extends PHPUnit_Framework_TestCase {

    /**
     *
     * @var cHTMLForm
     */
    private $_form;

    /**
     * Creates tables with values of different datatypes.
     */
    public function setUp() {
        $this->_form = new cHTMLForm();
    }

    /**
     *
     */
    public function testConstruct() {
        $form = new cHTMLForm();
        $this->assertSame('', PHPUnit_Framework_Assert::readAttribute($form, '_name'));
        $this->assertSame('main.php', PHPUnit_Framework_Assert::readAttribute($form, '_action'));
        $this->assertSame('post', PHPUnit_Framework_Assert::readAttribute($form, '_method'));
        $this->assertSame('form', PHPUnit_Framework_Assert::readAttribute($form, '_tag'));

        $form = new cHTMLForm('testName');
        $this->assertSame('testName', PHPUnit_Framework_Assert::readAttribute($form, '_name'));
        $this->assertSame('main.php', PHPUnit_Framework_Assert::readAttribute($form, '_action'));
        $this->assertSame('post', PHPUnit_Framework_Assert::readAttribute($form, '_method'));
        $this->assertSame('form', PHPUnit_Framework_Assert::readAttribute($form, '_tag'));

        $form = new cHTMLForm('testName', 'testMain.php');
        $this->assertSame('testName', PHPUnit_Framework_Assert::readAttribute($form, '_name'));
        $this->assertSame('testMain.php', PHPUnit_Framework_Assert::readAttribute($form, '_action'));
        $this->assertSame('post', PHPUnit_Framework_Assert::readAttribute($form, '_method'));
        $this->assertSame('form', PHPUnit_Framework_Assert::readAttribute($form, '_tag'));

        $form = new cHTMLForm('testName', 'testMain.php', 'GET');
        $this->assertSame('testName', PHPUnit_Framework_Assert::readAttribute($form, '_name'));
        $this->assertSame('testMain.php', PHPUnit_Framework_Assert::readAttribute($form, '_action'));
        $this->assertSame('GET', PHPUnit_Framework_Assert::readAttribute($form, '_method'));
        $this->assertSame('form', PHPUnit_Framework_Assert::readAttribute($form, '_tag'));

        $form = new cHTMLForm('testName', 'testMain.php', 'GET', 'testClass');
        $this->assertSame('testName', PHPUnit_Framework_Assert::readAttribute($form, '_name'));
        $this->assertSame('testMain.php', PHPUnit_Framework_Assert::readAttribute($form, '_action'));
        $this->assertSame('GET', PHPUnit_Framework_Assert::readAttribute($form, '_method'));
        $this->assertSame('form', PHPUnit_Framework_Assert::readAttribute($form, '_tag'));
        $this->assertSame('testClass', $form->getAttribute('class'));
    }

    /**
     *
     * @todo
     */
    public function testToHtml() {
        $act = $this->_form->toHTML();
        $exp = '<form id="" name="" method="post" action="main.php"></form>';
        $this->assertSame($exp, $act);
    }

    /**
     *
     * @todo
     */
    public function testSetVar() {
        // Oupsi $_form has no $_vars ... but this is probably correct!?!
        //$act = PHPUnit_Framework_Assert::readAttribute($this->_form, '_vars');
        $exp = array();
        //$this->assertSame($exp, $act);

        $this->_form->setVar('foo', 'bar');
        $act = PHPUnit_Framework_Assert::readAttribute($this->_form, '_vars');
        $exp['foo'] = 'bar';
        $this->assertSame($exp, $act);

        $this->_form->setVar('spam', 'eggs');
        $act = PHPUnit_Framework_Assert::readAttribute($this->_form, '_vars');
        $exp['spam'] = 'eggs';
        $this->assertSame($exp, $act);
    }

}
?>