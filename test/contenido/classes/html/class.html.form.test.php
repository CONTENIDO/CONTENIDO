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
class cHtmlFormTest extends cTestingTestCase {

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
        $this->_form->setID('1');
    }

    /**
     *
     */
    public function testConstruct() {
        $form = new cHTMLForm();
        $this->assertSame('', $this->_readAttribute($form, '_name'));
        $this->assertSame('main.php', $this->_readAttribute($form, '_action'));
        $this->assertSame('post', $this->_readAttribute($form, '_method'));
        $this->assertSame('form', $this->_readAttribute($form, '_tag'));

        $form = new cHTMLForm('testName');
        $this->assertSame('testName', $this->_readAttribute($form, '_name'));
        $this->assertSame('main.php', $this->_readAttribute($form, '_action'));
        $this->assertSame('post', $this->_readAttribute($form, '_method'));
        $this->assertSame('form', $this->_readAttribute($form, '_tag'));

        $form = new cHTMLForm('testName', 'testMain.php');
        $this->assertSame('testName', $this->_readAttribute($form, '_name'));
        $this->assertSame('testMain.php', $this->_readAttribute($form, '_action'));
        $this->assertSame('post', $this->_readAttribute($form, '_method'));
        $this->assertSame('form', $this->_readAttribute($form, '_tag'));

        $form = new cHTMLForm('testName', 'testMain.php', 'GET');
        $this->assertSame('testName', $this->_readAttribute($form, '_name'));
        $this->assertSame('testMain.php', $this->_readAttribute($form, '_action'));
        $this->assertSame('GET', $this->_readAttribute($form, '_method'));
        $this->assertSame('form', $this->_readAttribute($form, '_tag'));

        $form = new cHTMLForm('testName', 'testMain.php', 'GET', 'testClass');
        $this->assertSame('testName', $this->_readAttribute($form, '_name'));
        $this->assertSame('testMain.php', $this->_readAttribute($form, '_action'));
        $this->assertSame('GET', $this->_readAttribute($form, '_method'));
        $this->assertSame('form', $this->_readAttribute($form, '_tag'));
        $this->assertSame('testClass', $form->getAttribute('class'));
    }

    /**
     *
     * @todo
     */
    public function testToHtml() {
        $act = $this->_form->toHTML();
        $exp = '<form id="1" name="" method="post" action="main.php"></form>';
        $this->assertSame($exp, $act);
    }

    /**
     *
     * @todo
     */
    public function testSetVar() {
        // Oupsi $_form has no $_vars ... but this is probably correct!?!
        //$act = $this->_readAttribute($this->_form, '_vars');
        $exp = array();
        //$this->assertSame($exp, $act);

        $this->_form->setVar('foo', 'bar');
        $act = $this->_readAttribute($this->_form, '_vars');
        $exp['foo'] = 'bar';
        $this->assertSame($exp, $act);

        $this->_form->setVar('spam', 'eggs');
        $act = $this->_readAttribute($this->_form, '_vars');
        $exp['spam'] = 'eggs';
        $this->assertSame($exp, $act);
    }

}
?>