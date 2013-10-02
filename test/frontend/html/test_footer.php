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
class cHtmlFooterTest extends PHPUnit_Framework_TestCase {

    protected $_cFooter = null;

    protected function setUp() {
        $this->_cFooter = new cHTMLFooter();
    }

    public function testConstruct() {
        $footer = new cHTMLFooter('testContent', 'testClass', 'testId');
        $this->assertSame('<footer id="testId" class="testClass">testContent</footer>', $footer->toHTML());
        $this->assertSame('<footer id=""></footer>', $this->_cFooter->toHTML());
    }

}
?>