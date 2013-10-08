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
class cHtmlTableHeaderTest extends PHPUnit_Framework_TestCase {

    protected $_tableHeader;

    public function setUp() {
        $this->_tableHeader = new cHTMLTableHeader();
    }

    public function testConstructor() {
        $this->assertSame('thead', PHPUnit_Framework_Assert::readAttribute($this->_tableHeader, '_tag'));
    }

}
?>

