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
     *
     */
    public function testToHtml() {
    }

    /**
     *
     * @todo
     *
     */
    public function testSetVar() {
    }

}
?>