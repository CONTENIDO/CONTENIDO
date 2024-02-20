<?PHP

/**
 * @package    Testing
 * @subpackage GUI_HTML
 * @author     claus.schunk@4fb.de
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */
class cHtmlUploadTest extends cTestingTestCase
{
    public function testConstruct()
    {
        $pwBox = new cHTMLUpload('testName');
        $this->assertSame(3, count($pwBox->getAttributes()));
        $this->assertSame('testName', $pwBox->getAttribute('name'));
        $this->assertSame(null, $pwBox->getAttribute('value'));
        $this->assertSame('file', $pwBox->getAttribute('type'));

        $pwBox = new cHTMLUpload('testName', 100);
        $this->assertSame(3, count($pwBox->getAttributes()));
        $this->assertSame('testName', $pwBox->getAttribute('name'));
        $this->assertSame(null, $pwBox->getAttribute('value'));
        $this->assertSame('file', $pwBox->getAttribute('type'));

        $pwBox = new cHTMLUpload('testName', 100, 200);
        $this->assertSame(4, count($pwBox->getAttributes()));
        $this->assertSame('testName', $pwBox->getAttribute('name'));
        $this->assertSame(null, $pwBox->getAttribute('value'));
        $this->assertSame('file', $pwBox->getAttribute('type'));
        $this->assertSame(100, $pwBox->getAttribute('size'));

        $pwBox = new cHTMLUpload('testName', 100, 200);
        $this->assertSame(4, count($pwBox->getAttributes()));
        $this->assertSame('testName', $pwBox->getAttribute('name'));
        $this->assertSame(null, $pwBox->getAttribute('value'));
        $this->assertSame('file', $pwBox->getAttribute('type'));
        $this->assertSame(100, $pwBox->getAttribute('size'));
        $this->assertSame(200, $pwBox->getAttribute('maxlength'));

        $pwBox = new cHTMLUpload('testName', 100, 200, 'testId');
        $this->assertSame(5, count($pwBox->getAttributes()));
        $this->assertSame('testName', $pwBox->getAttribute('name'));
        $this->assertSame(null, $pwBox->getAttribute('value'));
        $this->assertSame('file', $pwBox->getAttribute('type'));
        $this->assertSame(100, $pwBox->getAttribute('size'));
        $this->assertSame(200, $pwBox->getAttribute('maxlength'));
        $this->assertSame('testId', $pwBox->getAttribute('id'));

        $pwBox = new cHTMLUpload('testName', 100, 200, 'testId', false);
        $this->assertSame(5, count($pwBox->getAttributes()));
        $this->assertSame('testName', $pwBox->getAttribute('name'));
        $this->assertSame(null, $pwBox->getAttribute('value'));
        $this->assertSame('file', $pwBox->getAttribute('type'));
        $this->assertSame(100, $pwBox->getAttribute('size'));
        $this->assertSame(200, $pwBox->getAttribute('maxlength'));
        $this->assertSame('testId', $pwBox->getAttribute('id'));
        $this->assertSame(null, $pwBox->getAttribute('disabled'));

        $pwBox = new cHTMLUpload('testName', 100, 200, 'testId', true);
        $this->assertSame(6, count($pwBox->getAttributes()));
        $this->assertSame('testName', $pwBox->getAttribute('name'));
        $this->assertSame(null, $pwBox->getAttribute('value'));
        $this->assertSame('file', $pwBox->getAttribute('type'));
        $this->assertSame(100, $pwBox->getAttribute('size'));
        $this->assertSame(200, $pwBox->getAttribute('maxlength'));
        $this->assertSame('testId', $pwBox->getAttribute('id'));
        $this->assertSame('disabled', $pwBox->getAttribute('disabled'));

        $pwBox = new cHTMLUpload('testName', 100, 200, 'testId', false, null, '', 'testClass');
        $this->assertSame(6, count($pwBox->getAttributes()));
        $this->assertSame('testName', $pwBox->getAttribute('name'));
        $this->assertSame(null, $pwBox->getAttribute('value'));
        $this->assertSame('file', $pwBox->getAttribute('type'));
        $this->assertSame(100, $pwBox->getAttribute('size'));
        $this->assertSame(200, $pwBox->getAttribute('maxlength'));
        $this->assertSame('testId', $pwBox->getAttribute('id'));
        $this->assertSame(null, $pwBox->getAttribute('disabled'));
        $this->assertSame('testClass', $pwBox->getAttribute('class'));
    }

    public function testSetWidth()
    {
        $pwBox = new cHTMLUpload('testName', 100);
        $this->assertSame(3, count($pwBox->getAttributes()));
        $this->assertSame('testName', $pwBox->getAttribute('name'));
        $this->assertSame(null, $pwBox->getAttribute('value'));
        $this->assertSame('file', $pwBox->getAttribute('type'));
        $this->assertSame(100, $pwBox->getAttribute('size'));

        $pwBox->setWidth(-1);
        $this->assertSame(20, $pwBox->getAttribute('size'));
        $pwBox->setWidth(0);
        $this->assertSame(20, $pwBox->getAttribute('size'));
        $pwBox->setWidth(1);
        $this->assertSame(1, $pwBox->getAttribute('size'));
    }

    public function testSetMaxLength()
    {
        $pwBox = new cHTMLUpload('testName', 100, 200);
        $this->assertSame(4, count($pwBox->getAttributes()));
        $this->assertSame('testName', $pwBox->getAttribute('name'));
        $this->assertSame(null, $pwBox->getAttribute('value'));
        $this->assertSame('file', $pwBox->getAttribute('type'));
        $this->assertSame(100, $pwBox->getAttribute('size'));
        $this->assertSame(200, $pwBox->getAttribute('maxlength'));

        $pwBox->setMaxLength(-1);
        $this->assertSame(null, $pwBox->getAttribute('maxlength'));
        $pwBox->setMaxLength(0);
        $this->assertSame(null, $pwBox->getAttribute('maxlength'));
        $pwBox->setMaxLength(1);
        $this->assertSame(1, $pwBox->getAttribute('maxlength'));
    }
}
