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
class cHtmlTimeTest extends PHPUnit_Framework_TestCase {

    public function testConstruct() {
        $time = new cHTMLTime('testContent', 'testClass', 'testId', 'testDateTime');
        $this->assertSame(3, count($time->getAttributes()));
        $this->assertSame('time', PHPUnit_Framework_Assert::readAttribute($time, '_tag'));
        $this->assertSame('testClass', $time->getAttribute('class'));
        $this->assertSame('testId', $time->getAttribute('id'));
        $this->assertSame('testDateTime', $time->getAttribute('datetime'));

        $this->assertSame('<time id="testId" class="testClass" datetime="testDateTime">testContent</time>', $time->toHTML());
    }

}
?>