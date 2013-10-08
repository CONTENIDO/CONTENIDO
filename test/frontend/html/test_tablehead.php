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
class cHtmlTableHeadTest extends PHPUnit_Framework_TestCase {

    protected $_tableHead;

    public function setUp() {
        $this->_tableHead = new cHTMLTableHead();
    }

    public function testConstructor() {
        $this->assertSame('th', PHPUnit_Framework_Assert::readAttribute($this->_tableHead, '_tag'));
    }

}
?>

