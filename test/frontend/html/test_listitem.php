<?PHP
class cHtmlListItemTest extends PHPUnit_Framework_TestCase {

    protected $_cListItem = null;

    protected function setUp() {
        $this->_cList = new cHTMLListItem();
    }

    public function testConstruct() {
        $cListItem = new cHTMLListItem('testId','testClass');

        $this->assertSame('<li id="testId" class="testClass"></li>', $cListItem->toHTML());
    }

}
?>