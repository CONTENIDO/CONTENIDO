<?PHP

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Assert;

/**
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class cHtmlTableHeaderTest extends TestCase {

    protected $_tableHeader;

    protected function setUp(): void {
        $this->_tableHeader = new cHTMLTableHeader();
    }

    public function testConstructor() {
        $this->assertSame('thead', Assert::readAttribute($this->_tableHeader, '_tag'));
    }

}
?>

