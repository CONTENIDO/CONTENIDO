<?PHP
class cHtmlListTest extends PHPUnit_Framework_TestCase {

    protected $_cList = null;

    protected function setUp() {
        $this->_cList = new cHTMLList();
    }

    public function testConstruct() {
        $cList = new cHTMLList('ul', 'testId', 'testclass', array(
            '<li>bla</li>',
            '<li>haa</li>'
        ));
        $this->assertSame('<ul id="testId" class="testclass"><li>bla</li><li>haa</li></ul>', $cList->toHTML());
        $cList = new cHTMLList('ul', 'testId', 'testclass', array());
        $this->assertSame('<ul id="testId" class="testclass"></ul>', $cList->toHTML());
        $cListItem = new cHTMLListItem('testId', 'testClass');
        $cList = new cHTMLList('ul', 'testId', 'testclass', array(
            $cListItem
        ));
        $this->assertSame('<ul id="testId" class="testclass"><li id="testId" class="testClass"></li></ul>', $cList->toHTML());
    }

}
?>